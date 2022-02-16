<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressLocationInfo;
use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\DocumentType;
use Avalara\LineItemModel;
use Shopware\Core\Checkout\Cart\Cart;
use MoptAvalara6\Bootstrap\Form;

/**
 *
 * Factory to create CreateTransactionModel from the basket.
 * Just to get estimated tax and landed cost
 * Without commiting it to Avalara
 *
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class OrderTransactionModelFactory extends AbstractTransactionModelFactory
{
    /**
     * @param Cart $cart
     * @param string $customerId
     * @param $currencyIso
     * @return CreateTransactionModel
     */
    public function build(Cart $cart, string $customerId, $currencyIso): CreateTransactionModel
    {
        $addresses = $this->getAddressesModel($cart);

        $model = new CreateTransactionModel();
        $model->companyCode = $this->getPluginConfig(Form::COMPANY_CODE_FIELD);
        $model->commit = false;
        $model->customerCode = $customerId;
        $model->type = DocumentType::C_SALESINVOICE;
        $model->currencyCode = $currencyIso;
        $model->addresses = $addresses;
        $model->lines = $this->getLineModels($cart, $addresses->shipTo);
        // todo: parameters, customerUsageType, discount
        return $model;
    }

    /**
     * @param Cart $cart
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel(Cart $cart): AddressesModel
    {
        /* @var $addressFactory \MoptAvalara6\Adapter\Factory\AddressFactory */
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress($cart);

        return $addressesModel;
    }

    /**
     * @param Cart $cart
     * @param AddressLocationInfo $deliveryAddress
     * @return LineItemModel[]
     */
    protected function getLineModels(Cart $cart, AddressLocationInfo $deliveryAddress)
    {
        return parent::getLineModels($cart, $deliveryAddress);
    }
}
