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
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;

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
                if ($this->isOrderCanceled($payload['stateId'])) {
                    $docCode = $this->getAvalaraDocumentTaxCode($payload['id'], $event->getContext());
                    $this->cancelAvalaraTax($docCode);
                }
            }
        }
    }

    /**
     * @param string $stateId
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function isOrderCanceled(string $stateId): bool
    {
        $connection = Kernel::getConnection();
        $sql = "SELECT technical_name FROM state_machine_state WHERE id = UNHEX('$stateId')";
        $technical_name = $connection->executeQuery($sql)->fetchAssociative();

        if ($technical_name['technical_name'] === 'cancelled') {
            return true;
        }

        return false;
    }

    private function getAvalaraDocumentTaxCode($orderId, $context)
    {
        $orders = $this->orderRepository->search(new Criteria([$orderId]), $context);
        /* @var $order OrderEntity */
        foreach ($orders->getElements() as $order) {
            $customFeilds = $order->getCustomFields();
            if (array_key_exists(Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE, $customFeilds)) {
                return $customFeilds[Form::CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE];
            } else {
                $this->logger->log(LogLevel::ERROR, 'There is no Avalara Tax Document Code!');
            }
        }

        $this->logger->log(LogLevel::ERROR, "There is no order with id = $orderId");
    }

    /**
     * @param string $orderId
     * @return void
     */
    private function cancelAvalaraTax(string $orderId)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $service = $adapter->getService('CancelOrder');
        $service->voidTransaction($orderId);
    }
}
