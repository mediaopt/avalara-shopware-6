<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use MoptAvalara6\Adapter\AdapterInterface;
use Monolog\Logger;

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
     * @return void
     */
    public function checkResponse($response, string $docCode, string $process)
    {
        if (!is_object($response)) {
            $this->log("Avalara $process can not be parsed", $response);
            return;
        }

        if ($response->code != $docCode) {
            $this->log("Avalara $process response docCode is {$response->code}, request code is $docCode", $response);
            return;
        }

        if ($response->status != 'Cancelled') {
            $this->log("Avalara transaction was not $process, docCode is $docCode", $response);
        } else {
            $this->log("Order with docCode: $docCode has been $process", $response);
        }
    }

    /**
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public function log(string $message, $additionalData = '') {
        $this->logger->addRecord(
            Logger::INFO,
            $message,
            [
                'source' => 'Avalara',
                'environment' => 'env',
                'additionalData' => json_encode($additionalData),
            ]
        );
    }
}