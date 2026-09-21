const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class AvalaraTaxCodeService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'avalara-tax-code') {
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

Application.addServiceProvider('avalaraTaxCode', (container) => {
    const initContainer = Application.getContainer('init');
    return new AvalaraTaxCodeService(initContainer.httpClient, container.loginService);
});