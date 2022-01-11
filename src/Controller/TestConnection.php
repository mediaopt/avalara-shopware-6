<?php declare(strict_types=1);

namespace MoptAvalara6\Controller;

require_once  __DIR__ . '/../../vendor/autoload.php';

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Avalara\AvaTaxClient;
use Shopware\Core\System\SystemConfig\SystemConfigService;
/**
 * @RouteScope(scopes={"api"})
 */
class TestConnection extends AbstractController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
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
        $accountNumber = $this->systemConfigService->get('MoptAvalara6.config.accountNumber');
        $licenseKey = $this->systemConfigService->get('MoptAvalara6.config.licenseKey');

        $client = new AvaTaxClient(
            'MoptAvalara6',
            '1.0',
            'localhost',
            'sandbox'
        );

        $client->withSecurity($accountNumber, $licenseKey);

        $pingResponse = $client->ping();

        $result = false;
        $message = 'Connection test failed: unknown error.';

        if (!empty($pingResponse->authenticated)) {
            $result = true;
            $message = 'Connection test successful.';
        }

        return new JsonResponse([
            'result' => $result,
            'message' => $message
        ]);
    }
}
