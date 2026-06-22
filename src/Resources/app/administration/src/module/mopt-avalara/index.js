import './page/mopt-avalara-settings';
import './component/config-watcher';

Shopware.Module.register('mopt-avalara', {
    type: 'plugin',
    name: 'mopt-avalara',
    color: '#ff8e3d',
    icon: 'regular-cog',
    title: 'mopt-avalara.general.mainMenuItemGeneral',
    description: 'mopt-avalara.general.descriptionTextModule',

    routes: {
        index: {
            component: 'mopt-avalara-settings',
            path: 'index',
            icon: 'regular-cog',
            meta: {
                parentPath: 'sw.settings.index.plugins'
            }
        },
    },

    settingsItem: [{
        group: 'plugins',
        icon: 'regular-cog',
        to: 'mopt.avalara.index',
    }],
});