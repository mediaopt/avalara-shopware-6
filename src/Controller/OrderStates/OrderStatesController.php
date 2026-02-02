<?php

namespace MoptAvalara6\Controller\OrderStates;

use Monolog\Level;
use MoptAvalara6\Bootstrap\Form;
use MoptAvalara6\Service\LogHelper;
use Shopware\Core\Kernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

#[Route(defaults: ['_routeScope' => ['api']])]
class OrderStatesController extends AbstractController
{
    private SystemConfigService $systemConfigService;

    public function __construct(SystemConfigService $systemConfigService)
    {
        $this->systemConfigService = $systemConfigService;
    }

    #[Route(
        path: '/api/_action/avalara-order-states/getStates',
        name: 'api.action.avalara-order-states.getStates',
        methods: ['POST']
    )]
    public function getStates(Request $request): JsonResponse
    {
        $localeId = $request->request->get('localeId');
        $selectOptions = $this->getOptions($localeId);

        return new JsonResponse([
            'cancelStatusId' => $this->systemConfigService->get(Form::CANCEL_ORDER_STATUS_FIELD),
            'refundStatusId' => $this->systemConfigService->get(Form::REFUND_ORDER_STATUS_FIELD),
            'selectOptions' => $selectOptions,
        ]);
    }

    #[Route(
        path: '/api/_action/avalara-order-states/setCancelStatusId',
        name: 'api.action.avalara-order-states.setCancelStatusId',
        methods: ['POST']
    )]
    public function setCancelStatusId(Request $request): JsonResponse
    {
        $cancelStatus = $request->request->get('cancelStatusId');
        $this->systemConfigService->set(Form::CANCEL_ORDER_STATUS_FIELD, $cancelStatus);
        return new JsonResponse([
            'value' => $cancelStatus
        ]);
    }

    #[Route(
        path: '/api/_action/avalara-order-states/setRefundStatusId',
        name: 'api.action.avalara-order-states.setRefundStatusId',
        methods: ['POST']
    )]
    public function setRefundStatusId(Request $request): JsonResponse
    {
        $refundStatus = $request->request->get('refundStatusId');
        $this->systemConfigService->set(Form::REFUND_ORDER_STATUS_FIELD, $refundStatus);
        return new JsonResponse([
            'value' => $refundStatus
        ]);
    }

    /**
     * @param string $localeId
     * @return array
     */
    private function getOptions(string $localeId): array
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('LOWER(HEX(sms.id)) AS value', 'smst.name AS label')
            ->from('state_machine', 'sm')
            ->leftJoin('sm', 'state_machine_state', 'sms', 'sms.state_machine_id = sm.id')
            ->leftJoin('sms', 'state_machine_state_translation', 'smst', 'smst.state_machine_state_id = sms.id')
            ->leftJoin('smst', 'language', 'l', 'smst.language_id = l.id')
            ->where('sm.technical_name = :technicalName')
            ->andWhere('l.locale_id = UNHEX(:localeId)')
            ->setParameter('technicalName', 'order.state')
            ->setParameter('localeId', $localeId);

        try {
            $options = $qb->fetchAllAssociative();
        } catch (\Exception $e) {
            $options = [];
            LogHelper::addLog(Level::Error, $e->getMessage());
        }

        return $options;
    }
}