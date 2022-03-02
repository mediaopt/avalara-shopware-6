<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Monolog\Logger;
use MoptAvalara6\Adapter\AdapterInterface;
use MoptAvalara6\Bootstrap\Form;
use Avalara\VoidTransactionModel;
use Avalara\VoidReasonCode;
use Avalara\DocumentType;

require_once __DIR__ . '/../../vendor/autoload.php';

/**
 * @author derksen mediaopt GmbH
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
    public function voidTransaction(string $docCode)
    {
        $adapter = $this->getAdapter();
        try {
            if (empty($docCode)) {
                $this->log("Cannot void Avalara transaction with empty DocCode");
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

            $this->log('Avalara void request', $request);

            $client = $adapter->getAvaTaxClient();
            if (!$response = $client->voidTransaction(
                $request['companyCode'],
                $request['docCode'],
                $request['DocumentType'],
                null,
                $request['model']
            )) {
                $this->log('Empty response from Avalara on void transaction ' . $docCode);
                return;
            } else {
                $this->checkResponse($response, $docCode);
            }
        } catch (\Exception $e) {
            $this->log('CancelTax call failed', $e->getMessage());
        }
    }

    /**
     * @param mixed $response
     * @param string $docCode
     * @return void
     */
    private function checkResponse($response, string $docCode){
        if (!is_object($response)){
            $this->log('Avalara void response can not be parsed', $response);
            return;
        }

        if ($response->code != $docCode) {
            $this->log("Avalara void response docCode is {$response->code}, request code is $docCode", $response);
            return;
        }

        if ($response->status != 'Cancelled') {
            $this->log("Avalara transaction was not voided, docCode is $docCode", $response);
        } else {
            $this->log("Order with docCode: $docCode has been voided", $response);
        }
    }
}
