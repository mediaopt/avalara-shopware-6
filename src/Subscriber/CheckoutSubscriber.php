<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;
require_once __DIR__ . '/../../vendor/autoload.php';

use Avalara\CreateTransactionModel;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Checkout\Cart\Event\CartChangedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Order\Event\OrderStateChangeCriteriaEvent;
use Shopware\Storefront\Event\StorefrontRenderEvent;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private Session $session;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Session $session
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Session $session
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->session = $session;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutFinishPageLoadedEvent::class => ['makeAvalaraCommitCall', 1],
            CartChangedEvent::class => ['onCartChangedEvent', 1],
            OrderStateChangeCriteriaEvent::class => ['onOrderChangeEvent', 1],
            StorefrontRenderEvent::class => ['onStorefrontRender', 1],
        ];
    }

    /**
     * @param CartChangedEvent $event
     * @return void
     */
    public function onCartChangedEvent(CartChangedEvent $event)
    {
        $this->session->set(Form::SESSION_CART_UPDATED, true);
        $cart = $event->getCart();
        $customerId = $event->getContext()->getCustomer()->getId();
        $currencyIso = $event->getContext()->getCurrency()->getIsoCode();
        $this->makeAvalaraCall($cart, $customerId, $currencyIso);
    }

    /**
     * @param Cart $cart
     * @param $customerId
     * @param $currencyIso
     * @return void
     */
    public function makeAvalaraCall(Cart $cart, $customerId, $currencyIso): void
    {
        if (!$this->session->get(Form::SESSION_CART_UPDATED)) {
            return;
        }

        $shippingCountry = $cart->getDeliveries()->getAddresses()->getCountries()->first();
        if (is_null($shippingCountry)) {
            return;
        }
        $shippingCountryIso3 = $shippingCountry->getIso3();

        $adapter = new AvalaraSDKAdapter($this->systemConfigService);
        if (!$adapter->getFactory('AddressFactory')->checkCountryRestriction($shippingCountryIso3)) {
            return;
        }

        $model = $adapter->getFactory('OrderTransactionModelFactory')->build($cart, $customerId, $currencyIso);
        $service = $adapter->getService('GetTax');
        $response = $service->calculate($model);

        $transformedTaxes = $this->transformResponseForOverwrite($response);
        $this->session->set(Form::SESSION_AVALARA_TAXES_TRANSFORMED, $transformedTaxes);
        $this->session->set(Form::SESSION_AVALARA_MODEL, $model);
        $this->session->set(Form::SESSION_AVALARA_TAXES, $response);
        $this->session->set(Form::SESSION_CART_UPDATED, false);
    }

    /**
     * @return void
     */
    public function makeAvalaraCommitCall(): void
    {
        /* @var CreateTransactionModel */
        $avalaraModel = $this->session->get(Form::SESSION_AVALARA_MODEL);

        if (!empty($avalaraModel)) {
            $avalaraModel->commit = true;

            $adapter = new AvalaraSDKAdapter($this->systemConfigService);
            $service = $adapter->getService('GetTax');
            $response = $service->calculate($avalaraModel);
            //todo log response
            $this->session->set(Form::SESSION_AVALARA_MODEL, null);
        }
    }

    /**
     * @param \stdClass $response
     * @return array
     */
    public function transformResponseForOverwrite(\stdClass $response): array
    {
        $transformedTax = [];
        foreach ($response->lines as $line) {
            $rate = 0;
            foreach ($line->details as $detail) {
                $rate += $detail->rate;
            }
            $transformedTax[$line->itemCode] = [
                'tax' => $line->tax * $line->quantity,
                'rate' => $rate,
            ];
        }

        return $transformedTax;
    }
}
