<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\Service\GetTax;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class CartValidator implements CartValidatorInterface
{
    /**
     * @param Cart $cart
     * @param ErrorCollection $errorCollection
     * @param SalesChannelContext $salesChannelContext
     * @return void
     */
    public function validate(Cart $cart, ErrorCollection $errorCollection, SalesChannelContext $salesChannelContext): void
    {
        $key = Form::TAX_REQUEST_STATUS;
        $statusFailed = Form::TAX_REQUEST_STATUS_FAILED;
        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            $payload = $lineItem->getPayload();
            if (array_key_exists($key, $payload) && $payload[$key] == $statusFailed) {
                $errorCollection->add(new CartBlockedError());
                return;
            }
        }
    }
}
