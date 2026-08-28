<?php

namespace App\Services;

use CodeIgniter\Database\ConnectionInterface;
use Config\Database;

/**
 * §22.2/§22.9 backing queries. Only the dashboard summary needs a stored
 * procedure (the OUT-param aggregation, §8.4); the three reports below are
 * simple read-only GROUP BY aggregations — a dedicated procedure per report
 * would add no protection a plain scoped query builder call doesn't already
 * give a read-only endpoint.
 */
class ReportingService
{
    private ConnectionInterface $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{totals:array<string,mixed>,monthlyTrend:list<array<string,mixed>>}
     */
    public function dashboardSummary(?array $ownerScope): array
    {
        $scopeCsv = $ownerScope === null ? null : implode(',', $ownerScope);

        $result = $this->db->query(
            'CALL sp_dashboard_summary(?, @o_companies, @o_leads, @o_rate, @o_openCount, @o_openValue, @o_revenue)',
            [$scopeCsv],
        );
        $trend  = $result !== false ? $result->getResultArray() : [];
        $totals = $this->db->query(
            'SELECT @o_companies AS totalCompanies, @o_leads AS newLeadsThisMonth, @o_rate AS conversionRate,'
            . ' @o_openCount AS openDealsCount, @o_openValue AS openDealsValue, @o_revenue AS revenueThisMonth',
        )->getRowArray();

        return ['totals' => $totals, 'monthlyTrend' => $trend];
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return list<array{stage:string,count:int,value:float}>
     */
    public function pipelineByStage(?array $ownerScope): array
    {
        $builder = $this->db->table('deals')
            ->select('stage, COUNT(*) AS count, COALESCE(SUM(value_amount), 0) AS value')
            ->where('deleted_at', null)
            ->groupBy('stage');

        $this->applyOwnerScope($builder, $ownerScope, 'owner_id');

        return $builder->get()->getResultArray();
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return list<array{month:string,revenue:float}>
     */
    public function monthlySales(?array $ownerScope): array
    {
        $builder = $this->db->table('deals')
            ->select("DATE_FORMAT(closed_at, '%Y-%m') AS month, COALESCE(SUM(value_amount), 0) AS revenue")
            ->where('deleted_at', null)
            ->where('stage', 'WON')
            ->where('closed_at >=', date('Y-m-01', strtotime('-11 months')))
            ->groupBy('month')
            ->orderBy('month', 'ASC');

        $this->applyOwnerScope($builder, $ownerScope, 'owner_id');

        return $builder->get()->getResultArray();
    }

    /**
     * Manager/admin only (§19) — per-rep won deals, revenue, conversion rate.
     *
     * @param list<int>|null $ownerScope
     *
     * @return list<array{userId:int,name:string,dealsWon:int,revenue:float}>
     */
    public function salespersonPerformance(?array $ownerScope): array
    {
        $builder = $this->db->table('deals d')
            ->select('u.id AS userId, u.name AS name, COUNT(*) AS dealsWon, COALESCE(SUM(d.value_amount), 0) AS revenue')
            ->join('users u', 'u.id = d.owner_id')
            ->where('d.deleted_at', null)
            ->where('d.stage', 'WON')
            ->groupBy('u.id, u.name')
            ->orderBy('revenue', 'DESC');

        $this->applyOwnerScope($builder, $ownerScope, 'd.owner_id');

        return $builder->get()->getResultArray();
    }

    /**
     * @param list<int>|null $ownerScope
     */
    private function applyOwnerScope(\CodeIgniter\Database\BaseBuilder $builder, ?array $ownerScope, string $column): void
    {
        if ($ownerScope !== null) {
            $builder->whereIn($column, $ownerScope);
        }
    }
}
