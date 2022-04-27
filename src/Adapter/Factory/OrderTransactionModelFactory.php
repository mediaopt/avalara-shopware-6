<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\CreateTransactionModel;
use Avalara\AddressesModel;
use Avalara\DocumentType;
use Shopware\Core\Checkout\Cart\Cart;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

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
     * @param EntityRepositoryInterface $categoryRepository
     * @param SalesChannelContext $context
     * @return CreateTransactionModel
     */
    public function build(
        Cart $cart,
        string $customerId,
        string $currencyIso,
        bool $taxIncluded,
        EntityRepositoryInterface $categoryRepository,
        SalesChannelContext $context
    ): CreateTransactionModel
    {
        $addresses = $this->getAddressesModel($cart);

        $model = new CreateTransactionModel();
        $model->companyCode = $this->getPluginConfig(Form::COMPANY_CODE_FIELD);
        $model->commit = false;
        $model->customerCode = $customerId;
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = $currencyIso;
        $model->addresses = $addresses;

        $discount = $this->calculateDiscount($cart);
        $discounted = false;
        if ($discount > 0) {
            $model->discount = $discount;
            $discounted = true;
        }

        $model->lines = $this->getLineModels($cart, $addresses->shipTo, $taxIncluded, $categoryRepository, $context, $discounted);

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
     * @return float
     */
    private function calculateDiscount(Cart $cart)
    {
        $discount = 0.0;

        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            if (LineFactory::isDiscount($lineItem)) {
                $price = $lineItem->getPrice()->getUnitPrice();
                $quantity = $lineItem->getPrice()->getQuantity();
                $discount += abs($price * $quantity);
            }
        }

        return $discount;
    }

}
