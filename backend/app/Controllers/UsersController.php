<?php

namespace App\Controllers;

use App\Services\AuthService;
use OpenApi\Attributes as OA;

/**
 * ADMIN-only team management (§15) — RoleFilter already enforces the role
 * before any of these methods run (see Config\Routes).
 */
#[OA\Tag(name: 'Users')]
class UsersController extends BaseApiController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    #[OA\Get(
        path: '/users',
        summary: 'List the team',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Every user, ordered by name'),
            new OA\Response(response: 403, description: 'FORBIDDEN_ROLE — ADMIN only', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function index()
    {
        return $this->ok($this->auth->listUsers());
    }

    #[OA\Post(
        path: '/users',
        summary: 'Invite a user',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['name', 'email', 'role'],
            properties: [
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'role', type: 'string', enum: ['ADMIN', 'SALES_MANAGER', 'SALES_REP']),
                new OA\Property(property: 'managerId', type: 'integer', nullable: true, description: 'Required when role is SALES_REP'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'User invited'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR or MANAGER_REQUIRED', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 403, description: 'FORBIDDEN_ROLE', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 409, description: 'DUPLICATE_EMAIL', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function create()
    {
        $data = $this->validateBody('userInvite');

        $user = $this->auth->inviteUser(
            $data['name'],
            $data['email'],
            $data['role'],
            $data['managerId'] ?? null,
            $this->authUser()->id,
        );

        return $this->created($user);
    }

    #[OA\Put(
        path: '/users/{id}',
        summary: 'Update a user\'s role, status, or manager',
        tags: ['Users'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'role', type: 'string', enum: ['ADMIN', 'SALES_MANAGER', 'SALES_REP']),
                new OA\Property(property: 'status', type: 'string', enum: ['ACTIVE', 'DISABLED']),
                new OA\Property(property: 'managerId', type: 'integer', nullable: true),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated user'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 403, description: 'FORBIDDEN_ROLE — e.g. disabling your own account', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'USER_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function update($id)
    {
        $data = $this->validateBody('userUpdate');

        $fields = [];
        if (isset($data['role'])) {
            $fields['role'] = $data['role'];
        }
        if (isset($data['status'])) {
            $fields['status'] = $data['status'];
        }
        if (isset($data['managerId'])) {
            $fields['manager_id'] = $data['managerId'];
        }

        $user = $this->auth->updateUser((int) $id, $fields, $this->authUser());

        return $this->ok($user);
    }
}
