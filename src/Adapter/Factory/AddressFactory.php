<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressLocationInfo;
use MoptAvalara6\Bootstrap\Form;
use Shopware\Core\Checkout\Cart\Cart;

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
     * @param Cart $cart
     * @return AddressLocationInfo
     */
    public function buildDeliveryAddress(Cart $cart)
    {
        $customerAddress = $cart->getDeliveries()->getAddresses()->first();
        $customerCountry = $cart->getDeliveries()->getAddresses()->getCountries()->first();
        $customerState = $cart->getDeliveries()->getAddresses()->getCountryStates()->first();

        $address = new AddressLocationInfo();
        $address->city = $customerAddress->getCity();
        $address->country = $customerCountry->getIso3();
        $address->line1 = $customerAddress->getStreet();
        $address->postalCode = $customerAddress->getZipcode();
        if ($region = $customerState->getName()) {
            $address->region = $region;
        }

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
