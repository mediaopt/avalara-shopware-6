import './service/avalaraApiTestService';
import './component/avalara-api-test-button';
import './service/avalaraAddressTestService';
import './service/supportFormService';
import './service/avalaraOrderStatesService';
import './component/avalara-address-test-button';
import './component/support-form';
import './component/avalara-order-states';

import localeDE from './snippet/de_DE.json';
import localeEN from './snippet/en_GB.json';
Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);
