<?php declare(strict_types=1);

namespace MoptAvalara6\Controller;

use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class AddressValidationController extends StorefrontController
{
    private SystemConfigService $systemConfigService;
    private Logger $logger;

    public function __construct(
        SystemConfigService                $systemConfigService,
        Logger                             $logger
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    #[Route(path: "/store-api/avalara/address-validate", name: "frontend.api.avalara.address-validate", methods: ['POST'])]
    public function load(Request $request, SalesChannelContext $context): Response
    {
        $salesChannelId = $request->get('salesChannelId');
        $address = $request->get('address');

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);

        $addressFactory = $adapter->getFactory('AddressFactory');
        $addressLocationInfo = $addressFactory->buildAddressBookAddress($address);

        $service = $adapter->getService('ValidateAddress');
        $response = $service->validate($addressLocationInfo);
        $return = $service->parseAvalaraResponse($addressLocationInfo, $response);
        unset($return['hash']);

        return new Response(json_encode($return));
    }
}
