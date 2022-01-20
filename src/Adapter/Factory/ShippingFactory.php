<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\LineItemModel;

/**
 * @author derksen mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class ShippingFactory extends AbstractFactory
{
    /**
     * @var string Article ID for a shipping
     */
    const ARTICLE_ID = 'Shipping';

    /**
     * @var string Avalara default taxcode for a voucher
     */
    const TAXCODE = 'FR010000';

    /**
     * build Line-model based on passed in lineData
     * @param float $price
     * @return LineItemModel
     */
    public function build($price)
    {
        $line = new LineItemModel();
        $line->number = self::ARTICLE_ID;
        $line->itemCode = self::ARTICLE_ID;
        $line->amount = $price;
        $line->quantity = 1;
        $line->description = self::ARTICLE_ID;
        $line->taxCode = self::TAXCODE;
        $line->discounted = false;
        $line->taxIncluded = false;

        return $line;
    }
}
