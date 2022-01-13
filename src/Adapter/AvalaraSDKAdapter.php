<?php

namespace MoptAvalara6\Adapter;

require_once  __DIR__ . '/../../vendor/autoload.php';

use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\MoptAvalara6;
use Avalara\AvaTaxClient;

/**
 * This is the adaptor for avalara's API
 *
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class AvalaraSDKAdapter implements AdapterInterface
{
    /**
     * @var string
     */
    const SERVICE_NAME = 'AvalaraSdkAdapter';

    /**
     * @var string
     */
    const PRODUCTION_ENV = 'production';

    /**
     * @var string
     */
    const SANDBOX_ENV = 'sandbox';

    /**
     * @var string
     */
    const MACHINE_NAME = 'localhost';

    /**
     *
     * @var AvaTaxClient
     */
    protected $avaTaxClient;

    /**
     *
     * @var string
     */
    protected $pluginName;

    /**
     *
     * @var string
     */
    protected $pluginVersion;

    /**
     * @var SystemConfigService
     */
    private $cachedConfigService;

    /**
     * @param SystemConfigService $cachedConfigService
     */
    public function __construct(SystemConfigService $cachedConfigService)
    {
        $this->cachedConfigService = $cachedConfigService;
    }

    /**
     * @return AvaTaxClient
     */
    public function getAvaTaxClient()
    {
        if ($this->avaTaxClient !== null) {
            return $this->avaTaxClient;
        }

        $avaClient = new AvaTaxClient(
            MoptAvalara6::PLUGIN_NAME,
            MoptAvalara6::PLUGIN_VERSION,
            $this->getMachineName(),
            $this->getSDKEnv()
        );

        $accountNumber = $this->getPluginConfig(Form::ACCOUNT_NUMBER_FIELD);
        $licenseKey = $this->getPluginConfig(Form::LICENSE_KEY_FIELD);
        $avaClient->withSecurity($accountNumber, $licenseKey);
        $this->avaTaxClient = $avaClient;

        // Attach a handler to log all requests
        //todo: logger
        //$avaClient->getHttpClient()->getEmitter()->attach($this->getLogSubscriber());

        return $this->avaTaxClient;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getPluginConfig($key)
    {
        return $this->cachedConfigService->get($key);
    }

    /**
     * @return string
     */
    private function getSDKEnv()
    {
        return $this->getPluginConfig(Form::IS_LIVE_MODE_FIELD)
            ? self::PRODUCTION_ENV
            : self::SANDBOX_ENV
        ;
    }

    /**
     * @return string
     */
    private function getMachineName()
    {
        return self::MACHINE_NAME;
    }

}
