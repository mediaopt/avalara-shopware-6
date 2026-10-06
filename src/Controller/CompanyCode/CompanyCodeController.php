<?php

namespace MoptAvalara6\Controller\CompanyCode;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class CompanyCodeController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/api/_action/avalara-company-code/list',
        name: 'api.action.avalara-company-code.list',
        methods: ['POST']
    )]
    public function list(Request $request): JsonResponse
    {
        $rawChannelId = $request->request->get('salesChannelId');
        // JS sends the string "null" when on "All Sales Channels" — convert it to actual null
        $salesChannelId = ($rawChannelId === null || $rawChannelId === 'null') ? null : $rawChannelId;

        try {
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);
            $client = $adapter->getAvaTaxClient();
            $response = $client->queryCompanies(null, null, null, null, 'companyCode ASC');

            if (is_string($response)) {
                // SDK returned an error body string (catchExceptions=true catches HTTP errors)
                return new JsonResponse(['success' => false, 'companies' => [], 'message' => $response]);
            }

            $companies = [];
            if (!empty($response->value)) {
                foreach ($response->value as $company) {
                    $companies[] = [
                        'value' => $company->companyCode,
                        'label' => $company->name . ' (' . $company->companyCode . ')',
                    ];
                }
            }

            return new JsonResponse(['success' => true, 'companies' => $companies]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'companies' => [], 'message' => $e->getMessage()]);
        }
    }
}