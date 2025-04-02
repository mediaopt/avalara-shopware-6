<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use Avalara\CreateTransactionModel;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Service\SessionService;
use OpenApi\Context;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;
use Monolog\Logger;
use Avalara\DocumentType;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private Session $session;

    private Logger $logger;
    private EntityRepository $categoryRepository;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     * @param EntityRepository $categoryRepository
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Logger $logger,
        EntityRepository $categoryRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $logger;
        $this->session = new SessionService();
    }

    /**
     * @return array[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => ['makeAvalaraCommitCall', 1]
        ];
    }

    /**
     * @param CheckoutOrderPlacedEvent $event
     * @return void
     */
    public function makeAvalaraCommitCall(CheckoutOrderPlacedEvent $event): void
    {
        $salesChannelId = $event->getSalesChannelId();
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        if ($adapter->getPluginConfig(Form::SEND_GET_TAX_ONLY)) {
            return;
        }

        $sessionModel = $this->session->getValue(Form::SESSION_AVALARA_MODEL, $adapter);

        if (!empty($sessionModel)) {
            $orderNumber = $event->getOrder()->getOrderNumber();
            /* @var CreateTransactionModel */
            $avalaraRequestModel = unserialize($sessionModel);
            $avalaraRequestModel->commit = true;
            $avalaraRequestModel->code = $orderNumber;
            $avalaraRequestModel->type = DocumentType::C_SALESINVOICE;

            $service = $adapter->getService('GetTax');
            $result = $service->calculate($avalaraRequestModel);

            if (!is_object($result)) {
                $service->log('Unexpected response from Avalara.', Logger::ERROR, $result);
            } else {
                if (is_null($result->code)) {
                    $service->log('Can not get tax document code from Avalara response.', Logger::ERROR, $result);
                } elseif ($result->code != $orderNumber) {
                    $service->log(
                        "Tax code ({$result->code}) is not the same as order number {$orderNumber}",
                        Logger::ERROR,
                        $result
                    );
                }
            }

            $this->cleanSession($adapter);
        } else {
            $order = $event->getOrder();
            $customer =  $order->getOrderCustomer()->getCustomer();

            $customerId = $customer->getId();
            $currencyIso = $order->getCurrency()->getIsoCode();
            $taxIncluded = $this->isTaxIncluded($customer);
            $context = $event->getContext();

            $customerAddress = $order->getDeliveries()->getShippingAddress()->first();
            $lineItems = $order->getLineItems()->getElements();
            $shippingMethod = $order->getDeliveries()->first()->getShippingMethod();
            $shippingPrice = $order->getShippingCosts()->getUnitPrice();

            $adapter->getFactory('TransactionModelFactory')
                ->build(
                    $customerAddress,
                    $lineItems,
                    $shippingMethod,
                    $shippingPrice,
                    $customerId,
                    $currencyIso,
                    $taxIncluded,
                    $this->categoryRepository,
                    $context,
                    true
                );
        }
    }

    /**
     * @param CustomerEntity $customer
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function isTaxIncluded(CustomerEntity $customer): bool
    {
        $groupId = $customer->getGroupId();
        $connection = Kernel::getConnection();

        $sql = "SELECT display_gross FROM customer_group WHERE id = UNHEX('$groupId')";

        $isTaxIncluded = $connection->executeQuery($sql)->fetchAssociative();

        return $isTaxIncluded['display_gross'] == false;
    }

    /**
     * @param AvalaraSDKAdapter $adapter
     * @return void
     */
    private function cleanSession(AvalaraSDKAdapter $adapter)
    {
        foreach (Form::SESSION_KEYS as $key) {
            $this->session->setValue($key, null, $adapter);
        }
    }
}
