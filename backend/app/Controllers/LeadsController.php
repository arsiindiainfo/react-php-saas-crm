<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\LeadService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Leads')]
class LeadsController extends BaseApiController
{
    private LeadService $leads;

    private const SORTABLE = ['firstName', 'lastName', 'status', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->leads = new LeadService();
    }

    #[OA\Post(
        path: '/leads',
        summary: 'Create a lead',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['firstName', 'lastName', 'source'],
            properties: [
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'email', type: 'string', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'companyName', type: 'string', nullable: true, description: 'Free text, pre-conversion'),
                new OA\Property(property: 'source', type: 'string', enum: ['WEBSITE', 'REFERRAL', 'COLD_CALL', 'EVENT', 'OTHER']),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Lead created, status NEW'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function create()
    {
        $data = $this->validateBody('leadCreate');

        $lead = $this->leads->createLead(
            $data['firstName'],
            $data['lastName'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['companyName'] ?? null,
            $data['source'],
            $this->authUser()->id,
        );

        return $this->created($lead);
    }

    #[OA\Get(
        path: '/leads',
        summary: 'List/board leads — the shared §10 list contract',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: self::SORTABLE)),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'DISQUALIFIED'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated, ownership-scoped list of leads')],
    )]
    public function index()
    {
        $query  = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $status = $this->request->getGet('status');

        $result = $this->leads->listLeads($query, AuthContext::ownerScope(), $status ?: null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    #[OA\Get(
        path: '/leads/{id}',
        summary: 'Lead detail',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The lead'),
            new OA\Response(response: 404, description: 'LEAD_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show($id)
    {
        return $this->ok($this->leads->getLead((int) $id, AuthContext::ownerScope()));
    }

    #[OA\Put(
        path: '/leads/{id}',
        summary: 'Update a lead (including a board drag between New/Contacted/Qualified)',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'email', type: 'string', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'companyName', type: 'string', nullable: true),
                new OA\Property(property: 'source', type: 'string', enum: ['WEBSITE', 'REFERRAL', 'COLD_CALL', 'EVENT', 'OTHER']),
                new OA\Property(property: 'status', type: 'string', enum: ['NEW', 'CONTACTED', 'QUALIFIED'], description: 'CONVERTED/DISQUALIFIED are only reachable via their dedicated actions'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated lead'),
            new OA\Response(response: 404, description: 'LEAD_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'INVALID_TRANSITION — the lead already reached a terminal status', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function update($id)
    {
        $data = $this->validateBody('leadUpdate');

        $fields = [];
        foreach (['firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'companyName' => 'company_name', 'source' => 'source', 'status' => 'status'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->leads->updateLead((int) $id, $fields, AuthContext::ownerScope()));
    }

    #[OA\Post(
        path: '/leads/{id}/convert',
        summary: 'Convert a qualified lead into a Company + Contact + Deal (the flagship feature, §8.2)',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['dealName'],
            properties: [
                new OA\Property(property: 'dealName', type: 'string'),
                new OA\Property(property: 'dealValue', type: 'number', minimum: 0, nullable: true),
                new OA\Property(property: 'existingCompanyId', type: 'integer', nullable: true, description: 'Link to an existing company instead of creating a new one'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: '{ company, contact, deal }'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'LEAD_NOT_FOUND or COMPANY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'ALREADY_CONVERTED or NOT_QUALIFIED', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function convert($id)
    {
        $data = $this->validateBody('leadConvert');

        $result = $this->leads->convert(
            (int) $id,
            AuthContext::ownerScope(),
            isset($data['existingCompanyId']) ? (int) $data['existingCompanyId'] : null,
            $data['dealName'],
            (float) ($data['dealValue'] ?? 0),
            $this->authUser()->id,
        );

        return $this->ok($result);
    }

    #[OA\Post(
        path: '/leads/{id}/disqualify',
        summary: 'Disqualify a lead (terminal, reason required)',
        tags: ['Leads'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['reason'],
            properties: [new OA\Property(property: 'reason', type: 'string', minLength: 1, maxLength: 255)],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Lead marked DISQUALIFIED'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'LEAD_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'INVALID_TRANSITION', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function disqualify($id)
    {
        $data = $this->validateBody('leadDisqualify');

        $lead = $this->leads->disqualify((int) $id, AuthContext::ownerScope(), $data['reason'], $this->authUser()->id);

        return $this->ok($lead);
    }
}
