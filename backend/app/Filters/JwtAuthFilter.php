<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use App\Libraries\AuthContext;
use App\Libraries\JwtService;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Verifies the Bearer access token and stashes the resolved User in
 * AuthContext for the rest of the request (§5, §15). Applied to every
 * route except /auth/login and /auth/refresh (§12.1).
 */
class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');

        if ($header === '' || ! str_starts_with($header, 'Bearer ')) {
            throw new UnauthorizedException();
        }

        $token   = substr($header, 7);
        $payload = (new JwtService())->decode($token);

        if ($payload === null || ! isset($payload['sub'])) {
            throw new UnauthorizedException();
        }

        $user = (new UserModel())->find((int) $payload['sub']);

        if ($user === null || $user->status !== 'ACTIVE') {
            throw new UnauthorizedException();
        }

        AuthContext::setUser($user);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
