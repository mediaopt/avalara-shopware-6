<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\CreateTransactionModel;
use Avalara\TransactionModel;
use MoptAvalara6\Adapter\AdapterInterface;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class GetTax extends AbstractService
{
    /**
     * @var string Item ID in Avalara response
     */
    const IMPORT_FEES_LINE = 'ImportFees';

    /**
     * @var string Item ID in Avalara response
     */
    const IMPORT_DUTIES_LINE = 'ImportDuties';

    /**
     * Array containing the user data
     * @var array
     */
    protected $userData;

    /**
     *
     * @param AdapterInterface $adapter
     */
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
    }

    /**
     *
     * @param CreateTransactionModel $model
     * @return TransactionModel
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        return $client->createTransaction(null, $model);
    }
}
