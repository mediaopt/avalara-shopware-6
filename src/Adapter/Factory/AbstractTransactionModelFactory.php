<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\LineItemModel;
use Shopware\Core\Checkout\Cart\Cart;

/**
 *
 * Factory to create CreateTransactionModel from a basket/order
 *
 * @author derksen mediaopt GmbH
 * @package \MoptAvalara6\Adapter\Factory
 */
abstract class AbstractTransactionModelFactory extends AbstractFactory
{
    /**
     * @param Cart $cart
     * @return \Avalara\AddressesModel
     */
    abstract protected function getAddressesModel(Cart $cart);

    /**
     * @return \MoptAvalara6\Adapter\Factory\AddressFactory
     */
    protected function getAddressFactory()
    {
        return $this->getAdapter()->getFactory('AddressFactory');
    }

    /**
     * @param Cart $cart
     * @return LineItemModel[]
     */
    protected function getLineModels(Cart $cart)
    {
        $lineFactory = $this->getLineFactory();
        $lines = [];

        foreach ($cart->getLineItems()->getFlat() as $lineItem) {
            $lines[] = $lineFactory->build($lineItem);
        }

        if ($shippingModel = $this->getShippingModel($cart)) {
            $lines[] = $shippingModel;
        }

        return $lines;
    }

    /**
     * get shipment information
     *
     * @return LineItemModel
     */
    protected function getShippingModel(Cart $cart)
    {
        $price = $cart->getShippingCosts()->getUnitPrice();
        if (null === $price) {
            return null;
        }

        return $this
            ->getShippingFactory()
            ->build($price)
            ;
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
}
