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
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Kernel;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\Session;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Monolog\Logger;

class OverwritePriceProcessor implements CartProcessorInterface
{
    private QuantityPriceCalculator $calculator;

    private SystemConfigService $systemConfigService;

    private Session $session;

    private $avalaraTaxes;

    private Logger $logger;

    public function __construct(
        QuantityPriceCalculator $calculator,
        SystemConfigService $systemConfigService,
        Logger $loggerMonolog,
        Session $session
    ) {
        $this->calculator = $calculator;
        $this->systemConfigService = $systemConfigService;
        $this->session = $session;
        $this->logger = $loggerMonolog;
        $this->avalaraTaxes = $this->session->get(Form::SESSION_AVALARA_TAXES_TRANSFORMED);
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if ($this->isTaxesUpdateNeeded()) {
           $this->avalaraTaxes = $this->getAvalaraTaxes($original, $context);
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
            $avalaraTaxAmount = $this->avalaraTaxes[$productNumber]['tax'];
            $avalaraTaxRate = $this->avalaraTaxes[$productNumber]['rate'];

            $avalaraCalculatedTax = new CalculatedTax(
                $avalaraTaxAmount,
                $avalaraTaxRate,
                $product->getPrice()->getTotalPrice()
            );

            $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
            $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

            $avalaraPriceForProduct = $product->getPrice()->getTotalPrice();

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

        $shippingCosts = $delivery->getShippingCosts();

        $avalaraShippingTaxAmount = $this->avalaraTaxes['Shipping']['tax'];
        $avalaraShippingTaxRate = $this->avalaraTaxes['Shipping']['rate'];

        $avalaraCalculatedTax = new CalculatedTax(
            $avalaraShippingTaxAmount,
            $avalaraShippingTaxRate,
            $shippingCosts->getTotalPrice()
        );

        $avalaraCalculatedTaxCollection = new CalculatedTaxCollection();
        $avalaraCalculatedTaxCollection->add($avalaraCalculatedTax);

        $avalaraPriceForShipping = $shippingCosts->getTotalPrice();
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

    /**
     * @return bool
     */
    private function isTaxesUpdateNeeded()
    {
        $pagesForUpdate = [
            'checkout/cart',
            'checkout/confirm',
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
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @return array|mixed
     */
    private function getAvalaraTaxes(Cart $cart, SalesChannelContext $context)
    {
        $customer = $context->getCustomer();
        if ($customer) {
            $customerId = $customer->getId();
            $taxIncluded = $this->isTaxIncluded($customer);
            $currencyIso = $context->getCurrency()->getIsoCode();
            $avalaraRequest = $this->prepareAvalaraRequest($cart, $customerId, $currencyIso, $taxIncluded);
            if ($avalaraRequest) {
                $avalaraRequestKey = md5(json_encode($avalaraRequest));
                $sessionAvalaraRequestKey = $this->session->get(Form::SESSION_AVALARA_MODEL_KEY);
                if ($avalaraRequestKey != $sessionAvalaraRequestKey) {
                    $this->session->set(Form::SESSION_AVALARA_MODEL, serialize($avalaraRequest));
                    $this->session->set(Form::SESSION_AVALARA_MODEL_KEY, $avalaraRequestKey);
                    $this->avalaraTaxes = $this->makeAvalaraCall($avalaraRequest);
                }
            }
        }
        return $this->avalaraTaxes;
    }

    /**
     * @param Cart $cart
     * @param string $customerId
     * @param string $currencyIso
     * @param bool $taxIncluded
     * @return mixed
     */
    private function prepareAvalaraRequest(Cart $cart, string $customerId, string $currencyIso, bool $taxIncluded)
    {
        $shippingCountry = $cart->getDeliveries()->getAddresses()->getCountries()->first();
        if (is_null($shippingCountry)) {
            return false;
        }
        $shippingCountryIso3 = $shippingCountry->getIso3();

        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        if (!$adapter->getFactory('AddressFactory')->checkCountryRestriction($shippingCountryIso3)) {
            return false;
        }

        return $adapter->getFactory('OrderTransactionModelFactory')->build($cart, $customerId, $currencyIso, $taxIncluded);
    }

    /**
     * @param $avalaraRequest
     * @return array
     */
    private function makeAvalaraCall($avalaraRequest)
    {
        $adapter = new AvalaraSDKAdapter($this->systemConfigService, $this->logger);
        $service = $adapter->getService('GetTax');
        $response = $service->calculate($avalaraRequest);

        $transformedTaxes = $this->transformResponse($response);

        $this->session->set(Form::SESSION_AVALARA_TAXES, $response);
        $this->session->set(Form::SESSION_AVALARA_TAXES_TRANSFORMED, $transformedTaxes);

        return $transformedTaxes;
    }

    /**
     * @param mixed $response
     * @return array
     */
    private function transformResponse($response): array
    {
        $transformedTax = [];
        if (!is_object($response)) {
            return $transformedTax;
        }

        if (is_null($response->lines)) {
            return $transformedTax;
        }

        foreach ($response->lines as $line) {
            $rate = 0;
            foreach ($line->details as $detail) {
                $rate += $detail->rate;
            }
            $transformedTax[$line->itemCode] = [
                'tax' => $line->tax,
                'rate' => $rate * 100,
            ];
        }

        return $transformedTax;
    }

    /**
     * @param CustomerEntity $customer
     * @return bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function isTaxIncluded(CustomerEntity $customer): bool
    {
        $isTaxIncluded = $this->session->get(Form::SESSION_AVALARA_IS_GROSS_PRICE);

        if (is_null($isTaxIncluded)) {
            $groupId = $customer->getGroupId();
            $connection = Kernel::getConnection();

            $sql = "SELECT display_gross FROM customer_group WHERE id = UNHEX('$groupId')";

            $isTaxIncluded = $connection->executeQuery($sql)->fetchAssociative();

            $isTaxIncluded = (bool) $isTaxIncluded['display_gross'];
            $this->session->set(Form::SESSION_AVALARA_IS_GROSS_PRICE, $isTaxIncluded);
        }

        return $isTaxIncluded;
    }
}
