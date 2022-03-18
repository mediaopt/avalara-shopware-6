<?php declare(strict_types=1);

namespace MoptAvalara6\Storefront\Controller;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractChangeCustomerProfileRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractDeleteAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractListAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractUpsertAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoadedHook;
use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoader;
use Shopware\Storefront\Page\Address\Listing\AddressListingPageLoader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Monolog\Logger;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Storefront\Framework\Routing\Annotation\NoStore;

/**
 * @RouteScope(scopes={"storefront"})
 */
class AddressController extends StorefrontController
{
    private AccountService $accountService;

    private AddressListingPageLoader $addressListingPageLoader;

    private AddressDetailPageLoader $addressDetailPageLoader;

    private AbstractListAddressRoute $listAddressRoute;

    private AbstractUpsertAddressRoute $updateAddressRoute;

    private AbstractDeleteAddressRoute $deleteAddressRoute;

    private AbstractChangeCustomerProfileRoute $updateCustomerProfileRoute;
    private SystemConfigService $systemConfigService;
    private Logger $logger;
    private Session $session;

    public function __construct(
        AddressListingPageLoader           $addressListingPageLoader,
        AddressDetailPageLoader            $addressDetailPageLoader,
        AccountService                     $accountService,
        AbstractListAddressRoute           $listAddressRoute,
        AbstractUpsertAddressRoute         $updateAddressRoute,
        AbstractDeleteAddressRoute         $deleteAddressRoute,
        AbstractChangeCustomerProfileRoute $updateCustomerProfileRoute,
        SystemConfigService                $systemConfigService,
        Logger                             $logger,
        Session                            $session
    )
    {
        $this->accountService = $accountService;
        $this->addressListingPageLoader = $addressListingPageLoader;
        $this->addressDetailPageLoader = $addressDetailPageLoader;
        $this->listAddressRoute = $listAddressRoute;
        $this->updateAddressRoute = $updateAddressRoute;
        $this->deleteAddressRoute = $deleteAddressRoute;
        $this->updateCustomerProfileRoute = $updateCustomerProfileRoute;
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * @Since("6.0.0.0")
     * @LoginRequired()
     * @Route("/account/address/{addressId}", name="frontend.account.address.edit.page", options={"seo"="false"}, methods={"GET"})
     * @NoStore
     *
     * @throws CustomerNotLoggedInException
     */
    public function accountEditAddress(Request $request, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $page = $this->addressDetailPageLoader->load($request, $context, $customer);

        $formViolations = $this->buildFormError($request->get('addressId'));

        $this->hook(new AddressDetailPageLoadedHook($page, $context));

        return $this->renderStorefront(
            '@Storefront/storefront/page/account/addressbook/edit.html.twig',
            [
                'page' => $page,
                'formViolations' => $formViolations
            ]
        );
    }

    /**
     * @Since("6.0.0.0")
     * @LoginRequired()
     * @Route("/account/address/create", name="frontend.account.address.create", options={"seo"="false"}, methods={"POST"})
     * @Route("/account/address/{addressId}", name="frontend.account.address.edit.save", options={"seo"="false"}, methods={"POST"})
     *
     * @throws CustomerNotLoggedInException
     */
    public function saveAddress(RequestDataBag $data, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        /** @var RequestDataBag $address */
        $address = $data->get('address');
        try {
            $this->updateAddressRoute->upsert(
                $address->get('id'),
                $address->toRequestDataBag(),
                $context,
                $customer
            );

            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
            $addressFactory = $adapter->getFactory('AddressFactory');
            $addressLocationInfo = $addressFactory->buildDataBagAddress($address);
            $addressFactory->validate($addressLocationInfo, $address->get('id'), $this->session, false);

            if ($formViolations = $this->buildFormError($address->get('id'))) {
                throw $formViolations;
            }
            return new RedirectResponse($this->generateUrl('frontend.account.address.page', ['addressSaved' => true]));
        } catch (ConstraintViolationException $formViolations) {
        }

        if (!$address->get('id')) {
            return $this->forwardToRoute('frontend.account.address.create.page', ['formViolations' => $formViolations]);
        }

        $addressFactory->validate($addressLocationInfo, $address->get('id'), $this->session, false);

        return $this->forwardToRoute(
            'frontend.account.address.edit.page',
            ['formViolations' => $formViolations],
            ['addressId' => $address->get('id')]
        );
    }

    private function buildFormError($addressId)
    {
        $sessionAddresses = $this->session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

        if (is_array($sessionAddresses) && array_key_exists($addressId, $sessionAddresses)) {
            if ($sessionAddresses[$addressId]['valid']) {
                return [];
            }

            if (!array_key_exists('messages', $sessionAddresses[$addressId])) {
                return [];
            }

            $violations = new \Symfony\Component\Validator\ConstraintViolationList();
            $violations->add(
                new ConstraintViolation(
                    '',
                    '',
                    [],
                    '',
                    '/street',
                    ['street' => ''],
                    null,
                    implode(' ', $sessionAddresses[$addressId]['messages'])
                )
            );
            return new ConstraintViolationException($violations, []);

        }

        return [];
    }
}
