<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\CreateTransactionModel;
use Avalara\TransactionModel;
use Monolog\Logger;
use MoptAvalara6\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Service
 */
class GetTax extends AbstractService
{

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
    }

    /**
     * @param CreateTransactionModel $model
     * @param Logger $logger
     * @return TransactionModel
     */
    public function calculate(CreateTransactionModel $model, Logger $logger)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        $model->date = date(DATE_W3C);
        $this->log($logger, 'Avalara request', $model);
        $response = $client->createTransaction(null, $model);
        $this->log($logger, 'Avalara response', $response);
        return $response;
    }
}
