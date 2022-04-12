<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\RefundTransactionModel;
use Monolog\Logger;
use MoptAvalara6\Adapter\AdapterInterface;
use MoptAvalara6\Bootstrap\Form;
use Avalara\DocumentType;

/**
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Service
 */
class RefundOrder extends AbstractService
{
    /**
     * @param AdapterInterface $adapter
     * @param Logger $logger
     */
    public function __construct(AdapterInterface $adapter, Logger $logger)
    {
        parent::__construct($adapter, $logger);
    }

    /**
     * @param string $orderId
     * @throws \RuntimeException
     */
    public function refundTransaction(string $docCode)
    {
        $adapter = $this->getAdapter();
        if ($adapter->getPluginConfig(Bootstrap::SEND_GET_TAX_ONLY)) {
            $this->log("Cannot refund Avalara transaction. Only get tax requests are enabled.");
            return;
        }

        try {
            if (empty($docCode)) {
                $this->log("Cannot refund Avalara transaction with empty DocCode");
                return;
            }

            $companyCode = $this->getAdapter()->getPluginConfig(Form::COMPANY_CODE_FIELD);
            $model = new RefundTransactionModel();
            $model->refundTransactionCode = $docCode;
            $model->refundDate = date('Y-m-d', time());
            $model->refundType = 'Full';
            $model->referenceCode = 'Refund for a committed transaction';

            $request = [
                'companyCode' => $companyCode,
                'docCode' => $docCode,
                'documentType' => DocumentType::C_SALESINVOICE,
                'model' => $model
            ];

            $this->log('Avalara refund request', $model);

            $client = $adapter->getAvaTaxClient();
            if (!$response = $client->refundTransaction(
                $request['companyCode'],
                $request['docCode'],
                null,
                $request['documentType'],
                null,
                $request['model']
            )) {
                $this->log('Empty response from Avalara on refund transaction ' . $docCode);
                return;
            } else {
                $this->checkResponse($response, $docCode, 'refund');
            }
        } catch (\Exception $e) {
            $this->log('RefundTax call failed', $e->getMessage());
        }
    }
}
