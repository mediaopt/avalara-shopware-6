<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\CreateTransactionModel;
use Monolog\Logger;
use MoptAvalara6\Adapter\AdapterInterface;

/**
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Service
 */
class GetTax extends AbstractService
{

    /**
     *
     * @param AdapterInterface $adapter
     * @param Logger $logger
     */
    public function __construct(AdapterInterface $adapter, Logger $logger)
    {
        parent::__construct($adapter, $logger);
    }

    /**
     * @param CreateTransactionModel $model
     * @return mixed
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        $model->date = date(DATE_W3C);
        try {
            $this->log('Avalara request', $model);
            $response = $client->createTransaction(null, $model);
            $this->log('Avalara response', $response);
            return $response;
        } catch (\Exception $e) {
            $this->log($e->getMessage());
        }

        return false;
    }
}
