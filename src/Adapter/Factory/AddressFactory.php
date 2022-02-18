<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressLocationInfo;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;

/**
 *
 *
 * @author derksen mediaopt GmbH
 *
 * @package MoptAvalara6\Adapter\Factory
 */
class AddressFactory extends AbstractFactory
{
    /**
     * @var string
     */
    const COUNTRY_CODE__USA = 'USA';

    /**
     * @var string
     */
    const COUNTRY_CODE__CAN = 'CAN';

    /**
     * build Address-model based on delivery address
     *
     * @param CustomerAddressEntity $cart
     * @return AddressLocationInfo
     */
    public function buildDeliveryAddress(CustomerAddressEntity $customerAddress)
    {
        $address = new AddressLocationInfo();
        $address->line1 = $customerAddress->getStreet();
        $address->line2 = $customerAddress->getAdditionalAddressLine1();
        $address->line3 = $customerAddress->getAdditionalAddressLine2();
        $address->city = $customerAddress->getCity();
        $address->postalCode = $customerAddress->getZipcode();
        $address->region = $customerAddress->getCountryState()->getName();
        $address->country = $customerAddress->getCountry()->getIso3();

        return $address;
    }

    /**
     * Origination (ship-from) address
     *
     * @return AddressLocationInfo
     */
    public function buildOriginAddress()
    {
        $address = new AddressLocationInfo();
        $address->line1 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_1_FIELD);
        $address->line2 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_2_FIELD);
        $address->line3 = $this->getPluginConfig(Form::ORIGIN_ADDRESS_LINE_3_FIELD);
        $address->city = $this->getPluginConfig(Form::ORIGIN_CITY_FIELD);
        $address->postalCode = $this->getPluginConfig(Form::ORIGIN_POSTAL_CODE_FIELD);
        $address->region = $this->getPluginConfig(Form::ORIGIN_REGION_FIELD);
        $address->country = $this->getPluginConfig(Form::ORIGIN_COUNTRY_FIELD);

        //todo: should we fix country code/region, like in shopware5?

        return $address;
    }

    /**
     * @param array $warehouse
     * @return AddressLocationInfo
     */
    public function buildWarehouseAddress(array $warehouse)
    {
        $address = new AddressLocationInfo();
        $address->line1 = $warehouse['address_line_1'];
        $address->line2 = $warehouse['address_line_2'];
        $address->line3 = $warehouse['address_line_3'];
        $address->city = $warehouse['city'];
        $address->postalCode = $warehouse['postcode'];
        $address->region = $warehouse['region'];
        $address->country = $warehouse['country'];

        return $address;
    }

    /**
     * @param string $country
     * @return bool
     */
    public function checkCountryRestriction(string $country): bool
    {
        $countriesForDelivery = $this->getPluginConfig(Form::TAX_COUNTRY_RESTRICTION);

        switch ($countriesForDelivery) {
            case Form::DELIVERY_COUNTRY_NO_VALIDATION:
                return false;
            case Form::DELIVERY_COUNTRY_USA:
                if ($country === AddressFactory::COUNTRY_CODE__USA) {
                    return true;
                }
                break;
            case Form::DELIVERY_COUNTRY_CANADA:
                if ($country === AddressFactory::COUNTRY_CODE__CAN) {
                    return true;
                }
                break;
            case Form::DELIVERY_COUNTRY_USA_AND_CANADA:
                $usaAndCanada = [
                    AddressFactory::COUNTRY_CODE__CAN,
                    AddressFactory::COUNTRY_CODE__USA
                ];

                if (in_array($country, $usaAndCanada, true)) {
                    return true;
                }
                break;
        }

        return false;
    }
}
