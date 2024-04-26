<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressesModel;
use Avalara\AddressLocationInfo;
use Avalara\LineItemModel;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Kernel;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

/**
 *
 * Factory to create \Avalara\LineItemModel
 *
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class LineFactory extends AbstractFactory
{
    private EntityRepository $categoryRepository;

    private Context $context;

    /**
     * build Line-model based on passed in lineData
     *
     * @param LineItem|OrderLineItemEntity $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @param bool $taxIncluded
     * @param EntityRepository $categoryRepository
     * @param Context $context
     * @return LineItemModel|bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function build(
        LineItem|OrderLineItemEntity $lineItem,
        AddressLocationInfo $deliveryAddress,
        bool $taxIncluded,
        EntityRepository $categoryRepository,
        Context $context,
        bool $discounted
    )
    {
        if (self::isDiscount($lineItem)) {
            return false;
        }

        $this->categoryRepository = $categoryRepository;
        $this->context = $context;

        [$price, $quantity] = self::getLineItemDetails($lineItem);

        $line = new LineItemModel();
        $line->number = self::getPayloadValue($lineItem, 'productNumber');
        $line->itemCode = self::getPayloadValue($lineItem, 'productNumber');
        $line->amount = $price * $quantity;
        $line->quantity = $quantity;
        $line->description = $lineItem->getLabel();
        $line->discounted = $discounted;
        $line->taxIncluded = $taxIncluded;

        if ($taxCode = $this->getTaxCode($lineItem)) {
            $line->taxCode = $taxCode;
        }

        if ($warehouse = $this->getWarehouse($lineItem, $deliveryAddress)) {
            $line->addresses = $warehouse;
        }

        // todo discounted

        return $line;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @return AddressesModel|false
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getWarehouse(LineItem|OrderLineItemEntity $lineItem, AddressLocationInfo $deliveryAddress)
    {
        $customFields = self::getPayloadValue($lineItem, 'customFields');
        if (is_null($customFields)) {
            return false;
        }

        if (!array_key_exists(Form::CUSTOM_FIELD_PRODUCT_WAREHOUSE, $customFields)) {
            return false;
        }

        $warehouseId = $customFields[Form::CUSTOM_FIELD_PRODUCT_WAREHOUSE];
        $connection = Kernel::getConnection();

        $sql = "SELECT * FROM vlck_warehouse WHERE id = UNHEX('$warehouseId')";
        $warehouse = $connection->executeQuery($sql)->fetchAssociative();

        $addressesModel = new AddressesModel();
        $addressFactory = new AddressFactory($this->adapter);
        $addressesModel->shipFrom = $addressFactory->buildAddressFromArray($warehouse);
        $addressesModel->shipTo = $deliveryAddress;

        return $addressesModel;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @return string|bool
     */
    private function getTaxCode(LineItem|OrderLineItemEntity $lineItem): string
    {
        if (($taxCode = $this->getProductTaxCode($lineItem))
            || ($taxCode = $this->getCategoryTaxCode($lineItem))) {
            return $taxCode;
        }

        return false;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @return string|bool
     */
    private function getProductTaxCode(LineItem|OrderLineItemEntity $lineItem)
    {
        $customFields = self::getPayloadValue($lineItem, 'customFields');

        if (!empty($customFields)
            && array_key_exists(Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE, $customFields)
            && !empty($customFields[Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE])
        ) {
            return $customFields[Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE];
        }

        return false;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @return string|bool
     */
    private function getCategoryTaxCode(LineItem|OrderLineItemEntity $lineItem)
    {
        $categoryIds = self::getPayloadValue($lineItem,'categoryIds');
        if (!is_array($categoryIds)) {
            return false;
        }

        //Ids sorted from main to sub, but we need to take the first subcategory tax code
        $categoryIds = array_reverse($categoryIds);

        $searchResults = $this->categoryRepository->search(
            new Criteria($categoryIds),
            $this->context
        );

        /**
         * @var CategoryEntity $category
         */
        foreach ($searchResults as $category) {
            $customFields = $category->getCustomFields();
            if (!empty($customFields)
                && array_key_exists(Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE, $customFields)
                && !empty($customFields[Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE])
            ) {
                return $customFields[Form::CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE];
            }
        }

        return false;
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @return bool
     */
    public static function isDiscount(LineItem|OrderLineItemEntity $lineItem): bool
    {
        if (self::getPayloadValue($lineItem, 'discountId')) {
            return true;
        }
        return false;
    }

    /**
     * @param LineItem|OrderLineItemEntity $item
     * @param string $key
     * @return ?mixed
     */
    private static function getPayloadValue(LineItem|OrderLineItemEntity $item, string $key)
    {
        $payload = $item->getPayload();
        if (!array_key_exists($key, $payload)) {
            return null;
        }

        return $payload[$key];
    }

    /**
     * @param LineItem|OrderLineItemEntity $lineItem
     * @return array
     */
    public static function getLineItemDetails(LineItem|OrderLineItemEntity $lineItem): array
    {
        if (is_null($lineItem->getPrice())) {
            return [0, 0];

        }

        return [$lineItem->getPrice()->getUnitPrice(), $lineItem->getPrice()->getQuantity()];
    }
}
