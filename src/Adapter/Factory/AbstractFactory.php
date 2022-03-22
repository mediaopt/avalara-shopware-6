<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use MoptAvalara6\Adapter\AdapterInterface;

/**
 *
 * Abstract factory to generate requests to the AvalaraSDK
 *
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
abstract class AbstractFactory
{
    /**
     *
     * @var \MoptAvalara6\Adapter\AdapterInterface
     */
    protected $adapter;

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     *
     * @return \Avalara\AvaTaxClient
     */
    public function getSdk()
    {
        return $this->adapter->getAvaTaxClient();
    }

    /**
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getPluginConfig($key)
    {
        return $this->getAdapter()->getPluginConfig($key);
    }
}
