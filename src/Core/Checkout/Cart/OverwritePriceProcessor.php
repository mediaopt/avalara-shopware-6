<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class OverwritePriceProcessor implements CartProcessorInterface
{
    private QuantityPriceCalculator $calculator;

    public function __construct(QuantityPriceCalculator $calculator) {
        $this->calculator = $calculator;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $this->changeTaxes($toCalculate);
        $this->changeShippingCosts($toCalculate);


        $toCalculate->getShippingCosts();

    }

    private function changeTaxes(Cart $toCalculate)
    {
        // get all product line items
        $products = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $originalPrice = $product->getPrice();
            //@TODO: Find avalara product results and replace it here
            $avalaraTaxAmount = 11.11;
            $avalaraTaxRate = 2.22;

            $avalaraCalculatedTax = new CalculatedTax(
                $avalaraTaxAmount,
                $avalaraTaxRate,
                //@TODO: check if we have to replace it with $avalaraPriceForProduct
                $product->getPrice()->getTotalPrice()
            );

            $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
            $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

            //@TODO: only works if we are calculating with netto prices and if avalaraTaxAmount is multiplied by quantity
            $avalaraPriceForProduct = $product->getPrice()->getTotalPrice() + $avalaraTaxAmount;

            $avalaraProductPriceCalculated = new CalculatedPrice(
                $originalPrice->getUnitPrice(),
                $avalaraPriceForProduct,
                $avalaraCalculatedTaxCollection,
                $originalPrice->getTaxRules(),
                $originalPrice->getQuantity(),
                $originalPrice->getReferencePrice(),
                $originalPrice->getListPrice()
            );

            $product->setPrice($avalaraProductPriceCalculated);
        }
    }

    private function changeShippingCosts(Cart $toCalculate)
    {
        //@TODO: check if we are using always the first delivery for request and display
        $delivery = $toCalculate->getDeliveries()->first();
        $shippingCosts = $delivery->getShippingCosts();

        $avalaraShippingTaxAmount = 4.44;
        $avalaraShippingTaxRate = 5.55;

        $avalaraCalculatedTax = new CalculatedTax(
            $avalaraShippingTaxAmount,
            $avalaraShippingTaxRate,
            //@TODO: check if we have to replace it with $avalaraPriceForShipping
            $shippingCosts->getTotalPrice()
        );

        $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
        $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

        $avalaraPriceForShipping = $shippingCosts->getTotalPrice() + $avalaraShippingTaxAmount;
        $avalaraShippingCalculated = new CalculatedPrice(
            $shippingCosts->getUnitPrice(),
            $avalaraPriceForShipping,
            $avalaraCalculatedTaxCollection,
            $shippingCosts->getTaxRules(),
            $shippingCosts->getQuantity(),
            $shippingCosts->getReferencePrice(),
            $shippingCosts->getListPrice()
        );
        $delivery->setShippingCosts($avalaraShippingCalculated);
    }
}
