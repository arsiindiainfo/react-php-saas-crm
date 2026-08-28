<?php

namespace App\Filters;

use App\Exceptions\UnauthorizedException;
use App\Libraries\AuthContext;
use App\Libraries\ExceptionResponder;
use App\Libraries\JwtService;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Verifies the Bearer access token and stashes the resolved User in
 * AuthContext for the rest of the request (§5, §15). Applied to every
 * route except /auth/login and /auth/refresh (§12.1).
 *
 * Returns the §13.3 error envelope directly on failure rather than throwing
 * — a `before` filter runs ahead of the Controller, so relying on the
 * global exception handler (or BaseApiController::_remap()) would miss it;
 * see ExceptionResponder's docblock.
 */
class JwtAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        try {
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

            return null;
        } catch (\Throwable $e) {
            return ExceptionResponder::toResponse($e, Services::response());
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
