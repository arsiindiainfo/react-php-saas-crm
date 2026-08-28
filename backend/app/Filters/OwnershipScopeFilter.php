<?php

namespace App\Filters;

use App\Libraries\AuthContext;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Resolves the caller's visible owner_id set once per request (§6.2):
 * `[self]` for a rep, `[self, ...directReports]` for a manager (one query
 * against `users.manager_id`), or `null` (unrestricted) for an ADMIN.
 * Registered from Phase 0 onward so every procedure signature is settled
 * early; the guardrail tests proving it actually restricts visibility land
 * in Phase 3 alongside the Companies/Leads/Deals Services that consume it.
 */
class OwnershipScopeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = AuthContext::user();

        if ($user === null) {
            return;
        }

        if ($user->isAdmin()) {
            AuthContext::setOwnerScope(null);

            return;
        }

        if ($user->isManager()) {
            $reportIds = (new UserModel())->directReportIds($user->id);
            AuthContext::setOwnerScope([$user->id, ...$reportIds]);

            return;
        }

        AuthContext::setOwnerScope([$user->id]);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
