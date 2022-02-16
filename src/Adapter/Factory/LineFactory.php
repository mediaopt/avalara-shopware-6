<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressesModel;
use Avalara\AddressLocationInfo;
use Avalara\LineItemModel;
use Shopware\Core\Kernel;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use MoptAvalara6\Bootstrap\Form;

/**
 *
 * Factory to create \Avalara\LineItemModel
 *
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Adapter\Factory
 */
class LineFactory extends AbstractFactory
{
    /**
     * build Line-model based on passed in lineData
     *
     * @param LineItem $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @return LineItemModel
     */
    public function build(LineItem $lineItem, AddressLocationInfo $deliveryAddress)
    {
        $price = $lineItem->getPrice()->getUnitPrice();
        $quantity = $lineItem->getPrice()->getQuantity();

        $line = new LineItemModel();
        $line->number = $lineItem->getPayloadValue('productNumber');
        $line->itemCode = $lineItem->getPayloadValue('productNumber');
        $line->amount = $price * $quantity;
        $line->quantity = $quantity;
        $line->description = $lineItem->getLabel();
        $line->taxIncluded = false;

        if ($warehouse = $this->getWarehouse($lineItem, $deliveryAddress)) {
            $line->addresses = $warehouse;
        }

        // todo discounted

        return $line;
    }

    /**
     * @param LineItem $lineItem
     * @param AddressLocationInfo $deliveryAddress
     * @return mixed
     */
    private function getWarehouse(LineItem $lineItem, AddressLocationInfo $deliveryAddress)
    {
        $payload = $lineItem->getPayload();
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
        $addressesModel->shipFrom = $addressFactory->buildWarehouseAddress($warehouse);
        $addressesModel->shipTo = $deliveryAddress;

        return $addressesModel;
    }
}
