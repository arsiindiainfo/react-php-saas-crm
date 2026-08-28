<?php

namespace App\Filters;

use App\Exceptions\ForbiddenRoleException;
use App\Libraries\AuthContext;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Route filter argument is the allow-listed role(s), e.g. `role:ADMIN`.
 * Must run after JwtAuthFilter so AuthContext::user() is already set.
 */
class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $allowedRoles = $arguments ?? [];
        $user         = AuthContext::user();

        if ($user === null || ($allowedRoles !== [] && ! in_array($user->role, $allowedRoles, true))) {
            throw new ForbiddenRoleException();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
