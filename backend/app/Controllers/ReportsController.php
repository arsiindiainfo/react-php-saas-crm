<?php

namespace App\Controllers;

use App\Exceptions\ForbiddenRoleException;
use App\Libraries\AuthContext;
use App\Services\ReportingService;

class ReportsController extends BaseApiController
{
    private ReportingService $reporting;

    public function __construct()
    {
        $this->reporting = new ReportingService();
    }

    public function pipelineByStage()
    {
        return $this->ok($this->reporting->pipelineByStage(AuthContext::ownerScope()));
    }

    public function monthlySales()
    {
        return $this->ok($this->reporting->monthlySales(AuthContext::ownerScope()));
    }

    public function salespersonPerformance()
    {
        $user = $this->authUser();

        if (! $user->isAdmin() && ! $user->isManager()) {
            throw new ForbiddenRoleException('Only managers and admins can view the leaderboard.');
        }

        return $this->ok($this->reporting->salespersonPerformance(AuthContext::ownerScope()));
    }
}
