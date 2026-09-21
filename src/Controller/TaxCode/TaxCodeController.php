<?php

namespace MoptAvalara6\Controller\TaxCode;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class TaxCodeController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/api/_action/avalara-tax-code/list',
        name: 'api.action.avalara-tax-code.list',
        methods: ['POST']
    )]
    public function list(Request $request): JsonResponse
    {
        $rawChannelId = $request->request->get('salesChannelId');
        $salesChannelId = ($rawChannelId === null || $rawChannelId === 'null') ? null : $rawChannelId;
        $filter = $request->request->get('filter');

        try {
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);
            $client = $adapter->getAvaTaxClient();

            $avalaraFilter = null;
            if (!empty($filter)) {
                $escaped = str_replace("'", "\\'", $filter);
                $avalaraFilter = "taxCode startsWith '{$escaped}' OR description contains '{$escaped}'";
            }

            $response = $client->listTaxCodes($avalaraFilter, 500, null, 'taxCode ASC');

            if (is_string($response)) {
                return new JsonResponse(['success' => false, 'taxCodes' => [], 'message' => $response]);
            }

            $taxCodes = [];
            if (!empty($response->value)) {
                foreach ($response->value as $taxCode) {
                    $taxCodes[] = [
                        'value' => $taxCode->taxCode,
                        'label' => $taxCode->taxCode . ' — ' . $taxCode->description,
                    ];
                }
            }

            return new JsonResponse(['success' => true, 'taxCodes' => $taxCodes]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'taxCodes' => [], 'message' => $e->getMessage()]);
        }
    }
}