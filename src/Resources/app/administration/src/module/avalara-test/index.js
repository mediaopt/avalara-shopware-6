import './page/avalara-test-page';
import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

const { Module } = Shopware;

Module.register('avalara-test', {
    type: 'plugin',
    name: 'AvalaraTest',
    title: 'avalara-test.general.mainMenuItemGeneral',
    description: 'sw-property.general.descriptionTextModule',
    color: '#FFD700',
    icon: 'default-shopping-paper-bag-product',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes: {
        page: {
            component: 'avalara-test-page',
            path: 'page'
        },
    },

    navigation: [{
        id: 'avalara-test-example',
        path: 'avalara.test.page',
        parent: 'sw-extension',
        label: 'avalara-test.general.mainMenuItemGeneral',
        icon: 'default-shopping-paper-bag-product',
        position: 100
    }]
});
