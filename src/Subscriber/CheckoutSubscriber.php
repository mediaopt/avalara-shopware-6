<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MoptAvalara6\Subscriber;
require_once  __DIR__ . '/../../vendor/autoload.php';

use Avalara\TransactionBuilder;
use Avalara\TransactionAddressType;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private Session $session;

    /**
     * @param SystemConfigService $systemConfigService
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Session $session
    ) {
        $this->systemConfigService = $systemConfigService;
        $this->session = $session;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        //todo make a commit call to Avalara on "thank you page"
        return [
            CheckoutConfirmPageLoadedEvent::class => ['onConfirmPageLoaded', 1],
        ];
    }

    /**
     * @param CheckoutConfirmPageLoadedEvent $event
     * @return void
     */
    public function onConfirmPageLoaded(CheckoutConfirmPageLoadedEvent $event): void
    {
        //@TODO: config getTax Call enabled
        //@TODO: if $service->isGetTaxEnabled() BasketSubscriber.php on SW5
        $cart = $event->getPage()->getCart();
        $cartKey = md5(json_encode($cart));
        $sessionCartKey = $this->session->get(Form::SESSION_CART_KEY);
        if ($sessionCartKey === $cartKey){
            return;
        }
        $this->session->set(Form::SESSION_CART_KEY, $cartKey);

        $customerId = $event->getSalesChannelContext()->getCustomer()->getId();
        $shippingCountry = $cart->getDeliveries()->getAddresses()->getCountries()->first()->getIso3();

        //@TODO: check shippingCountry could be also a call to $service->isGetTaxDisabledForCountry()
        if ($shippingCountry !== 'USA' and $shippingCountry !== 'CAN') {
            return;
        }

        $adapter =new AvalaraSDKAdapter($this->systemConfigService);
        $model = $adapter->getFactory('OrderTransactionModelFactory')->build($cart, $customerId);
        $service = $adapter->getService('GetTax');
        $response = $service->calculate($model);
        $this->session->set(Form::SESSION_AVALARA_TAXES, $response);
    }
}
