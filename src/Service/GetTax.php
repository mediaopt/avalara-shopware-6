<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\CreateTransactionModel;
use Monolog\Logger;
use MoptAvalara6\Adapter\AdapterInterface;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Kernel;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\HttpFoundation\Session\Session;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;

/**
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Service
 */
class GetTax extends AbstractService
{
    /**
     * @param AdapterInterface $adapter
     * @param Logger $logger
     */
    public function __construct(AdapterInterface $adapter, Logger $logger)
    {
        parent::__construct($adapter, $logger);
    }

    /**
     * @param Cart $cart
     * @param SalesChannelContext $context
     * @param Session $session
     * @return array|null
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function getAvalaraTaxes(
        Cart $cart,
        SalesChannelContext $context,
        Session $session,
        EntityRepositoryInterface $entityRepository
    )
    {
        $customer = $context->getCustomer();
        if (!$customer) {
            return $session->get(Form::SESSION_AVALARA_TAXES_TRANSFORMED);
        }

        $customerId = $customer->customerNumber;
        $taxIncluded = $this->isTaxIncluded($customer, $session);
        $currencyIso = $context->getCurrency()->getIsoCode();
        $avalaraRequest = $this->prepareAvalaraRequest(
            $cart,
            $customerId,
            $currencyIso,
            $taxIncluded,
            $session,
            $entityRepository,
            $context
        );
        if (!$avalaraRequest) {
            return null;
        }

        $avalaraRequestKey = md5(json_encode($avalaraRequest));
        $sessionAvalaraRequestKey = $session->get(Form::SESSION_AVALARA_MODEL_KEY);
        if ($avalaraRequestKey != $sessionAvalaraRequestKey) {
            $session->set(Form::SESSION_AVALARA_MODEL, serialize($avalaraRequest));
            $session->set(Form::SESSION_AVALARA_MODEL_KEY, $avalaraRequestKey);
            return $this->makeAvalaraCall($avalaraRequest, $session, $cart);
        }

        return $session->get(Form::SESSION_AVALARA_TAXES_TRANSFORMED);
    }

    /**
     * @param Cart $cart
     * @param string $customerId
     * @param string $currencyIso
     * @param bool $taxIncluded
     * @param Session $session
     * @param EntityRepositoryInterface $categoryRepository
     * @param SalesChannelContext $context
     * @return CreateTransactionModel|bool
     */
    private function prepareAvalaraRequest(
        Cart $cart,
        string $customerId,
        string $currencyIso,
        bool $taxIncluded,
        Session $session,
        EntityRepositoryInterface $categoryRepository,
        SalesChannelContext $context
    )
    {
        $shippingCountry = $cart->getDeliveries()->getAddresses()->getCountries()->first();
        if (is_null($shippingCountry)) {
            return false;
        }
        $shippingCountryIso3 = $shippingCountry->getIso3();

        if (!$this->adapter->getFactory('AddressFactory')->checkCountryRestriction($shippingCountryIso3)) {
            $session->set(Form::SESSION_AVALARA_TAXES, null);
            $session->set(Form::SESSION_AVALARA_TAXES_TRANSFORMED, null);
            $session->set(Form::SESSION_AVALARA_MODEL, null);
            $session->set(Form::SESSION_AVALARA_MODEL_KEY, null);
            return false;
        }

        return $this->adapter->getFactory('OrderTransactionModelFactory')
            ->build($cart, $customerId, $currencyIso, $taxIncluded, $categoryRepository, $context);
    }

    /**
     * @param CreateTransactionModel $avalaraRequest
     * @param Session $session
     * @param Cart $cart
     * @return array
     */
    private function makeAvalaraCall(CreateTransactionModel $avalaraRequest, Session $session, Cart $cart)
    {
        $response = $this->calculate($avalaraRequest);

        $transformedTaxes = $this->transformResponse($response, $cart);

        $session->set(Form::SESSION_AVALARA_TAXES, $response);
        $session->set(Form::SESSION_AVALARA_TAXES_TRANSFORMED, $transformedTaxes);

        return $transformedTaxes;
    }

    /**
     * @param mixed $response
     * @param Cart $cart
     * @return array
     */
    private function transformResponse($response, Cart $cart): array
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

        $promotions = $cart->getLineItems()->filterType(LineItem::PROMOTION_LINE_ITEM_TYPE);

        //promotions taxes are included in item price and should be 0
        foreach ($promotions as $promotion) {
            $promotionId = $promotion->getPayloadValue('promotionId');
            $transformedTax[$promotionId] = [
                'tax' => 0,
                'rate' => 0,
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
    private function isTaxIncluded(CustomerEntity $customer, $session): bool
    {
        $isTaxIncluded = $session->get(Form::SESSION_AVALARA_IS_GROSS_PRICE);

        if (is_null($isTaxIncluded)) {
            $groupId = $customer->getGroupId();
            $connection = Kernel::getConnection();

            $sql = "SELECT display_gross FROM customer_group WHERE id = UNHEX('$groupId')";

            $isTaxIncluded = $connection->executeQuery($sql)->fetchAssociative();

            $isTaxIncluded = (bool) $isTaxIncluded['display_gross'];
            $session->set(Form::SESSION_AVALARA_IS_GROSS_PRICE, $isTaxIncluded);
        }

        return $isTaxIncluded;
    }

    /**
     * @param CreateTransactionModel $model
     * @return mixed
     */
    public function calculate(CreateTransactionModel $model)
    {
        $client = $this->getAdapter()->getAvaTaxClient();
        $model->date = date(DATE_W3C);
        try {
            $this->log('Avalara request', 0, $model);
            $response = $client->createTransaction(null, $model);
            $this->log('Avalara response', 0, $response);
            return $response;
        } catch (\Exception $e) {
            $this->log($e->getMessage(), Logger::ERROR);
        }

        return false;
    }
}
