<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
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
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class OrderTransactionModelFactory extends AbstractTransactionModelFactory
{
    /**
     * @param Cart $cart
     * @param string $customerId
     * @param string $currencyIso
     * @param bool $taxIncluded
     * @return CreateTransactionModel
     */
    public function build(Cart $cart, string $customerId, $currencyIso, bool $taxIncluded): CreateTransactionModel
    {
        $addresses = $this->getAddressesModel($cart);

        $model = new CreateTransactionModel();
        $model->companyCode = $this->getPluginConfig(Form::COMPANY_CODE_FIELD);
        $model->commit = false;
        $model->customerCode = $customerId;
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = $currencyIso;
        $model->addresses = $addresses;
        $model->lines = $this->getLineModels($cart, $addresses->shipTo, $taxIncluded);
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
        $customerAddress = $cart->getDeliveries()->getAddresses()->first();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress($customerAddress);

        return $addressesModel;
    }

    /**
     * @param Cart $cart
     * @param AddressLocationInfo $deliveryAddress
     * @param bool $taxIncluded
     * @return LineItemModel[]
     */
    protected function getLineModels(Cart $cart, AddressLocationInfo $deliveryAddress, bool $taxIncluded)
    {
        return parent::getLineModels($cart, $deliveryAddress, $taxIncluded);
    }
}
