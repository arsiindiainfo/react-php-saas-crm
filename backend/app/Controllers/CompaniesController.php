<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\CompanyService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Companies')]
class CompaniesController extends BaseApiController
{
    private CompanyService $companies;

    private const SORTABLE = ['name', 'industry', 'status', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    #[OA\Post(
        path: '/companies',
        summary: 'Create a company',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', minLength: 2, maxLength: 180),
                new OA\Property(property: 'industry', type: 'string', nullable: true),
                new OA\Property(property: 'website', type: 'string', format: 'uri', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Company created; caller becomes owner_id'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'DUPLICATE_NAME', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function create()
    {
        $data = $this->validateBody('companyCreate');

        $company = $this->companies->createCompany(
            $data['name'],
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['phone'] ?? null,
            $this->authUser()->id,
        );

        return $this->created($company);
    }

    #[OA\Get(
        path: '/companies',
        summary: 'List companies — the shared §10 list contract',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: self::SORTABLE)),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['PROSPECT', 'CUSTOMER', 'CHURNED'])),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated, ownership-scoped list of companies')],
    )]
    public function index()
    {
        $query = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $status = $this->request->getGet('status');

        $result = $this->companies->listCompanies($query, AuthContext::ownerScope(), $status ?: null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    #[OA\Get(
        path: '/companies/{id}',
        summary: 'Company detail',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The company'),
            new OA\Response(response: 404, description: 'COMPANY_NOT_FOUND — missing, or outside the caller\'s ownership scope', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show($id)
    {
        return $this->ok($this->companies->getCompany((int) $id, AuthContext::ownerScope()));
    }

    #[OA\Put(
        path: '/companies/{id}',
        summary: 'Update a company',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'industry', type: 'string', nullable: true),
                new OA\Property(property: 'website', type: 'string', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'status', type: 'string', enum: ['PROSPECT', 'CUSTOMER', 'CHURNED']),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated company'),
            new OA\Response(response: 404, description: 'COMPANY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function update($id)
    {
        $data = $this->validateBody('companyUpdate');

        $company = $this->companies->updateCompany((int) $id, $data, AuthContext::ownerScope());

        return $this->ok($company);
    }

    #[OA\Delete(
        path: '/companies/{id}',
        summary: 'Soft-delete a company',
        tags: ['Companies'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Company deleted'),
            new OA\Response(response: 404, description: 'COMPANY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'COMPANY_HAS_OPEN_DEALS — blocked while a non-closed deal references it', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function delete($id)
    {
        $this->companies->deleteCompany((int) $id, AuthContext::ownerScope());

        return $this->ok(['message' => 'Company deleted.']);
    }
}
