<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Service;

use Avalara\AddressLocationInfo;
use Avalara\AddressResolutionModel;

/**
 * @author Mediaopt GmbH
 * @package Shopware\Plugins\MoptAvalara\Service
 */
class ValidateAddress extends AbstractService
{

    const VALIDATION_CODE_VALID = 0;
    const VALIDATION_CODE_INVALID = 1;
    const VALIDATION_CODE_BAD_RESPONSE = 2;

    /**
     * Ignore any difference in this address parts
     * @var array
     */
    private static $ignoreAddressParts = [
        'region',
        'latitude',
        'longitude',
    ];

    /**
     * @var array
     */
    private static $requiredAddressParts = [
        'line1',
        'city',
        'postalCode',
        'country',
    ];

    /**
     * @param \Avalara\AddressLocationInfo $address
     * @return AddressResolutionModel
     */
    public function validate(AddressLocationInfo $address)
    {
        return $this->getAdapter()->getAvaTaxClient()->resolveAddressPost($address);
    }

    /**
     * @param \Avalara\AddressLocationInfo $address
     * @return array
     */
    public function getEmptyFields(AddressLocationInfo $address): array
    {
        $missingParts = [];
        foreach (self::$requiredAddressParts as $requiredAddressPart) {
            if (empty($address->$requiredAddressPart)){
                $missingParts[] = $requiredAddressPart;
            }
        }
        return $missingParts;
    }

    /**
     *
     * @param \Avalara\AddressLocationInfo $checkedAddress
     * @param \stdClass $response
     * @return array
     */
    public function parseAvalaraResponse(AddressLocationInfo $checkedAddress, $response): array
    {
        $return = [
            'code' => self::VALIDATION_CODE_VALID,
            'suggestedAddress' => [],
            'messages' => []
        ];

        if (null === $response || !is_object($response) || empty($response->validatedAddresses)) {
            $return['code'] = self::VALIDATION_CODE_BAD_RESPONSE;
            return $return;
        }

        /* @var $suggestedAddress \Avalara\AddressLocationInfo */
        $suggestedAddress = $response->validatedAddresses[0];

        foreach ($checkedAddress as $key => $value) {
            if (in_array($key, self::$ignoreAddressParts, false)) {
                continue;
            }
            if (isset($suggestedAddress->$key) && strtolower($suggestedAddress->$key) !== strtolower((string)$value)) {
                $return['suggestedAddress'][$key] = $suggestedAddress->$key;
            }
        }

        if (isset($response->messages)) {
            $return['code'] = self::VALIDATION_CODE_INVALID;
            foreach ($response->messages as $message) {
                $return['messages'][] = $message->details;
            }
        }

        return $return;
    }
}
