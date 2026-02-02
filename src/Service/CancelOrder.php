<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Monolog\Level;
use MoptAvalara6\Adapter\AdapterInterface;
use MoptAvalara6\Bootstrap\Form;
use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Avalara\DocumentType;

/**
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Service
 */
class CancelOrder extends AbstractService
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
            $logHelper->log(Level::Info, "Cannot void Avalara transaction. Only get tax requests are enabled.");
            return;
        }

        try {
            if (empty($docCode)) {
                LogHelper::addLog(Level::Error, "Cannot void Avalara transaction with empty DocCode");
                return;
            }

            $companyCode = $this->getAdapter()->getPluginConfig(Form::COMPANY_CODE_FIELD);
            $model = new VoidTransactionModel();
            $model->code = VoidReasonCode::C_DOCVOIDED;

            $request = [
                'companyCode' => $companyCode,
                'docCode' => $docCode,
                'documentType' => DocumentType::C_SALESINVOICE,
                'model' => $model
            ];

            $logHelper->log(Level::Info, "Avalara void request", $request);

            $client = $adapter->getAvaTaxClient();
            if (!$response = $client->voidTransaction(
                $request['companyCode'],
                $request['docCode'],
                $request['documentType'],
                null,
                $request['model']
            )) {
                LogHelper::addLog(Level::Error, 'Empty response from Avalara on void transaction ' . $docCode, $request);
                return;
            } else {
                $logHelper->log(Level::Info, 'Avalara cancel response', $response);
                $this->checkResponse($response, $docCode, 'cancel');
            }
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, 'CancelTax call failed', $e->getMessage());
        }
    }
}
