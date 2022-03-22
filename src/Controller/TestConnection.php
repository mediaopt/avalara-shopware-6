<?php declare(strict_types=1);

namespace MoptAvalara6\Controller;

use Monolog\Logger;
use MoptAvalara6\Adapter\AvalaraSDKAdapter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * @RouteScope(scopes={"api"})
 */
class TestConnection extends AbstractController
{
    private SystemConfigService $systemConfigService;

    private Logger $logger;

    public function __construct(SystemConfigService $systemConfigService, $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/api/_action/avalara/test-connection",
     *     name="api.action.avalara.test.connection",
     *     methods={"GET"}
     * )
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        $client = (new AvalaraSDKAdapter($this->systemConfigService, $this->logger))->getAvaTaxClient();

        $pingResponse = $client->ping();

        $message = 'Connection test failed: unknown error.';

        if (!empty($pingResponse->authenticated)) {
            $message = 'Connection test successful.';
        }

        return new JsonResponse([
            'message' => $message
        ]);
    }
}
