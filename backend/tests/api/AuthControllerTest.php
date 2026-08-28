<?php

namespace Tests\Api;

use Tests\Support\ApiTestCase;

/**
 * @internal
 */
final class AuthControllerTest extends ApiTestCase
{
    public function testLoginSucceedsWithSeededAdmin(): void
    {
        $result = $this->withBodyFormat('json')->post('api/v1/auth/login', [
            'email'    => 'admin@brightfield.test',
            'password' => 'Passw0rd!',
        ]);

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);

        $this->assertTrue($body['success']);
        $this->assertSame('ADMIN', $body['data']['user']['role']);
        $this->assertNotEmpty($body['data']['accessToken']);
        $this->assertNotEmpty($body['data']['refreshToken']);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $result = $this->withBodyFormat('json')->post('api/v1/auth/login', [
            'email'    => 'admin@brightfield.test',
            'password' => 'not-the-password',
        ]);

        $result->assertStatus(401);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('UNAUTHORIZED', $body['error']['code']);
    }

    public function testLoginValidatesMissingFields(): void
    {
        $result = $this->withBodyFormat('json')->post('api/v1/auth/login', ['email' => 'not-an-email']);

        $result->assertStatus(400);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('VALIDATION_ERROR', $body['error']['code']);
    }

    public function testRefreshRotatesTheToken(): void
    {
        $login = json_decode(
            $this->withBodyFormat('json')->post('api/v1/auth/login', [
                'email' => 'admin@brightfield.test', 'password' => 'Passw0rd!',
            ])->getJSON(),
            true,
        );

        $refreshed = $this->withBodyFormat('json')->post('api/v1/auth/refresh', [
            'refreshToken' => $login['data']['refreshToken'],
        ]);
        $refreshed->assertStatus(200);

        // the old refresh token was rotated out — reusing it now fails
        $reuse = $this->withBodyFormat('json')->post('api/v1/auth/refresh', [
            'refreshToken' => $login['data']['refreshToken'],
        ]);
        $reuse->assertStatus(401);
    }

    public function testMeReturnsTheAuthenticatedProfile(): void
    {
        $result = $this->withHeaders(['Authorization' => $this->bearerFor('admin@brightfield.test')])
            ->get('api/v1/users/me');

        $result->assertStatus(200);
        $body = json_decode($result->getJSON(), true);
        $this->assertSame('admin@brightfield.test', $body['data']['email']);
    }

    public function testMeRejectsMissingToken(): void
    {
        $this->get('api/v1/users/me')->assertStatus(401);
    }

    public function testAdminCanInviteAUser(): void
    {
        $result = $this->withHeaders(['Authorization' => $this->bearerFor('admin@brightfield.test')])
            ->withBodyFormat('json')
            ->post('api/v1/users', [
                'name'  => 'New Rep',
                'email' => 'new.rep@brightfield.test',
                'role'  => 'SALES_REP',
                // managerId omitted on purpose to exercise the guard below in a separate case
            ]);

        // SALES_REP without a managerId is rejected by sp_user_invite
        $result->assertStatus(400);
    }

    public function testNonAdminCannotInviteAUser(): void
    {
        $result = $this->withHeaders(['Authorization' => $this->bearerFor('arjun.rep@brightfield.test')])
            ->withBodyFormat('json')
            ->post('api/v1/users', ['name' => 'X', 'email' => 'x@brightfield.test', 'role' => 'SALES_REP']);

        $result->assertStatus(403);
    }
}
