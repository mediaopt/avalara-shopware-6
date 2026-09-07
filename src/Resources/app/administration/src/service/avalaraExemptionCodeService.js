const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class AvalaraExemptionCodeService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'avalara-exemption-code') {
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

Application.addServiceProvider('avalaraExemptionCode', (container) => {
    const initContainer = Application.getContainer('init');
    return new AvalaraExemptionCodeService(initContainer.httpClient, container.loginService);
});
