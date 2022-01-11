import AvalaraService from '../core/service/api/avalara.service';

const { Application } = Shopware;

const initContainer = Application.getContainer('init');

Application.addServiceProvider(
    'AvalaraService',
    (container) => new AvalaraService(initContainer.httpClient, container.loginService),
);
