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
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Kernel;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

/**
 *
 * Factory to create \Avalara\LineItemModel
 *
 * @author Mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class LineFactory extends AbstractFactory
{
    private EntityRepositoryInterface $categoryRepository;

    private SalesChannelContext $context;

    /**
     * build Line-model based on passed in lineData
     *
     * @param LineItem $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @param bool $taxIncluded
     * @param EntityRepositoryInterface $categoryRepository
     * @param SalesChannelContext $context
     * @return LineItemModel|bool
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function build(
        LineItem $lineItem,
        AddressLocationInfo $deliveryAddress,
        bool $taxIncluded,
        EntityRepositoryInterface $categoryRepository,
        SalesChannelContext $context,
        bool $discounted
    )
    {
        if (self::isDiscount($lineItem)) {
            return false;
        }

        $this->categoryRepository = $categoryRepository;
        $this->context = $context;

        $price = $lineItem->getPrice()->getUnitPrice();
        $quantity = $lineItem->getPrice()->getQuantity();

        $line = new LineItemModel();
        $line->number = $lineItem->getPayloadValue('productNumber');
        $line->itemCode = $lineItem->getPayloadValue('productNumber');
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
     * @param LineItem $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @return AddressesModel|false
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getWarehouse(LineItem $lineItem, AddressLocationInfo $deliveryAddress)
    {
        $payload = $lineItem->getPayload();
        if (!array_key_exists('customFields', [$payload])) {
            return false;
        }
        $customFields = $payload['customFields'];

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
     * @param LineItem $lineItem
     * @return string|bool
     */
    private function getTaxCode(LineItem $lineItem): string
    {
        if (($taxCode = $this->getProductTaxCode($lineItem))
            || ($taxCode = $this->getCategoryTaxCode($lineItem))) {
            return $taxCode;
        }

        return false;
    }

    /**
     * @param LineItem $lineItem
     * @return string|bool
     */
    private function getProductTaxCode(LineItem $lineItem)
    {
        $customFields = $lineItem->getPayloadValue('customFields');

        if (!empty($customFields)
            && array_key_exists(Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE, $customFields)
            && !empty($customFields[Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE])
        ) {
            return $customFields[Form::CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE];
        }

        return false;
    }

    /**
     * @param LineItem $lineItem
     * @return string|bool
     */
    private function getCategoryTaxCode(LineItem $lineItem)
    {
        $categoryIds = $lineItem->getPayloadValue('categoryIds');
        if (!is_array($categoryIds)) {
            return false;
        }

        //Ids sorted from main to sub, but we need to take the first subcategory tax code
        $categoryIds = array_reverse($categoryIds);

        $searchResults = $this->categoryRepository->search(
            new Criteria($categoryIds),
            $this->context->getContext()
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
     * @param LineItem $lineItem
     * @return bool
     */
    public static function isDiscount(LineItem $lineItem): bool
    {
        if ($lineItem->getPayloadValue('discountId')) {
            return true;
        }
        return false;
    }
}
