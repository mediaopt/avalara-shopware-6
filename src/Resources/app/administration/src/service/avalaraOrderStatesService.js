const ApiService = Shopware.Classes.ApiService;
const { Application } = Shopware;

class avalaraOrderStates extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'avalara-order-states') {
        super(httpClient, loginService, apiEndpoint);
    }

    getStates(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/getStates`, values,{
                headers
            })
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }

    setCancelStatusId(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/setCancelStatusId`, values,{
                headers
            });
    }

    setRefundStatusId(values) {
        const headers = this.getBasicHeaders({});
        return this.httpClient
            .post(`_action/${this.getApiBasePath()}/setRefundStatusId`, values,{
                headers
            });
    }

}

Application.addServiceProvider('avalaraOrderStates', (container) => {
    const initContainer = Application.getContainer('init');
    return new avalaraOrderStates(initContainer.httpClient, container.loginService);
});
