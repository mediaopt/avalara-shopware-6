const ApiService = Shopware.Classes.ApiService;

class AvalaraService extends ApiService {
    constructor(httpClient, loginService, apiEndpoint = 'avalara') {
        super(httpClient, loginService, apiEndpoint);
    }

    testConnection() {
        const headers = this.getBasicHeaders();

        return this.httpClient
            .get(
                `_action/${this.getApiBasePath()}/test-connection`,
                {
                    headers: headers,
                },
            )
            .then((response) => {
                return ApiService.handleResponse(response);
            });
    }
}

export default AvalaraService;
