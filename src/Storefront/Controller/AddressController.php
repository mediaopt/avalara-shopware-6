<?php declare(strict_types=1);

namespace MoptAvalara6\Storefront\Controller;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Exception\CustomerNotLoggedInException;
use Shopware\Core\Checkout\Cart\Order\Transformer\CustomerTransformer;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\Exception\AddressNotFoundException;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractChangeCustomerProfileRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractDeleteAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractListAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AbstractUpsertAddressRoute;
use Shopware\Core\Checkout\Customer\SalesChannel\AccountService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Routing\Annotation\LoginRequired;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Annotation\Since;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Page\Address\AddressEditorModalStruct;
use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoadedHook;
use Shopware\Storefront\Page\Address\Detail\AddressDetailPageLoader;
use Shopware\Storefront\Page\Address\Listing\AddressBookWidgetLoadedHook;
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
    private const ADDRESS_TYPE_BILLING = 'billing';
    private const ADDRESS_TYPE_SHIPPING = 'shipping';

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
     * @LoginRequired(allowGuest=true)
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
     * @LoginRequired(allowGuest=true)
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

            $salesChannelId = $context->getSalesChannel()->getId();
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
            $addressFactory = $adapter->getFactory('AddressFactory');
            $addressLocationInfo = $addressFactory->buildDataBagAddress($address);
            $addressFactory->validate($addressLocationInfo, $address->get('id'), $this->session, false);

            if ($formViolations = $this->buildFormError($address->get('id'))) {
                throw $formViolations;
            }

            //Redirect to cart page if needed
            $route = $this->session->get(Form::SESSION_AVALARA_REDIRECT_AFTER_ADDRESS_CHANGE);
            $this->session->set(Form::SESSION_AVALARA_REDIRECT_AFTER_ADDRESS_CHANGE, null);
            if (!$route) {
                $route = 'frontend.account.address.page';
            }

            return new RedirectResponse($this->generateUrl($route, ['addressSaved' => true]));
        } catch (ConstraintViolationException $formViolations) {
        }

        if (!$address->get('id')) {
            return $this->forwardToRoute('frontend.account.address.create.page', ['formViolations' => $formViolations]);
        }

        return $this->forwardToRoute(
            'frontend.account.address.edit.page',
            ['formViolations' => $formViolations],
            ['addressId' => $address->get('id')]
        );
    }

    /**
     * @Since("6.0.0.0")
     * @LoginRequired(allowGuest=true)
     * @Route("/widgets/account/address-book", name="frontend.account.addressbook", options={"seo"=true}, methods={"POST"}, defaults={"XmlHttpRequest"=true})
     */
    public function addressBook(Request $request, RequestDataBag $dataBag, SalesChannelContext $context, CustomerEntity $customer): Response
    {
        $viewData = new AddressEditorModalStruct();
        $this->handleChangeableAddresses($viewData, $dataBag, $context, $customer);
        $this->handleAddressCreation($viewData, $dataBag, $context, $customer);
        $this->handleAddressSelection($viewData, $dataBag, $context, $customer);

        $page = $this->addressListingPageLoader->load($request, $context, $customer);

        $this->hook(new AddressBookWidgetLoadedHook($page, $context));

        $viewData->setPage($page);
        if (Feature::isActive('FEATURE_NEXT_15957')) {
            $this->handleCustomerVatIds($dataBag, $context, $customer);
        }

        if ($request->get('redirectTo') || $request->get('forwardTo')) {
            return $this->createActionResponse($request);
        }

        $salesChannelId = $context->getSalesChannel()->getId();
        $this->validateAddressBook($request, $salesChannelId);

        $response = $this->renderStorefront(
            '@Storefront/storefront/component/address/address-editor-modal.html.twig',
            $viewData->getVars()
        );

        $response->headers->set('x-robots-tag', 'noindex');

        return $response;
    }

    private function handleAddressCreation(
        AddressEditorModalStruct $viewData,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): void {
        /** @var DataBag|null $addressData */
        $addressData = $dataBag->get('address');
        $addressId = null;

        if ($addressData === null) {
            return;
        }

        try {
            $response = $this->updateAddressRoute->upsert(
                $addressData->get('id'),
                $addressData->toRequestDataBag(),
                $context,
                $customer
            );

            $addressId = $response->getAddress()->getId();

            $addressType = null;

            if ($viewData->isChangeBilling()) {
                $addressType = self::ADDRESS_TYPE_BILLING;
            } elseif ($viewData->isChangeShipping()) {
                $addressType = self::ADDRESS_TYPE_SHIPPING;
            }

            // prepare data to set newly created address as customers default
            if ($addressType) {
                $dataBag->set('selectAddress', new RequestDataBag([
                    'id' => $addressId,
                    'type' => $addressType,
                ]));
            }

            $success = true;
            $messages = ['type' => 'success', 'text' => $this->trans('account.addressSaved')];
        } catch (\Exception $exception) {
            $success = false;
            $messages = ['type' => 'danger', 'text' => $this->trans('error.message-default')];
        }

        $viewData->setAddressId($addressId);
        $viewData->setSuccess($success);
        $viewData->setMessages($messages);
    }

    private function handleChangeableAddresses(
        AddressEditorModalStruct $viewData,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): void {
        $changeableAddresses = $dataBag->get('changeableAddresses');

        if ($changeableAddresses === null) {
            return;
        }

        $viewData->setChangeShipping((bool) $changeableAddresses->get('changeShipping'));
        $viewData->setChangeBilling((bool) $changeableAddresses->get('changeBilling'));

        $addressId = $dataBag->get('id');

        if (!$addressId) {
            return;
        }

        $address = $this->getById($addressId, $context, $customer);
        $viewData->setAddress($address);
    }

    /**
     * @throws CustomerNotLoggedInException
     * @throws InvalidUuidException
     */
    private function handleAddressSelection(
        AddressEditorModalStruct $viewData,
        RequestDataBag $dataBag,
        SalesChannelContext $context,
        CustomerEntity $customer
    ): void {
        $selectedAddress = $dataBag->get('selectAddress');

        if ($selectedAddress === null) {
            return;
        }

        $addressType = $selectedAddress->get('type');
        $addressId = $selectedAddress->get('id');

        if (!Uuid::isValid($addressId)) {
            throw new InvalidUuidException($addressId);
        }

        $success = true;

        try {
            if ($addressType === self::ADDRESS_TYPE_SHIPPING) {
                $address = $this->getById($addressId, $context, $customer);
                $context->getCustomer()->setDefaultShippingAddress($address);
                $this->accountService->setDefaultShippingAddress($addressId, $context, $customer);
            } elseif ($addressType === self::ADDRESS_TYPE_BILLING) {
                $address = $this->getById($addressId, $context, $customer);
                $context->getCustomer()->setDefaultBillingAddress($address);
                $this->accountService->setDefaultBillingAddress($addressId, $context, $customer);
            } else {
                $success = false;
            }
        } catch (AddressNotFoundException $exception) {
            $success = false;
        }

        if ($success) {
            $this->addFlash(self::SUCCESS, $this->trans('account.addressDefaultChanged'));
        } else {
            $this->addFlash(self::DANGER, $this->trans('account.addressDefaultNotChanged'));
        }

        $viewData->setSuccess($success);
    }

    private function getById(string $addressId, SalesChannelContext $context, CustomerEntity $customer): CustomerAddressEntity
    {
        if (!Uuid::isValid($addressId)) {
            throw new InvalidUuidException($addressId);
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $addressId));
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));

        $address = $this->listAddressRoute->load($criteria, $context, $customer)->getAddressCollection()->get($addressId);

        if (!$address) {
            throw new AddressNotFoundException($addressId);
        }

        return $address;
    }

    /**
     * @param Request $request
     * @param string $salesChannelId
     * @return bool|Response
     */
    private function validateAddressBook(Request $request, string $salesChannelId)
    {
        if ($addressId = $request->get('addressId')) {
            $address = $request->get('address');
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
            $addressFactory = $adapter->getFactory('AddressFactory');
            $addressLocationInfo = $addressFactory->buildAddressBookAddress($address);
            $addressFactory->validate($addressLocationInfo, $addressId, $this->session, false);
        }
        return false;
    }

    private function handleCustomerVatIds(RequestDataBag $dataBag, SalesChannelContext $context, CustomerEntity $customer): void
    {
        if (!$dataBag->has('vatIds')) {
            return;
        }

        $newVatIds = $dataBag->get('vatIds')->all();
        $oldVatIds = $customer->getVatIds() ?? [];
        if (!array_diff($newVatIds, $oldVatIds) && !array_diff($oldVatIds, $newVatIds)) {
            return;
        }

        $dataCustomer = CustomerTransformer::transform($customer);
        $dataCustomer['vatIds'] = $newVatIds;
        $dataCustomer['accountType'] = $customer->getCompany() === null ? CustomerEntity::ACCOUNT_TYPE_PRIVATE : CustomerEntity::ACCOUNT_TYPE_BUSINESS;

        $newDataBag = new RequestDataBag($dataCustomer);

        $this->updateCustomerProfileRoute->change($newDataBag, $context, $customer);
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
