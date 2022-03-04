<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use Monolog\Logger;
use MoptAvalara6\Bootstrap\Form;
use Psr\Log\LogLevel;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\Framework\Context;

class OrderChangesSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $orderRepository;

    private Logger $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $orderRepository,
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
        if (!$docCode = $this->getAvalaraDocumentTaxCode($payload['id'], $context)){
            return;
        }

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $cancelStatus = $adapter->getPluginConfig(Form::CANCEL_ORDER_STATUS_FIELD);
        $refundStatus = $adapter->getPluginConfig(Form::REFUND_ORDER_STATUS_FIELD);

        $newOrderStatus = $payload['stateId'];
        switch ($newOrderStatus) {
            case $cancelStatus:
            {
                $this->cancelAvalaraTax($docCode);
                break;
            }
            case $refundStatus:
            {
                $this->refundAvalaraTax($docCode);
                break;
            }
            default :{
                break;
            }
        }
    }

    /**
     * @param string $orderId
     * @param Context $context
     * @return mixed
     */
    private function getAvalaraDocumentTaxCode(string $orderId, Context $context)
    {
        $orders = $this->orderRepository->search(new Criteria([$orderId]), $context);

        $docCodeField = Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE;
        /* @var $order OrderEntity */
        foreach ($orders->getElements() as $order) {
            $customFeilds = $order->getCustomFields();
            if (is_array($customFeilds)) {
                if (array_key_exists($docCodeField, $customFeilds)) {
                    return $customFeilds[$docCodeField];
                } else {
                    $this->logger->log(LogLevel::ERROR, 'There is no Avalara Tax Document Code!');
                    return false;
                }
            } else {
                $this->logger->log(LogLevel::ERROR, "There is no Avalara custom field $docCodeField");
                return false;
            }
        }

        $this->logger->log(LogLevel::ERROR, "There is no order with id = $orderId");
        return false;
    }

    /**
     * @param string $docCode
     * @return void
     */
    private function cancelAvalaraTax(string $docCode)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $service = $adapter->getService('CancelOrder');
        $service->voidTransaction($docCode);
    }

    /**
     * @param string $docCode
     * @return void
     */
    private function refundAvalaraTax(string $docCode)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $service = $adapter->getService('RefundOrder');
        $service->refundTransaction($docCode);
    }
}
