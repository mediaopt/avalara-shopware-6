<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;
require_once __DIR__ . '/../../vendor/autoload.php';

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Monolog\Logger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AddressSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $orderRepository;

    private Session $session;

    private Logger $logger;

    private ContainerInterface $container;

    /**
     * @param ContainerInterface $container
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param Session $session
     */
    public function __construct(
        ContainerInterface        $container,
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $orderRepository,
        Logger                    $logger,
        Session                   $session
    )
    {
        $this->container = $container;
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->session = $session;
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'requestEvent',
            StorefrontRenderEvent::class => ['storefrontRenderEvent', 1]
        ];
    }

    /**
     * @param StorefrontRenderEvent $event
     * @return void
     */
    public function storefrontRenderEvent(StorefrontRenderEvent $event)
    {
        if ($this->isCheckoutPage($event)) {
            $customer = $event->getSalesChannelContext()->getCustomer();
            if (!is_object($customer)) {
                return;
            }

            $address = $customer->getActiveShippingAddress();

            $this->session->set(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID, $address->getId());

            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
            $addressFactory = $adapter->getFactory('AddressFactory');
            $addressLocationInfo = $addressFactory->buildDeliveryAddress($address);
            $addressFactory->validate($addressLocationInfo, $address->getId(), $this->session);
        }
    }

    /**
     * @param RequestEvent $event
     * @return void
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function requestEvent(RequestEvent $event)
    {
        if ($this->isCheckoutPage($event)) {
            $route = $event->getRequest()->attributes->get('_route');
            $addressId = $this->session->get(Form::SESSION_AVALARA_CURRENT_ADDRESS_ID);
            $sessionAddresses = $this->session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

            if (is_array($sessionAddresses) && array_key_exists($addressId, $sessionAddresses)) {
                if (!$sessionAddresses[$addressId]['valid']) {
                    $this->session->set(Form::SESSION_AVALARA_REDIRECT_AFTER_ADDRESS_CHANGE, $route);
                    $url = $this->container->get('router')->generate(
                        'frontend.account.address.edit.page',
                        ['addressId' => $addressId]
                    );
                    $event->setResponse(new RedirectResponse($url));
                }
            }
        }
    }

    /**
     * @param mixed $event
     * @return bool
     */
    private function isCheckoutPage($event): bool
    {
        $route = $event->getRequest()->attributes->get('_route');
        if (in_array($route, ['frontend.checkout.confirm.page', 'frontend.checkout.cart.page'])) {
            return true;
        }
        return false;
    }
}
