<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\ReportingService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Dashboard')]
class DashboardController extends BaseApiController
{
    #[OA\Get(
        path: '/dashboard/summary',
        summary: 'KPI cards + 12-month revenue trend (§22.2), scoped to the caller\'s ownership set',
        tags: ['Dashboard'],
        security: [['bearerAuth' => []]],
        responses: [new OA\Response(response: 200, description: '{ totals: {...5 KPIs}, monthlyTrend: [{month, revenue}] }')],
    )]
    public function summary()
    {
        return $this->ok((new ReportingService())->dashboardSummary(AuthContext::ownerScope()));
    }
}
