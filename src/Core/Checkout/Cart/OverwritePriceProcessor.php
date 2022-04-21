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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Monolog\Logger;

class OverwritePriceProcessor implements CartProcessorInterface
{
    private QuantityPriceCalculator $calculator;

    private SystemConfigService $systemConfigService;

    private EntityRepositoryInterface $categoryRepository;

    private Session $session;

    private $avalaraTaxes;

    private Logger $logger;

    public function __construct(
        QuantityPriceCalculator $calculator,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $categoryRepository,
        Logger $loggerMonolog,
        Session $session
    ) {
        $this->calculator = $calculator;
        $this->systemConfigService = $systemConfigService;
        $this->session = $session;
        $this->categoryRepository = $categoryRepository;
        $this->logger = $loggerMonolog;
        $this->avalaraTaxes = $this->session->get(Form::SESSION_AVALARA_TAXES_TRANSFORMED);
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if ($this->isTaxesUpdateNeeded()) {
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
            $service = $adapter->getService('GetTax');
            $this->avalaraTaxes = $service->getAvalaraTaxes($original, $context, $this->session, $this->categoryRepository);
        }

        if ($this->avalaraTaxes) {
            $this->changeTaxes($toCalculate);
            $this->changeShippingCosts($toCalculate);
            $toCalculate->getShippingCosts();
        }
    }

    /**
     * @param Cart $toCalculate
     * @return void
     */
    private function changeTaxes(Cart $toCalculate)
    {
        $products = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $productNumber = $product->getPayloadValue('productNumber');
            if (!array_key_exists($productNumber, $this->avalaraTaxes)) {
                continue;
            }

            $originalPrice = $product->getPrice();

            $avalaraProductPriceCalculated = $this->itemPriceCalculator($originalPrice, $productNumber);

            $product->setPrice($avalaraProductPriceCalculated);
        }
    }

    /**
     * @param Cart $toCalculate
     * @return void
     */
    private function changeShippingCosts(Cart $toCalculate)
    {
        $delivery = $toCalculate->getDeliveries()->first();

        if ($delivery === null) {
            return;
        }

        $originalPrice = $delivery->getShippingCosts();

        $avalaraShippingCalculated = $this->itemPriceCalculator($originalPrice, 'Shipping');

        $delivery->setShippingCosts($avalaraShippingCalculated);
    }

    /**
     * @param CalculatedPrice $price
     * @param string $productNumber
     * @return CalculatedPrice
     */
    private function itemPriceCalculator(CalculatedPrice $price, string $productNumber): CalculatedPrice
    {
        $avalaraCalculatedTax = new CalculatedTax(
            $this->avalaraTaxes[$productNumber]['tax'],
            $this->avalaraTaxes[$productNumber]['rate'],
            $price->getTotalPrice()
        );

        $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
        $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

        return new CalculatedPrice(
            $price->getUnitPrice(),
            $price->getTotalPrice(),
            $avalaraCalculatedTaxCollection,
            $price->getTaxRules(),
            $price->getQuantity(),
            $price->getReferencePrice(),
            $price->getListPrice()
        );
    }

    /**
     * @return bool
     */
    private function isTaxesUpdateNeeded()
    {
        $pagesForUpdate = [
            'checkout/cart',
            'checkout/confirm',
            'checkout/order'
        ];

        $currentPage = $_SERVER['REQUEST_URI'];

        foreach ($pagesForUpdate as $page) {
            if (strripos($currentPage, $page)) {
                return true;
            }
        }

        return false;
    }
}
