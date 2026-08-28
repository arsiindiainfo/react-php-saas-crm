<?php

namespace App\Models\Concerns;

use App\Libraries\ListQuery;

/**
 * Shared `CALL sp_records_search(...)` caller (§10.2) — every list Model
 * (Company/Contact/Lead/Deal/Task) uses this instead of hand-writing its own
 * paginated query.
 */
trait SearchesRecords
{
    /**
     * @param list<int>|null                                                            $ownerScope
     * @param array{status?:?string,stage?:?string,companyId?:?int,assignedTo?:?int} $filters
     *
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    protected function searchRecords(string $entityName, ListQuery $query, ?array $ownerScope, array $filters = []): array
    {
        $ownerScopeCsv = $ownerScope === null ? null : implode(',', $ownerScope);

        $result = $this->db->query(
            'CALL sp_records_search(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, @o_total)',
            [
                $entityName,
                $query->search,
                $query->sort,
                $query->direction,
                $query->page,
                $query->limit,
                $ownerScopeCsv,
                $filters['status'] ?? null,
                $filters['stage'] ?? null,
                $filters['companyId'] ?? null,
                $filters['assignedTo'] ?? null,
            ],
        );

        $rows  = $result !== false ? $result->getResultArray() : [];
        $total = (int) ($this->db->query('SELECT @o_total AS total')->getRowArray()['total'] ?? 0);

        return ['rows' => $rows, 'total' => $total];
    }
}
