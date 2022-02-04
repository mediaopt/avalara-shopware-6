<?php declare(strict_types=1);

namespace MoptAvalara6\Core\Checkout\Cart;

use MoptAvalara6\Bootstrap\Form;
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
use Symfony\Component\HttpFoundation\Session\Session;

class OverwritePriceProcessor implements CartProcessorInterface
{
    private QuantityPriceCalculator $calculator;

    private Session $session;

    private array $avalaraTaxes;

    public function __construct(QuantityPriceCalculator $calculator, Session $session) {
        $this->calculator = $calculator;
        $this->session = $session;
        $this->avalaraTaxes = $this->session->get(Form::SESSION_AVALARA_TAXES_TRANSFORMED);
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if ($this->avalaraTaxes) {
            $this->changeTaxes($toCalculate);
            $this->changeShippingCosts($toCalculate);
            $toCalculate->getShippingCosts();
        }
    }

    private function changeTaxes(Cart $toCalculate)
    {
        // get all product line items
        $products = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $productId = $product->getId();
            if (!array_key_exists($productId, $this->avalaraTaxes)) {
                continue;
            }

            $originalPrice = $product->getPrice();
            $avalaraTaxAmount = $this->avalaraTaxes[$productId]['tax'];
            $avalaraTaxRate = $this->avalaraTaxes[$productId]['rate'] * 100;

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

        if ($delivery === null) {
            return;
        }

        if ($this->avalaraTaxes['Shipping']['tax'] == 0) {
            return;
        }

        $shippingCosts = $delivery->getShippingCosts();

        $avalaraShippingTaxAmount = $this->avalaraTaxes['Shipping']['tax'];
        $avalaraShippingTaxRate = $this->avalaraTaxes['Shipping']['rate'];

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
