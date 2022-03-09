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
    const TAX_COUNTRY_RESTRICTION = 'MoptAvalara6.config.taxCountryRestriction';

    /**
     * @var string Field name for the plugin config
     */
    const ADDRESS_VALIDATION_REQUIRED_FIELD = 'MoptAvalara6.config.addressValidationRequired';

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
    const SESSION_AVALARA_ADDRESS_KEY = 'avalaraAddressKey';

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
    const CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE = 'avalara_order_tax_document_code';

    /**
     * @var string Fieldset name for the plugin custom field
     */
    const CUSTOM_FIELD_AVALARA_ORDER_TAX_DOCUMENT_CODE_FIELDSET = 'avalara_order_tax_document_code_fieldset';

    /**
     * @var string Field name for the vlck plugin custom field
     */
    const CUSTOM_FIELD_PRODUCT_WAREHOUSE = 'vlck_warehouse_id';
}
