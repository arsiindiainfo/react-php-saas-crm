<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\LeadService;

class LeadsController extends BaseApiController
{
    private LeadService $leads;

    private const SORTABLE = ['firstName', 'lastName', 'status', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->leads = new LeadService();
    }

    public function create()
    {
        $data = $this->validateBody('leadCreate');

        $lead = $this->leads->createLead(
            $data['firstName'],
            $data['lastName'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['companyName'] ?? null,
            $data['source'],
            $this->authUser()->id,
        );

        return $this->created($lead);
    }

    public function index()
    {
        $query  = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $status = $this->request->getGet('status');

        $result = $this->leads->listLeads($query, AuthContext::ownerScope(), $status ?: null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    public function show($id)
    {
        return $this->ok($this->leads->getLead((int) $id, AuthContext::ownerScope()));
    }

    public function update($id)
    {
        $data = $this->validateBody('leadUpdate');

        $fields = [];
        foreach (['firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'companyName' => 'company_name', 'source' => 'source', 'status' => 'status'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->leads->updateLead((int) $id, $fields, AuthContext::ownerScope()));
    }

    public function convert($id)
    {
        $data = $this->validateBody('leadConvert');

        $result = $this->leads->convert(
            (int) $id,
            AuthContext::ownerScope(),
            isset($data['existingCompanyId']) ? (int) $data['existingCompanyId'] : null,
            $data['dealName'],
            (float) ($data['dealValue'] ?? 0),
            $this->authUser()->id,
        );

        return $this->ok($result);
    }

    public function disqualify($id)
    {
        $data = $this->validateBody('leadDisqualify');

        $lead = $this->leads->disqualify((int) $id, AuthContext::ownerScope(), $data['reason'], $this->authUser()->id);

        return $this->ok($lead);
    }
}
