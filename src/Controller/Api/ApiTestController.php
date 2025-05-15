<?php

namespace MoptAvalara6\Controller\Api;

use Avalara\AddressLocationInfo;
use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\Service\ValidateAddress;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class ApiTestController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    private Logger $logger;

    private array $credentialKeys = [
        'accountNumber' => Form::ACCOUNT_NUMBER_FIELD,
        'licenseKey' => Form::LICENSE_KEY_FIELD,
        'isLiveMode' => Form::IS_LIVE_MODE_FIELD,
    ];

    private array $addressKeys = [
        'address_line_1' => Form::ORIGIN_ADDRESS_LINE_1_FIELD,
        'address_line_2' => Form::ORIGIN_ADDRESS_LINE_2_FIELD,
        'address_line_3' => Form::ORIGIN_ADDRESS_LINE_3_FIELD,
        'city' => Form::ORIGIN_CITY_FIELD,
        'postcode' => Form::ORIGIN_POSTAL_CODE_FIELD,
        'region' => Form::ORIGIN_REGION_FIELD,
        'country' => Form::ORIGIN_COUNTRY_FIELD,
    ];

    public function __construct(SystemConfigService $systemConfigService, $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    #[Route(
        path: '/api/_action/avalara-api-test/test-connection',
        name: 'api.action.avalara.test.connection',
        methods: ['POST']
    )]
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        $configFormData = $request->request->all('сonfigData');

        if (empty($configFormData)) {
            return new JsonResponse([
                'success' => false,
                'message' => "There is no config data."
            ]);
        }

        $salesChannelId = $request->request->get('salesChannelId');
        $credentials = $this->buildFormData($salesChannelId, $configFormData, $this->credentialKeys);

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $client = $adapter->getAvaTaxClient($credentials);

        $pingResponse = $client->ping();

        $success = false;
        if (!empty($pingResponse->authenticated)) {
            $success = true;
        }

        return new JsonResponse([
            'success' => $success
        ]);
    }

    #[Route(
        path: '/api/_action/avalara-address-test/test-address',
        name: 'api.action.avalara.test.address',
        methods: ['POST']
    )]
    public function testAddress(Request $request, Context $context): JsonResponse
    {
        $configFormData = $request->request->all('сonfigData');

        if (empty($configFormData)) {
            return new JsonResponse([
                'success' => false,
                'message' => "There is no config data."
            ]);
        }

        $salesChannelId = $request->request->get('salesChannelId');
        $formData = $this->buildFormData($salesChannelId, $configFormData, $this->addressKeys);

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $addressFactory = $adapter->getFactory('AddressFactory');
        $originAddress = $addressFactory->buildAddressFromArray($formData);
        $service = $adapter->getService('ValidateAddress');
        $emptyFields = $service->getEmptyFields($originAddress);

        $result = [
            'success' => false,
            'messages' => ["</br>"]
        ];
        if (empty($emptyFields)) {
            $this->avalaraValidation($service, $originAddress, $result);
        } else {
            foreach ($emptyFields as $emptyField) {
                $result['messages'][] = "Origin $emptyField should not be empty";
            }
        }

        $result['message'] = implode('</br>', $result['messages']);
        unset($result['messages']);

        return new JsonResponse($result);
    }

    /**
     * @param string|null $salesChannelId
     * @param array $configData
     * @param array $keys
     * @return array
     */
    private function buildFormData(?string $salesChannelId, array $configData, array $keys): array
    {
        $globalConfig = [];
        if (array_key_exists('null', $configData)) {
            $globalConfig = $configData['null'];
        }

        //For "All Sales Channels" data will be in "null" part of configData
        $salesChannelId = $salesChannelId ?? 'null';

        $formData = [];
        if (array_key_exists($salesChannelId, $configData)) {
            $channelConfig = $configData[$salesChannelId];
            foreach ($keys as $key => $formKey) {
                if (array_key_exists($formKey, $channelConfig) && !is_null($channelConfig[$formKey])) {
                    $formData[$key] = $channelConfig[$formKey];
                } elseif (array_key_exists($formKey, $globalConfig) && !is_null($globalConfig[$formKey])) {
                    $formData[$key] = $globalConfig[$formKey];
                } else {
                    $formData[$key] = '';
                }
            }
        }

        return $formData;
    }

    /**
     * @param ValidateAddress $service
     * @param AddressLocationInfo $originAddress
     * @param array $result
     * @return void
     */
    private function avalaraValidation(ValidateAddress $service, AddressLocationInfo $originAddress, array &$result)
    {
        $response = $service->validate($originAddress);
        $validation = $service->parseAvalaraResponse($originAddress, $response);

        $messages = [];
        switch ($validation['code']) {
            case ValidateAddress::VALIDATION_CODE_VALID:
            {
                $result['success'] = true;
                break;
            }
            case ValidateAddress::VALIDATION_CODE_INVALID:
            {
                $messages[] = 'Origin address is invalid';
                break;
            }
            case ValidateAddress::VALIDATION_CODE_BAD_RESPONSE:
            default:
            {
                $messages[] = 'Bad response from Avalara';
                break;
            }
        }

        if (!empty($validation['suggestedAddress'])) {
            $messages[] = 'Suggested address changes from Avalara:';
            foreach ($validation['suggestedAddress'] as $key => $item) {
                $messages[] = "$key is '{$originAddress->$key}', can be changed to '$item'";
            }
        }

        if (!empty($validation['messages'])) {
            $messages[] = 'Errors from Avalara:';
            $messages = array_merge($messages, $validation['messages']);
        }

        $result['messages'] = array_merge($result['messages'], $messages);
    }
}
