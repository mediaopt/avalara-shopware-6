<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoptAvalara6\Subscriber;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    public function __construct(
        SystemConfigService $systemConfigService
    ) {
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
            CheckoutCartPageLoadedEvent::class => ['onCartPageLoaded', 1],
        ];
    }

    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        //@TODO: config getTax Call enabled
        //@TODO: if $service->isGetTaxEnabled() BasketSubscriber.php on SW5
        //@TODO: only make call if cart changed or we don't have a saved response
        $cart = $event->getPage()->getCart();
        $lineItems = $cart->getLineItems();
        $customer = $event->getSalesChannelContext()->getCustomer();
        $shippingCountry = $customer->getActiveShippingAddress()->getCountry()->getIso();
        //@TODO: check shippingCountry could be also a call to $service->isGetTaxDisabledForCountry()
        if ($shippingCountry !== 'US' and $shippingCountry !== 'CA') {
            //@TODO: getTaxCall($linesItems)
        }
        //@TODO: save response to session?
        var_dump($shippingCountry);die;
    }

    public function onCartPageLoaded(CheckoutCartPageLoadedEvent $event): void
    {
        $cart = $event->getPage()->getCart();
        var_dump($cart);die;
    }
}
