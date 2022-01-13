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
 * @package Shopware\Plugins\MoptAvalara\Bootstrap
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
}
