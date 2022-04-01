<?php declare(strict_types=1);

namespace MoptAvalara6\Controller;

use Avalara\AddressLocationInfo;
use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Service\ValidateAddress;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @RouteScope(scopes={"api"})
 */
class TestConnection extends AbstractController
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
     *     "/api/_action/avalara/test-connection",
     *     name="api.action.avalara.test.connection",
     *     methods={"GET"}
     * )
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $client = $adapter->getAvaTaxClient();

        $pingResponse = $client->ping();

        $messages = ['Connection test failed: unknown error.'];

        if (!empty($pingResponse->authenticated)) {
            $messages = ['Connection test successful.'];
        }

        $this->validateOriginAddress($adapter, $messages);

        return new JsonResponse([
            'message' => implode("</br>", $messages)
        ]);
    }

    /**
     * @param AvalaraSDKAdapter $adapter
     * @param array $messages
     * @return void
     */
    private function validateOriginAddress(AvalaraSDKAdapter $adapter, array &$messages)
    {

        $addressFactory = $adapter->getFactory('AddressFactory');
        $originAddress = $addressFactory->buildOriginAddress();
        $service = $adapter->getService('ValidateAddress');
        $emptyFields = $service->getEmptyFields($originAddress);

        if (empty($emptyFields)) {
            $this->avalaraValidation($service, $originAddress, $messages);
        } else {
            foreach ($emptyFields as $emptyField) {
                $messages[] = "Origin $emptyField should not be empty";
            }
        }
    }

    /**
     * @param ValidateAddress $service
     * @param AddressLocationInfo $originAddress
     * @param array $messages
     * @return void
     */
    private function avalaraValidation (ValidateAddress $service, AddressLocationInfo $originAddress, array &$messages)
    {
        $response = $service->validate($originAddress);
        $validation = $service->parseAvalaraResponse($originAddress, $response);

        switch ($validation['code']) {
            case ValidateAddress::VALIDATION_CODE_VALID:
            {
                $messages[] = 'Origin address is valid';
                break;
            }
            case ValidateAddress::VALIDATION_CODE_INVALID:
            {
                $messages[] = 'Origin address is invalid';
                break;
            }
            case ValidateAddress::VALIDATION_CODE_BAD_RESPONSE:
            {
                $messages[] = 'Bad response from Avalara';
                break;
            }
        }

        if (!empty($validation['suggestedAddress'])) {
            $messages[] = 'Suggested address from Avalara:';
            foreach ($validation['suggestedAddress'] as $key => $item) {
                $messages[] = "$key is '{$originAddress->$key}', can be changed to '$item'";
            }
        }

        if (!empty($validation['messages'])) {
            $messages[] = 'Errors from Avalara:';
            foreach ($validation['messages'] as $key => $item) {
                $messages = array_merge($messages, $validation['messages']);
            }
        }
    }
}
