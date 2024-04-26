<?php

/**
 * For the full copyright and license information, refer to the accompanying LICENSE file.
 *
 * @copyright Mediaopt GmbH
 */

namespace MoptAvalara6\Bootstrap;

/**
 * This class will represent the plugin config options
 *
 * @author Mediaopt GmbH
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
    const TAX_CALCULATION_MODE = 'MoptAvalara6.config.taxCalculationMode';

    /**
     * @var string Field name for the plugin config
     */
    const SEND_GET_TAX_ONLY = 'MoptAvalara6.config.sendGetTaxOnly';

    /**
     * @var string Field name for the plugin config
     */
    const LOG_LEVEL = 'MoptAvalara6.config.logLevel';

    /**
     * @var string Field name for the plugin config
     */
    const ADDRESS_VALIDATION_REQUIRED_FIELD = 'MoptAvalara6.config.addressValidationRequired';

    /**
     * @var string Field name for the plugin config
     */
    const CONNECTION_TIMEOUT = 'MoptAvalara6.config.connectionTimeout';

    /**
     * @var string Field name for the plugin config
     */
    const HEADLESS_MODE = 'MoptAvalara6.config.headlessMode';

    /**
     * @var string Field name for the plugin config
     */
    const BLOCK_CART_ON_ERROR_FIELD = 'MoptAvalara6.config.blockCartOnError';

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
     * @var string Field name for the plugin config
     */
    const CANCEL_ORDER_STATUS_FIELD = 'MoptAvalara6.config.orderCancel';

    /**
     * @var string Field name for the plugin config
     */
    const REFUND_ORDER_STATUS_FIELD = 'MoptAvalara6.config.orderRefund';

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

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_MODEL_KEY = 'avalaraModelKey';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_IS_GROSS_PRICE = 'avalaraIsGrossPrice';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_ADDRESS_VALIDATION = 'avalaraAddressValidation';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_CURRENT_ADDRESS_ID = 'avalaraAddressFlag';

    /**
     * @var string Field name for the plugin session key
     */
    const SESSION_AVALARA_REDIRECT_AFTER_ADDRESS_CHANGE = 'avalaraRedirectAfterAddressChange';

    /**
     * @var string Field name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE = 'avalara_shipping_tax_code';

    /**
     * @var string Fieldset name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_SHIPPING_TAX_CODE_FIELDSET = 'avalara_shipping_tax_code_fieldset';

    /**
     * @var string Field name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE = 'avalara_product_tax_code';

    /**
     * @var string Fieldset name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_PRODUCT_TAX_CODE_FIELDSET = 'avalara_product_tax_code_fieldset';

    /**
     * @var string Field name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE = 'avalara_category_tax_code';

    /**
     * @var string Fieldset name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_CATEGORY_TAX_CODE_FIELDSET = 'avalara_category_tax_code_fieldset';

    /**
     * @var string Field name for the vlck plugin custom field
     */
    const CUSTOM_FIELD_PRODUCT_WAREHOUSE = 'vlck_warehouse_id';

    /**
     * @var array plugin session keys
     */
    const SESSION_KEYS = [
        self::SESSION_AVALARA_ADDRESS_VALIDATION,
        self::SESSION_AVALARA_TAXES_TRANSFORMED,
        self::SESSION_AVALARA_MODEL,
        self::SESSION_AVALARA_MODEL_KEY,
        self::SESSION_AVALARA_IS_GROSS_PRICE,
        self::SESSION_AVALARA_ADDRESS_VALIDATION,
        self::SESSION_AVALARA_CURRENT_ADDRESS_ID,
        self::SESSION_AVALARA_REDIRECT_AFTER_ADDRESS_CHANGE,
    ];

    /**
     * @var string
     */
    const TAX_REQUEST_STATUS = 'AvalaraTaxCalculationStatus';
    const TAX_REQUEST_STATUS_FAILED = 'failed';
    const TAX_REQUEST_STATUS_SUCCESS = 'success';
    const TAX_REQUEST_STATUS_NOT_NEEDED = 'notNeeded';
}
