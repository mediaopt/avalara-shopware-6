<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\RequestStack;

class CartValidator implements CartValidatorInterface
{
    private RequestStack $requestStack;
    private SystemConfigService $systemConfigService;

    public function __construct(RequestStack $requestStack, SystemConfigService $systemConfigService)
    {
        $this->requestStack = $requestStack;
        $this->systemConfigService = $systemConfigService;
    }

    /**
     * @param Cart $cart
     * @param ErrorCollection $errorCollection
     * @param SalesChannelContext $salesChannelContext
     * @return void
     */
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // 1. Existing Avalara tax calculation error check
        $key = Form::TAX_REQUEST_STATUS;
        $statusFailed = Form::TAX_REQUEST_STATUS_FAILED;
        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            $payload = $lineItem->getPayload();
            if (array_key_exists($key, $payload) && $payload[$key] == $statusFailed) {
                $errors->add(new CartBlockedError());
                return;
            }
        }

        // 2. Avalara address validation
        // We run the full validation here (not just a session read) so the result is
        // always up-to-date at the moment the cart is recalculated – before the page
        // renders. AddressFactory::validate() uses a session hash-cache internally, so
        // no extra Avalara API call is made when the address has not changed.
        $customer = $context->getCustomer();
        if ($customer === null) {
            return;
        }

        $address = $customer->getActiveShippingAddress();
        if ($address === null || $address->getCountry() === null) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session instanceof Session) {
            return;
        }

        $salesChannelId = $context->getSalesChannel()->getId();
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);

        /** @var \MoptAvalara6\Adapter\Factory\AddressFactory $addressFactory */
        $addressFactory = $adapter->getFactory('AddressFactory');
        $addressLocationInfo = $addressFactory->buildDeliveryAddress($address);

        // Keep the session address-ID key in sync so onKernelRequest can also read it.
        $session->set(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID, $address->getId());

        // validate() stores the result in the session and returns immediately when the
        // address hash matches the cached one (checkSession = true by default).
        $addressFactory->validate($addressLocationInfo, $address->getId(), $session);

        // Read the freshly written result.
        $sessionAddresses = $session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);
        $addressId = $address->getId();

        if (is_array($sessionAddresses)
            && array_key_exists($addressId, $sessionAddresses)
            && $sessionAddresses[$addressId]['valid'] === false
        ) {
            $errors->add(new AddressValidationError());
        }
    }
}
