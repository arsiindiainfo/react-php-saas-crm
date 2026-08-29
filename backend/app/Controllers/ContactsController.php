<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\CompanyService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Contacts')]
class ContactsController extends BaseApiController
{
    private CompanyService $companies;

    private const SORTABLE = ['firstName', 'lastName', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    #[OA\Post(
        path: '/contacts',
        summary: 'Create a contact under a company',
        tags: ['Contacts'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['companyId', 'firstName', 'lastName'],
            properties: [
                new OA\Property(property: 'companyId', type: 'integer'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'jobTitle', type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Contact created'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'COMPANY_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function create()
    {
        $data = $this->validateBody('contactCreate');

        $contact = $this->companies->createContact(
            (int) $data['companyId'],
            $data['firstName'],
            $data['lastName'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['jobTitle'] ?? null,
            $this->authUser()->id,
            AuthContext::ownerScope(),
        );

        return $this->created($contact);
    }

    #[OA\Get(
        path: '/contacts',
        summary: 'List contacts — the shared §10 list contract',
        tags: ['Contacts'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: self::SORTABLE)),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'companyId', in: 'query', schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated, ownership-scoped list of contacts')],
    )]
    public function index()
    {
        $query     = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $companyId = $this->request->getGet('companyId');

        $result = $this->companies->listContacts($query, AuthContext::ownerScope(), $companyId !== null ? (int) $companyId : null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    #[OA\Get(
        path: '/contacts/{id}',
        summary: 'Contact detail',
        tags: ['Contacts'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The contact'),
            new OA\Response(response: 404, description: 'CONTACT_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show($id)
    {
        return $this->ok($this->companies->getContact((int) $id, AuthContext::ownerScope()));
    }

    #[OA\Put(
        path: '/contacts/{id}',
        summary: 'Update a contact',
        tags: ['Contacts'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
                new OA\Property(property: 'email', type: 'string', nullable: true),
                new OA\Property(property: 'phone', type: 'string', nullable: true),
                new OA\Property(property: 'jobTitle', type: 'string', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated contact'),
            new OA\Response(response: 404, description: 'CONTACT_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function update($id)
    {
        $data = $this->validateBody('contactUpdate');

        $fields = [];
        foreach (['firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'jobTitle' => 'job_title'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->companies->updateContact((int) $id, $fields, AuthContext::ownerScope()));
    }

    #[OA\Delete(
        path: '/contacts/{id}',
        summary: 'Soft-delete a contact',
        tags: ['Contacts'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Contact deleted'),
            new OA\Response(response: 404, description: 'CONTACT_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function delete($id)
    {
        $this->companies->deleteContact((int) $id, AuthContext::ownerScope());

        return $this->ok(['message' => 'Contact deleted.']);
    }
}
