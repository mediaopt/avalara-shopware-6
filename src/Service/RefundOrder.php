<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\RefundTransactionModel;
use Monolog\Level;
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
     */
    public function __construct(AdapterInterface $adapter)
    {
        parent::__construct($adapter);
    }

    /**
     * @param string $docCode
     * @throws \RuntimeException
     */
    public function processTransaction(string $docCode)
    {
        $adapter = $this->getAdapter();
        $logHelper = new LogHelper($adapter);
        if ($adapter->getPluginConfig(Form::SEND_GET_TAX_ONLY)) {
            $logHelper->log(Level::Info, "Cannot refund Avalara transaction. Only get tax requests are enabled.");
            return;
        }

        try {
            if (empty($docCode)) {
                LogHelper::addLog(Level::Error, "Cannot refund Avalara transaction with empty DocCode");
                return;
            }

            $companyCode = $adapter->getPluginConfig(Form::COMPANY_CODE_FIELD);
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

            $logHelper->log(Level::Info, 'Avalara refund request', $model);

            $client = $adapter->getAvaTaxClient();
            if (!$response = $client->refundTransaction(
                $request['companyCode'],
                $request['docCode'],
                null,
                $request['documentType'],
                null,
                $request['model']
            )) {
                LogHelper::addLog(Level::Error, 'Empty response from Avalara on refund transaction ' . $docCode);
                return;
            } else {
                $logHelper->log(Level::Info, 'Avalara refund response', $response);
                $this->checkResponse($response, $docCode, 'refund');
            }
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, 'RefundTax call failed', $e->getMessage());
        }
    }
}
