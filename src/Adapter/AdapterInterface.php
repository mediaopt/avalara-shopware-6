<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter;

/**
 * Adapter interface for the Avalara SDK.
 *
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
interface AdapterInterface
{
    /**
     * @return \Avalara\AvaTaxClient
     */
    public function getAvaTaxClient();

    /**
     * @param string $key
     * @return mixed
     */
    public function getPluginConfig($key);
}
