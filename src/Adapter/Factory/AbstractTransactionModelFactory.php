<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressesModel;
use Avalara\AddressLocationInfo;
use Avalara\LineItemModel;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;

/**
 *
 * Factory to create CreateTransactionModel from a basket/order
 *
 * @author Mediaopt GmbH
 * @package \MoptAvalara6\Adapter\Factory
 */
abstract class AbstractTransactionModelFactory extends AbstractFactory
{
    /**
     * @param OrderAddressEntity|CustomerAddressEntity $address
     * @return \Avalara\AddressesModel
     */
    protected function getAddressesModel(OrderAddressEntity|CustomerAddressEntity $address)
    {
        $addressFactory = $this->getAddressFactory();

        $addressesModel = new AddressesModel();
        $addressesModel->shipFrom = $addressFactory->buildOriginAddress();
        $addressesModel->shipTo = $addressFactory->buildDeliveryAddress($address);

        return $addressesModel;
    }

    /**
     * @return AddressFactory
     */
    protected function getAddressFactory()
    {
        return $this->getAdapter()->getFactory('AddressFactory');
    }

    /**
     * @param mixed $lineItems
     * @param AddressLocationInfo $deliveryAddress
     * @param bool $taxIncluded
     * @param EntityRepository $categoryRepository
     * @param Context $context
     * @param bool $discounted
     * @param ShippingMethodEntity $shippingMethod
     * @param float|null $price
     * @return array
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getLineModels(
        mixed                $lineItems,
        AddressLocationInfo  $deliveryAddress,
        bool                 $taxIncluded,
        EntityRepository     $categoryRepository,
        Context              $context,
        bool                 $discounted,
        ShippingMethodEntity $shippingMethod,
        ?float               $price
    )
    {
        $lineFactory = $this->getLineFactory();
        $lines = [];

        foreach ($lineItems as $lineItem) {
            if ($newLine = $lineFactory->build($lineItem, $deliveryAddress, $taxIncluded, $categoryRepository, $context, $discounted)) {
                $lines[] = $newLine;
            }
        }

        if ($shippingModel = $this->buildShippingModel($shippingMethod, $price)) {
            $lines[] = $shippingModel;
        }

        return $lines;
    }

    /**
     * @param ShippingMethodEntity $shippingMethod
     * @param ?float $price
     * @return LineItemModel
     */
    protected function buildShippingModel(ShippingMethodEntity $shippingMethod, ?float $price)
    {
        if (null === $price) {
            return null;
        }

        return $this->getShippingFactory()->build($shippingMethod, $price);
    }

    /**
     * @return \MoptAvalara6\Adapter\Factory\LineFactory
     */
    protected function getLineFactory()
    {
        return $this->getAdapter()->getFactory('LineFactory');
    }

    /**
     * @return \MoptAvalara6\Adapter\Factory\ShippingFactory
     */
    protected function getShippingFactory()
    {
        return $this->getAdapter()->getFactory('ShippingFactory');
    }


    /**
     * @param array $elements
     * @return float
     */
    protected function calculateDiscount(array $elements)
    {
        $discount = 0.0;

        foreach ($elements as $lineItem) {
            if (LineFactory::isDiscount($lineItem)) {
                [$price, $quantity] = LineFactory::getLineItemDetails($lineItem);
                $discount += abs($price * $quantity);
            }
        }

        return $discount;
    }
}
