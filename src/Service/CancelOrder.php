<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Monolog\Logger;
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
    public function processTransaction(string $docCode)
    {
        $adapter = $this->getAdapter();
        if ($adapter->getPluginConfig(Form::SEND_GET_TAX_ONLY)) {
            $this->log("Cannot void Avalara transaction. Only get tax requests are enabled.", Logger::INFO);
            return;
        }

        try {
            if (empty($docCode)) {
                $this->log("Cannot void Avalara transaction with empty DocCode", Logger::ERROR);
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

            $this->log('Avalara void request', 0, $request);

            $client = $adapter->getAvaTaxClient();
            if (!$response = $client->voidTransaction(
                $request['companyCode'],
                $request['docCode'],
                $request['documentType'],
                null,
                $request['model']
            )) {
                $this->log('Empty response from Avalara on void transaction ' . $docCode, Logger::ERROR);
                return;
            } else {
                $this->checkResponse($response, $docCode, 'cancel');
            }
        } catch (\Exception $e) {
            $this->log('CancelTax call failed', Logger::ERROR, $e->getMessage());
        }
    }
}
