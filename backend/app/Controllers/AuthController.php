<?php

namespace App\Controllers;

use App\Services\AuthService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth')]
class AuthController extends BaseApiController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    #[OA\Post(
        path: '/auth/login',
        summary: 'Authenticate with email + password',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['email', 'password', 'recaptchaToken'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string'),
                new OA\Property(property: 'recaptchaToken', type: 'string', description: 'Google reCAPTCHA v2 response token'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Access/refresh tokens + user profile'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR, RECAPTCHA_REQUIRED, or RECAPTCHA_FAILED', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 401, description: 'UNAUTHORIZED — invalid credentials or disabled account', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 429, description: 'RATE_LIMITED — 10/min/IP', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 503, description: 'RECAPTCHA_UNAVAILABLE — could not reach Google to verify', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function login()
    {
        $data = $this->validateBody('authLogin');

        $result = $this->auth->login($data['email'], $data['password']);

        return $this->ok($this->tokenResponse($result));
    }

    #[OA\Post(
        path: '/auth/refresh',
        summary: 'Rotate an access/refresh token pair',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['refreshToken'],
            properties: [new OA\Property(property: 'refreshToken', type: 'string')],
        )),
        responses: [
            new OA\Response(response: 200, description: 'New access/refresh tokens + user profile'),
            new OA\Response(response: 401, description: 'UNAUTHORIZED — refresh token invalid, expired, or already used', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function refresh()
    {
        $data = $this->validateBody('authRefresh');

        $result = $this->auth->refresh($data['refreshToken']);

        return $this->ok($this->tokenResponse($result));
    }

    #[OA\Post(
        path: '/auth/logout',
        summary: 'Revoke a refresh token',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['refreshToken'],
            properties: [new OA\Property(property: 'refreshToken', type: 'string')],
        )),
        responses: [new OA\Response(response: 200, description: 'Logged out')],
    )]
    public function logout()
    {
        $data = $this->validateBody('authRefresh');
        $this->auth->logout($data['refreshToken']);

        return $this->ok(['message' => 'Logged out.']);
    }

    #[OA\Get(
        path: '/users/me',
        summary: 'Current authenticated profile',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'The caller\'s own User record'),
            new OA\Response(response: 401, description: 'UNAUTHORIZED', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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
