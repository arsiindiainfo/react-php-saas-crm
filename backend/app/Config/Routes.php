<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Public — no auth required (§12.1)
$routes->group('api/v1', ['namespace' => 'App\Controllers'], static function ($routes): void {
    $routes->post('auth/login', 'AuthController::login', ['filter' => 'rateLimit:10']);
    $routes->post('auth/refresh', 'AuthController::refresh');
});

// Generated OpenAPI spec (§26) — non-production only, see OpenApiController.
$routes->get('api/docs', 'OpenApiController::index', ['namespace' => 'App\Controllers']);

// Everything else requires a valid access token; OwnershipScopeFilter always
// runs right after so every Controller can rely on AuthContext::ownerScope().
$routes->group(
    'api/v1',
    ['namespace' => 'App\Controllers', 'filter' => ['jwtAuth', 'ownershipScope']],
    static function ($routes): void {
        $routes->post('auth/logout', 'AuthController::logout');
        $routes->get('users/me', 'AuthController::me');

        $routes->group('users', ['filter' => 'role:ADMIN'], static function ($routes): void {
            $routes->get('/', 'UsersController::index');
            $routes->post('/', 'UsersController::create');
            $routes->put('(:num)', 'UsersController::update/$1');
        });

        $routes->group('companies', static function ($routes): void {
            $routes->get('/', 'CompaniesController::index');
            $routes->post('/', 'CompaniesController::create');
            $routes->get('(:num)', 'CompaniesController::show/$1');
            $routes->put('(:num)', 'CompaniesController::update/$1');
            $routes->delete('(:num)', 'CompaniesController::delete/$1');
        });

        $routes->group('contacts', static function ($routes): void {
            $routes->get('/', 'ContactsController::index');
            $routes->post('/', 'ContactsController::create');
            $routes->get('(:num)', 'ContactsController::show/$1');
            $routes->put('(:num)', 'ContactsController::update/$1');
            $routes->delete('(:num)', 'ContactsController::delete/$1');
        });

        $routes->group('leads', static function ($routes): void {
            $routes->get('/', 'LeadsController::index');
            $routes->post('/', 'LeadsController::create');
            $routes->get('(:num)', 'LeadsController::show/$1');
            $routes->put('(:num)', 'LeadsController::update/$1');
            $routes->post('(:num)/convert', 'LeadsController::convert/$1');
            $routes->post('(:num)/disqualify', 'LeadsController::disqualify/$1');
        });

        $routes->group('deals', static function ($routes): void {
            $routes->get('/', 'DealsController::index');
            $routes->post('/', 'DealsController::create');
            $routes->get('(:num)', 'DealsController::show/$1');
            $routes->put('(:num)', 'DealsController::update/$1');
            $routes->post('(:num)/change-stage', 'DealsController::changeStage/$1');
        });

        $routes->group('tasks', static function ($routes): void {
            $routes->get('/', 'TasksController::index');
            $routes->post('/', 'TasksController::create');
            $routes->get('(:num)', 'TasksController::show/$1');
            $routes->put('(:num)', 'TasksController::update/$1');
            $routes->post('(:num)/complete', 'TasksController::complete/$1');
        });

        $routes->group('activities', static function ($routes): void {
            $routes->get('/', 'ActivitiesController::index');
            $routes->post('/', 'ActivitiesController::create');
        });

        $routes->get('dashboard/summary', 'DashboardController::summary');

        $routes->group('reports', static function ($routes): void {
            $routes->get('pipeline-by-stage', 'ReportsController::pipelineByStage');
            $routes->get('monthly-sales', 'ReportsController::monthlySales');
            $routes->get('salesperson-performance', 'ReportsController::salespersonPerformance');
        });

        $routes->get('audit-logs', 'AuditLogController::index', ['filter' => 'role:ADMIN']);
    },
);
