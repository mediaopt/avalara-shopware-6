<?php declare(strict_types=1);

namespace MoptAvalara6\Service;

use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Delivery\Struct\Delivery;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Rule\CartAmountRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\TaxProvider\AbstractTaxProvider;
use Shopware\Core\Checkout\Cart\TaxProvider\Struct\TaxProviderResult;
use Shopware\Core\Framework\App\Manifest\Xml\Tax\TaxProvider;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\Session;

class AvalaraTaxProvider extends AbstractTaxProvider
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $categoryRepository;
    private Session $session;
    private $avalaraTaxes;
    private Logger $logger;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository    $categoryRepository,
        Logger              $loggerMonolog
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->session = new SessionService();
        $this->categoryRepository = $categoryRepository;
        $this->logger = $loggerMonolog;
    }

    public function provide(Cart $cart, SalesChannelContext $context): TaxProviderResult
    {
        $defaultTaxRate = 19;

        $salesChannelId = $context->getSalesChannelId();
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $this->avalaraTaxes = $this->session->getValue(Form::SESSION_AVALARA_TAXES_TRANSFORMED, $adapter);

        if ($this->isTaxesUpdateNeeded()) {
            $service = $adapter->getService('GetTax');
            $this->avalaraTaxes = $service->getAvalaraTaxes($cart, $context, $this->session, $this->categoryRepository);
            $this->validateTaxes($adapter, $cart);
        }

        if ($this->avalaraTaxes
            && array_key_exists(Form::TAX_REQUEST_STATUS, $this->avalaraTaxes)
            && $this->avalaraTaxes[Form::TAX_REQUEST_STATUS] == Form::TAX_REQUEST_STATUS_SUCCESS) {

            //todo here calculate with avalara
            $lineItemTaxes = $this->changeTaxes($cart, $defaultTaxRate);
            $deliveryTaxes = $this->changeShippingCosts($cart, $defaultTaxRate);
            $promotionsTaxes = $this->changePromotionsTaxes($cart);
            /*$this->changeShippingCosts($cart);
            $this->changePromotionsTaxes($cart);
            $cart->getShippingCosts();*/

        } else {
            //todo here calculate with default
            $lineItemTaxes = $this->changeTaxes($cart, $defaultTaxRate);
            $deliveryTaxes = $this->changeShippingCosts($cart);
            $promotionsTaxes = $this->changePromotionsTaxes($cart);
        }

        // you could do the same for deliveries
        // $deliveryTaxes = []; // use the id of the delivery position as keys, if you want to transmit delivery taxes

        // foreach ($cart->getDeliveries() as $delivery) {
        //     foreach ($delivery->getPositions() as $position) {
        //         $deliveryTaxes[$delivery->getId()] = new CalculatedTaxCollection(...);
        //         ...
        //     }
        // }

        // If you call a tax provider, you will probably get calculated tax sums for the whole cart
        // Use the cartPriceTaxes to let Shopware show the correct sums in the checkout
        // If omitted, Shopware will try to calculate the tax sums itself
        // $cartPriceTaxes = [];

        return new TaxProviderResult(
            $lineItemTaxes,
            $deliveryTaxes
        );
    }

    /**
     * @param Cart $cart
     * @param float $defaultTaxRate
     * @return CalculatedTaxCollection[]
     */
    private function changeTaxes(Cart $cart, float $defaultTaxRate): array
    {
        $lineItemTaxes = [];

        $products = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($products as $product) {
            $price = $product->getPrice()->getTotalPrice();
            $productNumber = $product->getPayloadValue('productNumber');
            $productId = $product->getUniqueIdentifier();

            /*if (array_key_exists($productNumber, $this->avalaraTaxes)) {
                $tax = $this->avalaraTaxes[$productNumber];
            } else {
                $tax = [
                    'rate' =>$defaultTaxRate,
                    'tax' =>$price * $defaultTaxRate / 100
                ];
            }*/

            $lineItemTaxes[$productId] = $this->itemPriceCalculator($price, $productNumber);
        }


        $promotions = $cart->getLineItems()->filterType(LineItem::PROMOTION_LINE_ITEM_TYPE);

        foreach ($promotions as $promotion) {
            $promotionId = $promotion->getPayloadValue('promotionId');
            debug('###########');
            debug($promotionId);
            debug($promotion->getUniqueIdentifier());
            debug('###########');
            if (!array_key_exists($promotionId, $this->avalaraTaxes)) {
                continue;
            }
            $originalPrice = $promotion->getPrice()->getTotalPrice();
            $avalaraPromotionCalculated = $this->itemPriceCalculator($originalPrice, $promotionId);
            $lineItemTaxes[$promotion->getUniqueIdentifier()] = $avalaraPromotionCalculated;
        }


        return $lineItemTaxes;
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function changeShippingCosts(Cart $cart): array
    {
        /** @var Delivery $delivery */
        $delivery = $cart->getDeliveries()->first();

        if ($delivery === null) {
            return [];
        }
        $originalPrice = $delivery->getShippingCosts()->getTotalPrice();

        return [$this->itemPriceCalculator($originalPrice, 'Shipping')];
    }

    /**
     * @param Cart $cart
     * @return array
     */
    private function changePromotionsTaxes(Cart $cart): array
    {
        $promotionTaxes = [];

        $promotions = $cart->getLineItems()->filterType(LineItem::PROMOTION_LINE_ITEM_TYPE);

        foreach ($promotions as $promotion) {
            $promotionId = $promotion->getPayloadValue('promotionId');
            if (!array_key_exists($promotionId, $this->avalaraTaxes)) {
                continue;
            }
            $originalPrice = $promotion->getPrice()->getTotalPrice();
            $avalaraPromotionCalculated = $this->itemPriceCalculator($originalPrice, $promotionId);
            $promotionTaxes[$promotion->getUniqueIdentifier()] = $avalaraPromotionCalculated;
        }
        return $promotionTaxes;
    }

    /**
     * @param float $price
     * @param string $productNumber
     * @return CalculatedTaxCollection
     */
    private function itemPriceCalculator(float $price, string $productNumber): CalculatedTaxCollection
    {
        $avalaraCalculatedTax = new CalculatedTax(
            $this->avalaraTaxes[$productNumber]['tax'],
            $this->avalaraTaxes[$productNumber]['rate'],
            $price
        );

        $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
        $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

        return $avalaraCalculatedTaxCollection;
    }

    /**
     * @return bool
     */
    private function isTaxesUpdateNeeded()
    {
        if (!array_key_exists('REQUEST_URI', $_SERVER)) {
            return true;
        }

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

    /**
     * @param AvalaraSDKAdapter $adapter
     * @param Cart $cart
     * @return void
     */
    private function validateTaxes(AvalaraSDKAdapter $adapter, Cart $cart)
    {
        if ($adapter->getPluginConfig(Form::BLOCK_CART_ON_ERROR_FIELD)) {
            $products = $cart->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);
            $status = Form::TAX_REQUEST_STATUS_FAILED;
            if (is_array($this->avalaraTaxes) && array_key_exists(Form::TAX_REQUEST_STATUS, $this->avalaraTaxes)) {
                $status = $this->avalaraTaxes[Form::TAX_REQUEST_STATUS];
            }
            foreach ($products as $product) {
                $product->setPayloadValue(Form::TAX_REQUEST_STATUS, $status);
            }
        }
    }

    /**
     * @param InstallContext $installContext
     * @param EntityRepository $ruleRepo
     * @param EntityRepository $taxRepo
     * @return void
     */
    public static function createProvider(InstallContext $installContext, EntityRepository $ruleRepo, EntityRepository $taxRepo): void
    {
        // create a rule, which will be used to determine the availability of your tax provider
        // do not rely on specific rules to be always present
        $ruleRepo->create([
            [
                'name' => 'Cart > 0',
                'priority' => 0,
                'conditions' => [
                    [
                        'type' => CartAmountRule::RULE_NAME,
                        'operator' => CartAmountRule::OPERATOR_GTE,
                        'value' => [
                            'operator' => CartAmountRule::OPERATOR_GTE,
                            'amount' => 0,
                        ]
                    ],
                ],
            ],
        ], $installContext->getContext());

        $criteria = new Criteria();
        $criteria->addFilter(
            new EqualsFilter('name', 'Cart > 0')
        );

        $ruleId = $ruleRepo->searchIds($criteria, $installContext->getContext())->firstId();

        $taxRepo->create([
            [
                'id' => Uuid::randomHex(),
                'identifier' => AvalaraTaxProvider::class,
                'name' => 'Avalara Tax',
                'priority' => 1,
                'active' => false,
                'availabilityRuleId' => $ruleId,
            ],
        ], $installContext->getContext());
    }
}