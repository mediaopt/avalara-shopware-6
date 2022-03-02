<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use MoptAvalara6\Adapter\AdapterInterface;

/**
 *
 * Abstract factory to generate requests to the AvalaraSDK
 *
 * @author derksen mediaopt GmbH
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

    public function debug($str)
    {
        if (!is_string($str)) {
            $str = json_encode($str);
        }
        $fp = fopen('debug.txt', 'a');//opens file in append mode.
        $timestamp = date('d-m-Y h:i:s ', time());
        fwrite($fp, $timestamp . $str);
        fwrite($fp, "\r\n");
    }

    public function onStorefrontRender($event)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService);
        $service = $adapter->getService('GetTax');
        $client = $service->getAdapter()->getAvaTaxClient();
        $tb = new TransactionBuilder($client, "MediaoptAPITrial", 1, 'ABC');
        $request = $tb
            //global
            ->withAddress('ShipFrom', '123 Main Street', null, null, 'Irvine', 'CA', '92615', 'US')
            ->withAddress('ShipTo', '100 Ravine Lane', null, null, 'Bainbridge Island', 'WA', '98110', 'US')

            //product1
            //->withLine(100.0, 1, null, "P0000000")

            //product2
            //->withLine(1234.58, 1, null, "P0000000")

            //product3
            //->withExemptLine(50.0, null, "NT")

            //product4
            ->withLine(4000.0, 2, null, "P0000000")
            // ->withLineAddress('ShipFrom', "1500 Broadway", null, null, "New York", "NY", "10019", "US")
            ->withLineAddress('ShipFrom', '100 Ravine Lane', null, null, 'Bainbridge Island', 'WA', '98110', 'US')
            //->withLineAddress('ShipTo', "223 Carlton St #600", null, null, "Winnipeg", null, "MB R3C 0V4", "CA");
            ->withLineAddress('ShipTo',  '123 Main Street', null, null, 'Irvine', 'CA', '92615', 'US');

        //product5
        //->withLine(50.0, 1, null, "FR010000");
        //->create();
        ////223 Carlton St #600, Winnipeg, MB R3C 0V4, Kanada

        /**/$this->debug('<h2>Transaction #2</h2>');
        $this->debug(json_encode($request->getModel()) );
        $response = $request->create();
        $this->debug(json_encode($response) );/**/
    }
}
