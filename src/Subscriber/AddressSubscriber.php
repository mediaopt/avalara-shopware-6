<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class AddressSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private RouterInterface $router;

    public function __construct(
        RouterInterface     $router,
        SystemConfigService $systemConfigService
    ) {
        $this->router = $router;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => ['onStorefrontRender', 1],
            KernelEvents::REQUEST        => ['onKernelRequest', 1],
        ];
    }

    /**
     * Validate the customer's active shipping address on checkout pages and cache
     * the result in the session. CartValidator will read this result to show an
     * error on the cart / confirm page.
     */
    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (!in_array($route, ['frontend.checkout.cart.page', 'frontend.checkout.confirm.page'], true)) {
            return;
        }

        $request = $event->getRequest();
        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        if (!$session instanceof Session) {
            return;
        }

        $customer = $event->getSalesChannelContext()->getCustomer();
        if ($customer === null) {
            return;
        }

        $address = $customer->getActiveShippingAddress();
        if ($address === null) {
            return;
        }

        $session->set(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID, $address->getId());

        $salesChannelId = $event->getSalesChannelContext()->getSalesChannel()->getId();
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);

        /** @var \MoptAvalara6\Adapter\Factory\AddressFactory $addressFactory */
        $addressFactory = $adapter->getFactory('AddressFactory');
        $addressLocationInfo = $addressFactory->buildDeliveryAddress($address);
        $addressFactory->validate($addressLocationInfo, $address->getId(), $session);
    }

    /**
     * Intercept POST /checkout/order BEFORE the controller runs.
     * When the shipping address is invalid we issue a proper HTTP redirect to the
     * confirm page instead of letting the controller use forwardToRoute() (which is
     * a server-side forward that keeps the browser URL at /checkout/order, causing
     * subsequent GET requests to that POST-only route to fail with 405).
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->attributes->get('_route') !== 'frontend.checkout.finish.order') {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $session = $request->getSession();
        $addressId = $session->get(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID);
        $sessionAddresses = $session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

        if (!is_array($sessionAddresses) || empty($addressId)) {
            return;
        }

        if (array_key_exists($addressId, $sessionAddresses) && $sessionAddresses[$addressId]['valid'] === false) {
            // A proper redirect changes the browser URL to /checkout/confirm so that
            // any further GET navigation (e.g. after an address change) works correctly.
            $event->setResponse(
                new RedirectResponse($this->router->generate('frontend.checkout.confirm.page'))
            );
        }
    }
}
