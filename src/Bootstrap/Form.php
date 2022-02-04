<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright derksen mediaopt GmbH
 */

namespace MoptAvalara6\Bootstrap;

/**
 * This class will represent the plugin config options
 *
 * @author derksen mediaopt GmbH
 * @package MoptAvalara6\Bootstrap
 */
class Form
{
    /**
     * @var string Field name for the plugin config
     */
    const IS_LIVE_MODE_FIELD = 'MoptAvalara6.config.isLiveMode';

    /**
     * @var string Field name for the plugin config
     */
    const ACCOUNT_NUMBER_FIELD = 'MoptAvalara6.config.accountNumber';

    /**
     * @var string Field name for the plugin config
     */
    const LICENSE_KEY_FIELD = 'MoptAvalara6.config.licenseKey';

    /**
     * @var string Field name for the plugin config
     */
    const COMPANY_CODE_FIELD = 'MoptAvalara6.config.companyCode';

    /**
     * @var string Field name for the plugin config
     */
    const TAX_COUNTRY_RESTRICTION = 'MoptAvalara6.config.taxCountryRestriction';

    /**
     * Values and options
     */
    const DELIVERY_COUNTRY_NO_VALIDATION = 1;
    const DELIVERY_COUNTRY_USA = 2;
    const DELIVERY_COUNTRY_CANADA = 3;
    const DELIVERY_COUNTRY_USA_AND_CANADA = 4;


    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_ADDRESS_LINE_1_FIELD = 'MoptAvalara6.config.addressLine1';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_ADDRESS_LINE_2_FIELD = 'MoptAvalara6.config.addressLine2';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_ADDRESS_LINE_3_FIELD = 'MoptAvalara6.config.addressLine3';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_POSTAL_CODE_FIELD = 'MoptAvalara6.config.postalCode';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_CITY_FIELD = 'MoptAvalara6.config.city';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_REGION_FIELD = 'MoptAvalara6.config.region';

    /**
     * @var string Field name for the plugin config
     */
    const ORIGIN_COUNTRY_FIELD = 'MoptAvalara6.config.country';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_CART_UPDATED = 'cartUpdated';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_TAXES = 'avalaraTaxes';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_TAXES_TRANSFORMED = 'avalaraTaxesTransformed';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_MODEL = 'avalaraModel';
}
