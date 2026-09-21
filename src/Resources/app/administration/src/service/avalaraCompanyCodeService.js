const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class AvalaraCompanyCodeService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'avalara-company-code') {
        super(httpClient, loginService, apiEndpoint);
    }

    list(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/list`, values, { headers })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

Application.addServiceProvider('avalaraCompanyCode', (container) => {
    const initContainer = Application.getContainer('init');
    return new AvalaraCompanyCodeService(initContainer.httpClient, container.loginService);
});