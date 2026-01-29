<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use Avalara\CreateTransactionModel;
use Monolog\Level;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Service\LogHelper;
use MoptAvalara6\Service\SessionService;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;
use Avalara\DocumentType;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private Session $session;

    private EntityRepository $categoryRepository;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $categoryRepository
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository $categoryRepository
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
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
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);
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
                LogHelper::addLog(Level::Error, 'Unexpected response from Avalara.', $result);
            } else {
                if (is_null($result->code)) {
                    LogHelper::addLog(Level::Error, 'Can not get tax document code from Avalara response.', $result);
                } elseif ($result->code != $orderNumber) {
                    LogHelper::addLog(Level::Error, "Tax code ({$result->code}) is not the same as order number {$orderNumber}", $result);
                }
            }

            self::cleanSession($this->session, $adapter);
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
     * @param SessionService $session
     * @param AvalaraSDKAdapter $adapter
     * @return void
     */
    public static function cleanSession(SessionService $session, AvalaraSDKAdapter $adapter): void
    {
        foreach (Form::SESSION_KEYS as $key) {
            if ($key === Form::SESSION_AVALARA_REDIRECT_TO_ADDRESS_CHANGE) {
                $key .= $adapter->getSalesChannelId();
            }
            $session->setValue($key, null, $adapter);
        }
    }
}
