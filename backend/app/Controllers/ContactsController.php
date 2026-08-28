<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\CompanyService;

class ContactsController extends BaseApiController
{
    private CompanyService $companies;

    private const SORTABLE = ['firstName', 'lastName', 'createdAt', 'updatedAt'];

    public function __construct()
    {
        $this->companies = new CompanyService();
    }

    public function create()
    {
        $data = $this->validateBody('contactCreate');

        $contact = $this->companies->createContact(
            (int) $data['companyId'],
            $data['firstName'],
            $data['lastName'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['jobTitle'] ?? null,
            $this->authUser()->id,
            AuthContext::ownerScope(),
        );

        return $this->created($contact);
    }

    public function index()
    {
        $query     = $this->parseListQuery(self::SORTABLE, 'createdAt');
        $companyId = $this->request->getGet('companyId');

        $result = $this->companies->listContacts($query, AuthContext::ownerScope(), $companyId !== null ? (int) $companyId : null);

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    public function show($id)
    {
        return $this->ok($this->companies->getContact((int) $id, AuthContext::ownerScope()));
    }

    public function update($id)
    {
        $data = $this->validateBody('contactUpdate');

        $fields = [];
        foreach (['firstName' => 'first_name', 'lastName' => 'last_name', 'email' => 'email', 'phone' => 'phone', 'jobTitle' => 'job_title'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->companies->updateContact((int) $id, $fields, AuthContext::ownerScope()));
    }

    public function delete($id)
    {
        $this->companies->deleteContact((int) $id, AuthContext::ownerScope());

        return $this->ok(['message' => 'Contact deleted.']);
    }
}
