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
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Plugins\MoptAvalara\Adapter\Factory\AddressFactory;

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
     * @return CreateTransactionModel
     */
    public function build(Cart $cart)
    {
        //$user = $this->getUserData(); todo

        $model = new CreateTransactionModel();
        //$model->businessIdentificationNo = $user['billingaddress']['ustid']; todo
        $model->commit = false;
        //$model->customerCode = $user['additional']['user']['id']; todo
        $model->date = date(DATE_W3C);
        //$model->discount = $this->getDiscount();
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = 'USD'; //todo
        $model->addresses = $this->getAddressesModel($cart);

        //$model->lines = $this->getLineModels(); //todo
        //$model->companyCode = $this->getCompanyCode(); //todo
        //$model->parameters = $this->getTransactionParameters(); //todo

        /*if (!empty($user['additional']['user']['mopt_avalara_exemption_code'])) {
            $model->customerUsageType = $user['additional']['user']['mopt_avalara_exemption_code']; todo
        }*/

        return $model;
    }

    /**
     * @param Cart $cart
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel(Cart $cart)
    {
        /* @var $addressFactory AddressFactory */
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress($cart);

        return $addressesModel;
    }
}
