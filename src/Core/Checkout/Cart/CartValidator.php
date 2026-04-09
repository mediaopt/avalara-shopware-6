<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\RequestStack;

class CartValidator implements CartValidatorInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * @param Cart $cart
     * @param ErrorCollection $errorCollection
     * @param SalesChannelContext $salesChannelContext
     * @return void
     */
    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        // Check Avalara tax calculation errors from line items
        $key = Form::TAX_REQUEST_STATUS;
        $statusFailed = Form::TAX_REQUEST_STATUS_FAILED;
        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            $payload = $lineItem->getPayload();
            if (array_key_exists($key, $payload) && $payload[$key] == $statusFailed) {
                $errors->add(new CartBlockedError());
                return;
            }
        }

        // Check Avalara address validation result from session
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $addressId = $session->get(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID);
        $sessionAddresses = $session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

        if (!is_array($sessionAddresses) || empty($addressId)) {
            return;
        }

        if (array_key_exists($addressId, $sessionAddresses) && $sessionAddresses[$addressId]['valid'] === false) {
            $errors->add(new AddressValidationError());
        }
    }
}
