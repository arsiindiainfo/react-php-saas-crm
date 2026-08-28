<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController extends BaseApiController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function login()
    {
        $data = $this->validateBody('authLogin');

        $result = $this->auth->login($data['email'], $data['password']);

        return $this->ok($this->tokenResponse($result));
    }

    public function refresh()
    {
        $data = $this->validateBody('authRefresh');

        $result = $this->auth->refresh($data['refreshToken']);

        return $this->ok($this->tokenResponse($result));
    }

    public function logout()
    {
        $data = $this->validateBody('authRefresh');
        $this->auth->logout($data['refreshToken']);

        return $this->ok(['message' => 'Logged out.']);
    }

    public function me()
    {
        return $this->ok($this->authUser());
    }

    private function tokenResponse(array $result): array
    {
        return [
            'accessToken'  => $result['accessToken'],
            'refreshToken' => $result['refreshToken'],
            'user'         => $result['user'],
        ];
    }
}
