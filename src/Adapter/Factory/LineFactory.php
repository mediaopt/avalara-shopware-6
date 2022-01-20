<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\LineItemModel;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

/**
 *
 * Factory to create \Avalara\LineItemModel
 *
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class LineFactory extends AbstractFactory
{
    /**
     * build Line-model based on passed in lineData
     *
     * @param LineItem $lineItem
     * @return LineItemModel
     */
    public function build($lineItem)
    {
        $line = new LineItemModel();
        $line->number = $lineItem->getId();
        $line->itemCode = $lineItem->getId();
        $line->amount = $lineItem->getPrice()->getUnitPrice();
        $line->quantity = $lineItem->getPrice()->getQuantity();
        $line->description = $lineItem->getLabel();
        $line->taxIncluded = false;

        // todo discounted

        return $line;
    }

}
