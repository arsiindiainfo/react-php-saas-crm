<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * ADMIN-only audit trail (§11, §22.11). Not part of the shared
 * `sp_records_search` contract (§10.2) — that's reserved for the five
 * ownership-scoped list resources; the audit log is a flat, unscoped,
 * admin-only read with its own simple filter set.
 */
class AuditLogModel extends Model
{
    protected $table         = 'audit_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'action', 'entity_type', 'entity_id', 'details'];

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function search(int $page, int $limit, ?string $action, ?string $entityType, ?int $userId, ?string $from, ?string $to): array
    {
        $builder = $this->builder();

        if ($action !== null) {
            $builder->where('action', $action);
        }
        if ($entityType !== null) {
            $builder->where('entity_type', $entityType);
        }
        if ($userId !== null) {
            $builder->where('user_id', $userId);
        }
        if ($from !== null) {
            $builder->where('created_at >=', $from);
        }
        if ($to !== null) {
            $builder->where('created_at <=', $to);
        }

        $total = $builder->countAllResults(false);
        $rows  = $builder->orderBy('created_at', 'DESC')->limit($limit, ($page - 1) * $limit)->get()->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }
}
