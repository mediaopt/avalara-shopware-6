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
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\Checkout\Cart\Cart;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 *
 * Factory to create CreateTransactionModel from the order.
 * With committing it to Avalara
 *
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class TransactionModelFactory extends AbstractTransactionModelFactory
{
    /**
     * @param OrderAddressEntity|CustomerAddressEntity $customerAddress
     * @param array $lineItems
     * @param ShippingMethodEntity $shippingMethod
     * @param float|null $shippingPrice
     * @param string $customerId
     * @param string $currencyIso
     * @param bool $taxIncluded
     * @param EntityRepository $categoryRepository
     * @param Context $context
     * @param bool $commit
     * @return CreateTransactionModel
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function build(
        OrderAddressEntity|CustomerAddressEntity $customerAddress,
        array $lineItems,
        ShippingMethodEntity $shippingMethod,
        ?float $shippingPrice,
        string $customerId,
        string $currencyIso,
        bool $taxIncluded,
        EntityRepository $categoryRepository,
        Context $context,
        bool $commit = false
    ): CreateTransactionModel
    {
        $addresses = $this->getAddressesModel($customerAddress);

        $model = new CreateTransactionModel();
        $model->companyCode = $this->getPluginConfig(Form::COMPANY_CODE_FIELD);
        $model->commit = $commit;
        $model->customerCode = $customerId;
        $model->type = DocumentType::C_SALESORDER;
        $model->currencyCode = $currencyIso;
        $model->addresses = $addresses;

        $discount = $this->calculateDiscount($lineItems);
        $discounted = false;
        if ($discount > 0) {
            $model->discount = $discount;
            $discounted = true;
        }

        $model->lines = $this->getLineModels(
            $lineItems,
            $addresses->shipTo,
            $taxIncluded,
            $categoryRepository,
            $context,
            $discounted,
            $shippingMethod,
            $shippingPrice
        );

        return $model;
    }
}
