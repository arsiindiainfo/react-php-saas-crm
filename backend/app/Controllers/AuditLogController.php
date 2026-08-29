<?php

namespace App\Controllers;

use App\Models\AuditLogModel;
use OpenApi\Attributes as OA;

/**
 * ADMIN-only (route-guarded — see Config\Routes) audit trail (§22.11).
 */
#[OA\Tag(name: 'Audit Log')]
class AuditLogController extends BaseApiController
{
    #[OA\Get(
        path: '/audit-logs',
        summary: 'Paginated audit trail — every sensitive action, unscoped (ADMIN only)',
        tags: ['Audit Log'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'string'), description: 'e.g. LEAD_CONVERTED, DEAL_STAGE_CHANGED'),
            new OA\Parameter(name: 'entityType', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'userId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Paginated audit log entries'),
            new OA\Response(response: 403, description: 'FORBIDDEN_ROLE — ADMIN only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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
