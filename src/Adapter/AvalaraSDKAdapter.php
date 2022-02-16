<?php

namespace MoptAvalara6\Adapter;

require_once  __DIR__ . '/../../vendor/autoload.php';

use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\MoptAvalara6;
use Avalara\AvaTaxClient;
use MoptAvalara6\Adapter\Factory\AbstractFactory;
use MoptAvalara6\Service\AbstractService;

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
    const SEVICES_NAMESPACE = '\MoptAvalara6\Service\\';

    /**
     * @var string
     */
    const FACTORY_NAMESPACE = '\MoptAvalara6\Adapter\Factory\\';

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
     * @var string
     */
    protected $pluginName;

    /**
     * @var string
     */
    protected $pluginVersion;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @param SystemConfigService $cachedConfigService
     */
    public function __construct(SystemConfigService $cachedConfigService)
    {
        $this->systemConfigService = $cachedConfigService;
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
        return $this->systemConfigService->get($key);
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

    /**
     * Get service by type
     *
     * @param string $type
     * @return AbstractService
     */
    public function getService($type)
    {
        if (!isset($this->services[$type])) {
            $name = self::SEVICES_NAMESPACE . ucfirst($type);
            $this->services[$type] = new $name($this);
        }

        return $this->services[$type];
    }

    /**
     * return factory
     *
     * @param string $type
     * @return AbstractFactory
     */
    public function getFactory($type)
    {
        $name = self::FACTORY_NAMESPACE . ucfirst($type);

        return new $name($this);
    }
}
