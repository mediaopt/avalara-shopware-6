<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;

use Avalara\CreateTransactionModel;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
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

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     * @param Session $session
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Logger $logger,
        Session $session
    )
    {
        $this->systemConfigService = $systemConfigService;
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
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        if ($adapter->getPluginConfig(Form::SEND_GET_TAX_ONLY)) {
            return;
        }

        $sessionModel = $this->session->get(Form::SESSION_AVALARA_MODEL);

        if (!empty($sessionModel)) {
            $orderNumber = $event->getPage()->getOrder()->getOrderNumber();
            $avalaraRequestModel = unserialize($sessionModel);
            $avalaraRequestModel->commit = true;
            $avalaraRequestModel->code = $orderNumber;
            $avalaraRequestModel->type = DocumentType::C_SALESINVOICE;

            $service = $adapter->getService('GetTax');
            $result = $service->calculate($avalaraRequestModel);

            $order = $event->getPage()->getOrder();
            if (is_null($result->code)) {
                $service->log('Can not get tax document code from Avalara response.', Logger::ERROR, $result);
            } elseif ($result->code != $order->getId()) {
                $service->log(
                    "Tax code ({$result->code}) is not the same as order ID {$order->getId()}",
                    Logger::ERROR,
                    $result
                );
            }

            $this->cleanSession();
        }
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
