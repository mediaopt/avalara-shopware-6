<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Adapter\Factory;

use Avalara\AddressLocationInfo;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\Service\ValidateAddress;
use Shopware\Core\Checkout\Customer\Aggregate\CustomerAddress\CustomerAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Kernel;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 *
 *
 * @author Mediaopt GmbH
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
     * @param OrderAddressEntity|CustomerAddressEntity $cart
     * @return AddressLocationInfo
     */
    public function buildDeliveryAddress(OrderAddressEntity|CustomerAddressEntity $customerAddress)
    {
        $address = new AddressLocationInfo();
        $address->line1 = $customerAddress->getStreet();
        $address->line2 = $customerAddress->getAdditionalAddressLine1();
        $address->line3 = $customerAddress->getAdditionalAddressLine2();
        $address->city = $customerAddress->getCity();
        $address->postalCode = $customerAddress->getZipcode();
        $address->country = $customerAddress->getCountry()->getIso3();

        $customerState = $customerAddress->getCountryState();
        if (!is_null($customerState)) {
            if ($region = $customerState->getName()) {
                $address->region = $region;
            }
        }

        return $address;
    }

    /**
     * build Address-model based on address book array address
     * @param array $customerAddress
     * @return AddressLocationInfo
     */
    public function buildAddressBookAddress(array $customerAddress): AddressLocationInfo
    {
        $address = new AddressLocationInfo();
        $address->line1 = $customerAddress['street'];
        $address->city = $customerAddress['city'];
        $address->postalCode = $customerAddress['zipcode'];
        $address->country = $this->getCountryIso3($customerAddress['countryId']);
        if (array_key_exists('countryStateId', $customerAddress) && !empty($customerAddress['countryStateId'])) {
            $address->region = $this->getStateName($customerAddress['countryStateId']);
        }

        return $address;
    }

    /**
     * build Address-model based on databag address
     *
     * @param RequestDataBag $customerAddress
     * @return AddressLocationInfo
     */
    public function buildDataBagAddress(RequestDataBag $customerAddress): AddressLocationInfo
    {
        $address = new AddressLocationInfo();
        $address->line1 = $customerAddress->get('street');
        $address->city = $customerAddress->get('city');
        $address->postalCode = $customerAddress->get('zipcode');
        $address->country = $this->getCountryIso3($customerAddress->get('countryId'));

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

        return $address;
    }

    /**
     * @param array $addressArray
     * @return AddressLocationInfo
     */
    public function buildAddressFromArray(array $addressArray)
    {
        $address = new AddressLocationInfo();
        $address->line1 = $addressArray['address_line_1'];
        $address->line2 = $addressArray['address_line_2'];
        $address->line3 = $addressArray['address_line_3'];
        $address->city = $addressArray['city'];
        $address->postalCode = $addressArray['postcode'];
        $address->region = $addressArray['region'];
        $address->country = $addressArray['country'];

        return $address;
    }

    /**
     * @param string $country
     * @return bool
     */
    public function checkCountryRestriction(string $country): bool
    {
        $countriesForDelivery = $this->getPluginConfig(Form::TAX_CALCULATION_MODE);

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

    /**
     * @param AddressLocationInfo $addressLocationInfo
     * @param string|null $addressId
     * @param Session $session
     * @param bool $checkSession
     * @return void
     */
    public function validate(
        AddressLocationInfo $addressLocationInfo,
        ?string             $addressId,
        Session             $session,
        bool                $checkSession = true
    )
    {
        $adapter = $this->getAdapter();

        if ($this->isAddressToBeValidated($addressLocationInfo, $session, $addressId, $checkSession)) {
            $service = $adapter->getService('ValidateAddress');
            $response = $service->validate($addressLocationInfo);
            $parsed = $service->parseAvalaraResponse($addressLocationInfo, $response);
            $sessionAddresses = $session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

            if ($parsed['code'] == ValidateAddress::VALIDATION_CODE_VALID) {
                $sessionAddresses[$addressId] = [
                    'hash' => $parsed['hash'],
                    'messages' => $parsed['messages'],
                    'valid' => true
                ];
            } else {
                $sessionAddresses[$addressId] = [
                    'hash' => $parsed['hash'],
                    'messages' => $parsed['messages'],
                    'valid' => false
                ];
            }
            $session->set(Form::SESSION_AVALARA_ADDRESS_VALIDATION, $sessionAddresses);
        }
    }

    /**
     * @param AddressLocationInfo $address
     * @param Session $session
     * @param string|null $addressId
     * @param bool $checkSession
     * @return bool
     */
    public function isAddressToBeValidated(AddressLocationInfo $address, Session $session, ?string $addressId, bool $checkSession)
    {
        if (is_null($addressId)) {
            return true;
        }

        if (!$this->checkCountryRestriction($address->country)) {
            return false;
        }

        if (!$this->getPluginConfig(Form::ADDRESS_VALIDATION_REQUIRED_FIELD)) {
            return false;
        }

        if ($checkSession) {
            return $this->checkSession($session, $addressId, $address);
        }

        return true;
    }

    /**
     * @param Session $session
     * @param string $addressId
     * @return bool
     */
    public function checkSession(Session $session, string $addressId, $address)
    {
        $sessionAddresses = $session->get(Form::SESSION_AVALARA_ADDRESS_VALIDATION);

        if (!is_array($sessionAddresses)) {
            return true;
        }

        if (array_key_exists($addressId, $sessionAddresses) && $sessionAddresses[$addressId]['valid']) {
            if ($sessionAddresses[$addressId]['hash'] == self::getAddressHash($address)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $countryId
     * @return mixed
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getCountryIso3(string $countryId)
    {
        $connection = Kernel::getConnection();
        $sql = "SELECT iso3 FROM country WHERE id = UNHEX('$countryId')";
        $country = $connection->executeQuery($sql)->fetchAssociative();

        return $country ? $country['iso3'] : '';
    }

    /**
     * @param string $countryId
     * @return mixed
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getStateName(string $countryStateId)
    {
        $connection = Kernel::getConnection();
        $sql = "SELECT short_code FROM country_state WHERE id = UNHEX('$countryStateId')";
        $countryState = $connection->executeQuery($sql)->fetchAssociative();

        return $countryState ? $countryState['short_code'] : '';
    }

    /**
     * get hash of given address
     *
     * @param \Avalara\AddressLocationInfo $address
     * @return string
     */
    public static function getAddressHash(AddressLocationInfo $address)
    {
        return md5($address->line1 . $address->postalCode . $address->city . $address->country);
    }
}
