<?php

namespace App\Controllers;

use App\Exceptions\ForbiddenRoleException;
use App\Libraries\AuthContext;
use App\Services\ReportingService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Reports')]
class ReportsController extends BaseApiController
{
    private ReportingService $reporting;

    public function __construct()
    {
        $this->reporting = new ReportingService();
    }

    #[OA\Get(
        path: '/reports/pipeline-by-stage',
        summary: 'Open deal count + value per stage (funnel chart)',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: '[{ stage, count, value }]')],
    )]
    public function pipelineByStage()
    {
        return $this->ok($this->reporting->pipelineByStage(AuthContext::ownerScope()));
    }

    #[OA\Get(
        path: '/reports/monthly-sales',
        summary: 'Won-deal revenue by month, last 12 months',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: '[{ month, revenue }]')],
    )]
    public function monthlySales()
    {
        return $this->ok($this->reporting->monthlySales(AuthContext::ownerScope()));
    }

    #[OA\Get(
        path: '/reports/salesperson-performance',
        summary: 'Per-rep deals-won + revenue leaderboard (manager/admin only)',
        tags: ['Reports'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: '[{ userId, name, dealsWon, revenue }]'),
            new OA\Response(response: 403, description: 'FORBIDDEN_ROLE — SALES_REP cannot view the leaderboard', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function salespersonPerformance()
    {
        $user = $this->authUser();

        if (! $user->isAdmin() && ! $user->isManager()) {
            throw new ForbiddenRoleException('Only managers and admins can view the leaderboard.');
        }

        return $this->ok($this->reporting->salespersonPerformance(AuthContext::ownerScope()));
    }
}
