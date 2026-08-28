<?php

namespace App\Controllers;

use App\Models\AuditLogModel;

/**
 * ADMIN-only (route-guarded — see Config\Routes) audit trail (§22.11).
 */
class AuditLogController extends BaseApiController
{
    public function index()
    {
        $page  = max(1, (int) ($this->request->getGet('page') ?? 1));
        $limit = min(100, max(1, (int) ($this->request->getGet('limit') ?? 20)));

        $result = (new AuditLogModel())->search(
            $page,
            $limit,
            $this->request->getGet('action') ?: null,
            $this->request->getGet('entityType') ?: null,
            $this->request->getGet('userId') ? (int) $this->request->getGet('userId') : null,
            $this->request->getGet('from') ?: null,
            $this->request->getGet('to') ?: null,
        );

        return $this->okPaginated($result['rows'], $page, $limit, $result['total']);
    }
}
