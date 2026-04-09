<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RouterInterface;

class AddressSubscriber implements EventSubscriberInterface
{
    private RouterInterface $router;

    public function __construct(
        RouterInterface     $router,
        SystemConfigService $systemConfigService  // kept for DI compatibility; validation moved to CartValidator
    ) {
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 1],
        ];
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
