<?php

namespace MoptAvalara6\Controller\Api;

use Avalara\AddressLocationInfo;
use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\Service\ValidateAddress;
use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ApiTestController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    private Logger $logger;

    public function __construct(SystemConfigService $systemConfigService, $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/api/_action/avalara-api-test/test-connection",
     *     name="api.action.avalara.test.connection",
     *     methods={"POST"}
     * )
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        $credentials = [
            'accountNumber' => $request->request->get(Form::ACCOUNT_NUMBER_FIELD),
            'licenseKey' => $request->request->get(Form::LICENSE_KEY_FIELD),
            'isLiveMode' => $request->request->get(Form::IS_LIVE_MODE_FIELD),
        ];

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

    /**
     * @Route(
     *     "/api/_action/avalara-address-test/test-address",
     *     name="api.action.avalara.test.address",
     *     methods={"POST"}
     * )
     */
    public function testAddress(Request $request, Context $context): JsonResponse
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $addressFactory = $adapter->getFactory('AddressFactory');
        $originAddress = $addressFactory->buildAddressFromArray(
            $this->getAddressFromRequest($request)
        );
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

        return new JsonResponse($result);
    }

    /**
     * @param Request $request
     * @return array
     */
    private function getAddressFromRequest(Request $request): array
    {
        return [
            'address_line_1' => $request->request->get(Form::ORIGIN_ADDRESS_LINE_1_FIELD),
            'address_line_2' => $request->request->get(Form::ORIGIN_ADDRESS_LINE_2_FIELD),
            'address_line_3' => $request->request->get(Form::ORIGIN_ADDRESS_LINE_3_FIELD),
            'city' => $request->request->get(Form::ORIGIN_CITY_FIELD),
            'postcode' => $request->request->get(Form::ORIGIN_POSTAL_CODE_FIELD),
            'region' => $request->request->get(Form::ORIGIN_REGION_FIELD),
            'country' => $request->request->get(Form::ORIGIN_COUNTRY_FIELD),
        ];
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
