<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class DashboardControllerTest extends ApiTestCase
{
    public function testSummaryReflectsWonDealsAndCustomers(): void
    {
        $auth      = $this->actingAs('arjun.rep@brightfield.test');
        $companyId = json_decode($auth->post('api/v1/companies', ['name' => 'Dashboard Co'])->getJSON(), true)['data']['id'];
        $dealId    = json_decode(
            $auth->post('api/v1/deals', ['companyId' => $companyId, 'name' => 'Big Win', 'valueAmount' => 2500])->getJSON(),
            true,
        )['data']['id'];
        $auth->post('api/v1/deals/' . $dealId . '/change-stage', ['stage' => 'WON']);

        $summary = $auth->get('api/v1/dashboard/summary');
        $summary->assertStatus(200);
        $totals = json_decode($summary->getJSON(), true)['data']['totals'];

        $this->assertSame(1, (int) $totals['totalCompanies']);
        $this->assertSame(2500.0, (float) $totals['revenueThisMonth']);
    }

    public function testPipelineByStageReport(): void
    {
        $auth      = $this->actingAs('arjun.rep@brightfield.test');
        $companyId = json_decode($auth->post('api/v1/companies', ['name' => 'Pipeline Co'])->getJSON(), true)['data']['id'];
        $auth->post('api/v1/deals', ['companyId' => $companyId, 'name' => 'Deal 1', 'valueAmount' => 100]);

        $report = $auth->get('api/v1/reports/pipeline-by-stage');
        $report->assertStatus(200);
        $rows = json_decode($report->getJSON(), true)['data'];

        $this->assertSame('PROSPECTING', $rows[0]['stage']);
        $this->assertSame(1, (int) $rows[0]['count']);
    }

    public function testOnlyManagersAndAdminsSeeTheLeaderboard(): void
    {
        $rep     = $this->actingAs('arjun.rep@brightfield.test');
        $manager = $this->actingAs('priya.manager@brightfield.test');

        $rep->get('api/v1/reports/salesperson-performance')->assertStatus(403);
        $manager->get('api/v1/reports/salesperson-performance')->assertStatus(200);
    }
}
