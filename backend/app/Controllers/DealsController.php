<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\DealService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Deals')]
class DealsController extends BaseApiController
{
    private DealService $deals;

    private const SORTABLE = ['name', 'valueAmount', 'stage', 'expectedCloseDate', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->deals = new DealService();
    }

    #[OA\Post(
        path: '/deals',
        summary: 'Create a deal directly against an existing company',
        tags: ['Deals'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['companyId', 'name'],
            properties: [
                new OA\Property(property: 'companyId', type: 'integer'),
                new OA\Property(property: 'contactId', type: 'integer', nullable: true),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'valueAmount', type: 'number', minimum: 0, nullable: true),
                new OA\Property(property: 'expectedCloseDate', type: 'string', format: 'date', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Deal created, stage PROSPECTING'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'COMPANY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function create()
    {
        $data = $this->validateBody('dealCreate');

        $deal = $this->deals->createDeal(
            (int) $data['companyId'],
            isset($data['contactId']) ? (int) $data['contactId'] : null,
            AuthContext::ownerScope(),
            $data['name'],
            (float) ($data['valueAmount'] ?? 0),
            $data['expectedCloseDate'] ?? null,
            $this->authUser()->id,
        );

        return $this->created($deal);
    }

    #[OA\Get(
        path: '/deals',
        summary: 'List/board deals — the shared §10 list contract',
        tags: ['Deals'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: self::SORTABLE)),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'stage', in: 'query', schema: new OA\Schema(type: 'string', enum: ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION', 'WON', 'LOST'])),
            new OA\Parameter(name: 'companyId', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated, ownership-scoped list of deals')],
    )]
    public function index()
    {
        $query     = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $stage     = $this->request->getGet('stage');
        $companyId = $this->request->getGet('companyId');

        $result = $this->deals->listDeals(
            $query,
            AuthContext::ownerScope(),
            $stage ?: null,
            $companyId !== null ? (int) $companyId : null,
        );

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    #[OA\Get(
        path: '/deals/{id}',
        summary: 'Deal detail',
        tags: ['Deals'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The deal'),
            new OA\Response(response: 404, description: 'DEAL_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show($id)
    {
        return $this->ok($this->deals->getDeal((int) $id, AuthContext::ownerScope()));
    }

    #[OA\Put(
        path: '/deals/{id}',
        summary: 'Update deal fields (name, value, contact, expected close date)',
        tags: ['Deals'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'valueAmount', type: 'number', minimum: 0),
                new OA\Property(property: 'expectedCloseDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'contactId', type: 'integer', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated deal'),
            new OA\Response(response: 404, description: 'DEAL_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function update($id)
    {
        $data = $this->validateBody('dealUpdate');

        $fields = [];
        foreach (['name' => 'name', 'valueAmount' => 'value_amount', 'expectedCloseDate' => 'expected_close_date', 'contactId' => 'contact_id'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->deals->updateDeal((int) $id, $fields, AuthContext::ownerScope()));
    }

    #[OA\Post(
        path: '/deals/{id}/change-stage',
        summary: 'Move a deal through the pipeline (§8.3) — WON flips the company to CUSTOMER, LOST requires a reason',
        tags: ['Deals'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['stage'],
            properties: [
                new OA\Property(property: 'stage', type: 'string', enum: ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION', 'WON', 'LOST']),
                new OA\Property(property: 'lostReason', type: 'string', nullable: true, description: 'Required when stage is LOST'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated deal'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR or LOST_REASON_REQUIRED', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'DEAL_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'INVALID_TRANSITION — a closed deal cannot change stage', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function changeStage($id)
    {
        $data = $this->validateBody('dealChangeStage');

        $deal = $this->deals->changeStage(
            (int) $id,
            AuthContext::ownerScope(),
            $data['stage'],
            $data['lostReason'] ?? null,
            $this->authUser()->id,
        );

        return $this->ok($deal);
    }
}
