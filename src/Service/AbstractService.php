<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use MoptAvalara6\Adapter\AdapterInterface;
use Monolog\Logger;

/**
 * @author derksen mediaopt GmbH
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
