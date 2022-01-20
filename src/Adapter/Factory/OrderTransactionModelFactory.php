<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\DocumentType;
use Avalara\LineItemModel;
use Shopware\Core\Checkout\Cart\Cart;

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
     * @return CreateTransactionModel
     */
    public function build(Cart $cart, string $customerId): CreateTransactionModel
    {
        $model = new CreateTransactionModel();
        $model->commit = false;
        $model->customerCode = $customerId;
        $model->date = date(DATE_W3C);
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = 'USD'; //todo
        $model->addresses = $this->getAddressesModel($cart);
        $model->lines = $this->getLineModels($cart);
        // todo: currency, companyCode, parameters, customerUsageType, discount

        return $model;
    }

    /**
     * @param Cart $cart
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel(Cart $cart): AddressesModel
    {
        /* @var $addressFactory \MoptAvalara6\Adapter\Factory\AddressFactory\AddressFactory */
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress($cart);

        return $addressesModel;
    }

    /**
     * @param Cart $cart
     * @return LineItemModel[]
     */
    protected function getLineModels(Cart $cart)
    {
        return parent::getLineModels($cart);
    }
}
