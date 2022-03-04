<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\LineItemModel;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Shipping\ShippingMethodEntity;

/**
 * @author Mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Adapter\Factory
 */
class ShippingFactory extends AbstractFactory
{
    /**
     * @var string Article ID for a shipping
     */
    const ARTICLE_ID = 'Shipping';

    /**
     * @var string Avalara default taxcode for a shipping
     */
    const TAXCODE = 'FR020100';

    /**
     * build Line-model based on passed in lineData
     * @param ShippingMethodEntity $shippingMethod
     * @param float $price
     * @return LineItemModel
     */
    public function build(ShippingMethodEntity $shippingMethod, float $price)
    {
        $customFields = $shippingMethod->getCustomFields();
        if (is_array($customFields) && array_key_exists(Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE, $customFields)) {
            $taxCode = $customFields[Form::CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE];
        } else {
            $taxCode = self::TAXCODE;
        }

        $line = new LineItemModel();
        $line->number = self::ARTICLE_ID;
        $line->itemCode = self::ARTICLE_ID;
        $line->amount = $price;
        $line->quantity = 1;
        $line->description = $shippingMethod->getName();
        $line->taxCode = $taxCode;
        $line->discounted = false;
        $line->taxIncluded = false;

        return $line;
    }
}
