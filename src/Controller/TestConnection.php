<?php declare(strict_types=1);

namespace Avalara\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
/**
 * @RouteScope(scopes={"api"})
 */
class TestConnection extends AbstractController
{
    /**
     * @Route(
     *     "/api/_action/avalara/test-connection",
     *     name="api.action.avalara.test.connection",
     *     methods={"GET"}
     * )
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse(['result' => true]);
    }
}
