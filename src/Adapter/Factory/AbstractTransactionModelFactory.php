<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\LineItemModel;
use MoptAvalara6\Bootstrap\Form;
//use MoptAvalara6\LandedCost\LandedCostRequestParams;
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
}
