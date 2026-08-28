<?php

namespace App\Controllers;

use App\Services\AuthService;

/**
 * ADMIN-only team management (§15) — RoleFilter already enforces the role
 * before any of these methods run (see Config\Routes).
 */
class UsersController extends BaseApiController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function index()
    {
        return $this->ok($this->auth->listUsers());
    }

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
