<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;
require_once __DIR__ . '/../../vendor/autoload.php';

use Avalara\CreateTransactionModel;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Service\GetTax;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;
use Monolog\Logger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $orderRepository;

    private Session $session;

    private Logger $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     * @param Session $session
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $orderRepository,
        Logger $logger,
        Session $session
    )
    {
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
            CheckoutFinishPageLoadedEvent::class => ['makeAvalaraCommitCall', 1],
        ];
    }

    /**
     * @return void
     */
    public function makeAvalaraCommitCall(CheckoutFinishPageLoadedEvent $event): void
    {
        /* @var CreateTransactionModel */
        $sessionModel = $this->session->get(Form::SESSION_AVALARA_MODEL);

        if (!empty($sessionModel)) {
            $orderNumber = $event->getPage()->getOrder()->getOrderNumber();
            $avalaraRequestModel = unserialize($sessionModel);
            $avalaraRequestModel->commit = true;
            $avalaraRequestModel->customerCode = $orderNumber;

            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
            $service = $adapter->getService('GetTax');
            $result = $service->calculate($avalaraRequestModel);

            $this->saveTaxDocumentCode($event, $result, $service);

            $this->cleanSession();
        }
    }

    /**
     * @param CheckoutFinishPageLoadedEvent $event
     * @param mixed $result
     * @return void
     */
    private function saveTaxDocumentCode(CheckoutFinishPageLoadedEvent $event, $result, GetTax $service)
    {
        if (!is_object($result)) {
            return;
        }

        if (is_null($result->code)) {
            $service->log('Can not get tax document code from Avalara response.', $result);
            return;
        }

        $order = $event->getPage()->getOrder();

        $update = [
            'id' => $order->getId(),
            'customFields' => [
                Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE => $result->code
            ]
        ];

        $this->orderRepository->upsert(
            [$update],
            $event->getContext()
        );
    }

    /**
     * @return void
     */
    private function cleanSession()
    {
        foreach (Form::SESSION_KEYS as $key) {
            $this->session->set($key, null);
        }
    }
}
