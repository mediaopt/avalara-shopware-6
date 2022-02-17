<?php declare(strict_types=1);

namespace MoptAvalara6\Subscriber;
require_once __DIR__ . '/../../vendor/autoload.php';

use Avalara\CreateTransactionModel;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Bootstrap\Form;
use Monolog\Logger;

class CheckoutSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;

    private Session $session;

    private Logger $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Session $session
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        Logger $loggerMonolog,
        Session $session
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $loggerMonolog;
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
    public function makeAvalaraCommitCall(): void
    {
        /* @var CreateTransactionModel */
        $sessionModel = $this->session->get(Form::SESSION_AVALARA_MODEL);

        if (!empty($sessionModel)) {
            $avalaraRequestModel = unserialize($sessionModel);
            $avalaraRequestModel->commit = true;

            $adapter = new AvalaraSDKAdapter($this->systemConfigService);
            $service = $adapter->getService('GetTax');
            $service->calculate($avalaraRequestModel, $this->logger);
            $this->session->set(Form::SESSION_AVALARA_MODEL, null);
        }
    }
}
