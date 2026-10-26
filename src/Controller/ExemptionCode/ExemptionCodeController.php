<?php

namespace MoptAvalara6\Controller\ExemptionCode;

use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class ExemptionCodeController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/api/_action/avalara-exemption-code/list',
        name: 'api.action.avalara-exemption-code.list',
        methods: ['POST']
    )]
    public function list(Request $request): JsonResponse
    {
        $rawChannelId = $request->request->get('salesChannelId');
        $salesChannelId = ($rawChannelId === null || $rawChannelId === 'null') ? null : $rawChannelId;

        try {
            $adapter = new AvalaraSDKAdapter($this->systemConfigService, $salesChannelId);
            $client = $adapter->getAvaTaxClient();

            $response = $client->listEntityUseCodes(null, null, null, 'code ASC');

            if (is_string($response)) {
                return new JsonResponse(['success' => false, 'exemptionCodes' => [], 'message' => $response]);
            }

            $exemptionCodes = [];
            if (!empty($response->value)) {
                foreach ($response->value as $item) {
                    $exemptionCodes[] = [
                        'value' => $item->code,
                        'label' => $item->code . ' — ' . $item->name,
                    ];
                }
            }

            return new JsonResponse(['success' => true, 'exemptionCodes' => $exemptionCodes]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'exemptionCodes' => [], 'message' => $e->getMessage()]);
        }
    }
}
