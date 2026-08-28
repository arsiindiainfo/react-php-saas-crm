<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\ReportingService;

class DashboardController extends BaseApiController
{
    public function summary()
    {
        return $this->ok((new ReportingService())->dashboardSummary(AuthContext::ownerScope()));
    }
}
