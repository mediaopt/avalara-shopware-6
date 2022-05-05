import './service/avalaraApiTestService';
import './component/avalara-api-test-button';
import './service/avalaraAddressTestService';
import './component/avalara-address-test-button';

import localeDE from './snippet/de_DE.json';
import localeEN from './snippet/en_GB.json';
Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);
