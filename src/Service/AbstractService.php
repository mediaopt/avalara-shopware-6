<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use MoptAvalara6\Adapter\AdapterInterface;
use Monolog\Logger;
use MoptAvalara6\Bootstrap\Form;

/**
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Service
 */
abstract class AbstractService
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter, Logger $logger)
    {
        $this->adapter = $adapter;
        $this->logger = $logger;
    }

    /**
     * get adapter
     *
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param mixed $response
     * @param string $docCode
     * @return mixed
     */
    public function checkResponse($response, string $docCode, string $process)
    {
        if (!is_object($response)) {
            $this->log("Avalara $process can not be parsed", Logger::ERROR, $response);
            return false;
        }

        if ($response->code != $docCode) {
            $this->log("Avalara $process response docCode is {$response->code}, request code is $docCode", Logger::ERROR, $response);
            return false;
        }

        if ($response->status == 'Cancelled') {
            $this->log("Order with docCode: $docCode has been canceled", 0, $response);
        } elseif ($response->totalTax < 0) {
            $this->log("Refund request for docCOde: $docCode was created", 0, $response);
            return $response;
        } else {
            $this->log("Avalara transaction was not $process, docCode is $docCode", Logger::ERROR, $response);
        }

        return false;
    }

    /**
     * @param string $message
     * @param int $logLevel
     * @param mixed $additionalData
     * @return void
     */
    public function log(string $message, int $logLevel = 0, $additionalData = '')
    {
        if ($logLevel == 0) {
            $logLevel = $this->getLogLevel();
        }

        $this->logger->addRecord(
            $logLevel,
            $message,
            [
                'source' => 'Avalara',
                'environment' => 'env',
                'additionalData' => json_encode($additionalData),
            ]
        );
    }

    /**
     * get monolog log-level by module configuration
     * @return int
     */
    protected function getLogLevel()
    {
        $logLevel = 'INFO';

        if ($overrideLogLevel = $this->adapter->getPluginConfig(Form::LOG_LEVEL)) {
            $logLevel = $overrideLogLevel;
        }

        //set levels
        switch ($logLevel) {
            case 'INFO':
                return Logger::INFO;
            case 'ERROR':
                return Logger::ERROR;
            case 'DEBUG':
            default:
                return Logger::DEBUG;
        }
    }

}
