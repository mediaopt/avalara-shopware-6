<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use Monolog\Logger;
use MoptAvalara6\Bootstrap\Form;
use Psr\Log\LogLevel;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\Framework\Context;

class OrderChangesSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private EntityRepository $orderRepository;

    private Logger $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $orderRepository,
        Logger $logger
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten',
        ];
    }

    /**
     * @param EntityWrittenEvent $event
     * @return void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function onOrderWritten(EntityWrittenEvent $event): void
    {
        foreach ($event->getWriteResults() as $result) {
            $payload = $result->getPayload();
            if (is_array($payload) && array_key_exists('stateId', $payload)) {
                $this->processOrder($payload, $event->getContext());
            }
        }
    }

    /**
     * @param array $payload
     * @param Context $context
     * @return void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function processOrder(array $payload, Context $context)
    {
        if (!$order = $this->getOrder($payload['id'], $context)){
            return;
        }

        if (!$docCode = $order->getOrderNumber()){
            return;
        }

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $order->getSalesChannelId());
        $cancelStatus = $adapter->getPluginConfig(Form::CANCEL_ORDER_STATUS_FIELD);
        $refundStatus = $adapter->getPluginConfig(Form::REFUND_ORDER_STATUS_FIELD);

        $newOrderStatus = $payload['stateId'];
        switch ($newOrderStatus) {
            case $cancelStatus:
            {
                $this->processAvalaraTax($docCode, $order->getSalesChannelId(), 'CancelOrder');
                break;
            }
            case $refundStatus:
            {
                $this->processAvalaraTax($docCode, $order->getSalesChannelId(), 'RefundOrder');
                break;
            }
            default :
            {
                break;
            }
        }
    }

    /**
     * @param string $orderId
     * @param Context $context
     * @return OrderEntity|mixed
     */
    private function getOrder(string $orderId, Context $context)
    {
        $orders = $this->orderRepository->search(new Criteria([$orderId]), $context);
        /* @var $order OrderEntity */
        foreach ($orders->getElements() as $order) {
            return $order;
        }
        $this->logger->log(LogLevel::ERROR, "There is no order with id = $orderId");
        return false;
    }

    /**
     * @param string $docCode
     * @param string $salesChannelId
     * @param string $service
     * @return void
     */
    private function processAvalaraTax(string $docCode, string $salesChannelId, string $service)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $service = $adapter->getService($service);
        $service->processTransaction($docCode);
    }
}
