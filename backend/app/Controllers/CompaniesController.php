<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\CompanyService;

class CompaniesController extends BaseApiController
{
    private CompanyService $companies;

    private const SORTABLE = ['name', 'industry', 'status', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    public function create()
    {
        $data = $this->validateBody('companyCreate');

        $company = $this->companies->createCompany(
            $data['name'],
            $data['industry'] ?? null,
            $data['website'] ?? null,
            $data['phone'] ?? null,
            $this->authUser()->id,
        );

        return $this->created($company);
    }

    public function index()
    {
        $query = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $status = $this->request->getGet('status');

        $result = $this->companies->listCompanies($query, AuthContext::ownerScope(), $status ?: null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    public function show($id)
    {
        return $this->ok($this->companies->getCompany((int) $id, AuthContext::ownerScope()));
    }

    public function update($id)
    {
        $data = $this->validateBody('companyUpdate');

        $company = $this->companies->updateCompany((int) $id, $data, AuthContext::ownerScope());

        return $this->ok($company);
    }

    public function delete($id)
    {
        $this->companies->deleteCompany((int) $id, AuthContext::ownerScope());

        return $this->ok(['message' => 'Company deleted.']);
    }
}
