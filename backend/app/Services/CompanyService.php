<?php

namespace App\Services;

use App\Entities\Company;
use App\Entities\Contact;
use App\Exceptions\ApiException;
use App\Exceptions\DuplicateNameException;
use App\Exceptions\RecordNotFoundException;
use App\Libraries\ListQuery;
use App\Models\CompanyModel;
use App\Models\ContactModel;

/**
 * Owns both Company and Contact business logic — the spec models these as
 * one "account" domain (§7.3) rather than splitting into two Services.
 */
class CompanyService
{
    private CompanyModel $companies;
    private ContactModel $contacts;

    public function __construct()
    {
        $this->companies = new CompanyModel();
        $this->contacts  = new ContactModel();
    }

    // ---------------------------------------------------------------
    // Companies
    // ---------------------------------------------------------------

    public function createCompany(string $name, ?string $industry, ?string $website, ?string $phone, int $ownerId): Company
    {
        $result = $this->companies->createViaProcedure($name, $industry, $website, $phone, $ownerId);

        if ($result['statusCode'] === 'DUPLICATE_NAME') {
            throw new DuplicateNameException('A company with this name already exists.');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        return $this->companies->find($result['companyId']);
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listCompanies(ListQuery $query, ?array $ownerScope, ?string $status): array
    {
        return $this->companies->list($query, $ownerScope, $status);
    }

    public function getCompany(int $id, ?array $ownerScope): Company
    {
        $company = $this->companies->findScoped($id, $ownerScope);

        if ($company === null) {
            throw new RecordNotFoundException('company', 'COMPANY_NOT_FOUND');
        }

        return $company;
    }

    public function updateCompany(int $id, array $fields, ?array $ownerScope): Company
    {
        $this->getCompany($id, $ownerScope); // 404s if missing/out of scope

        $allowed = array_intersect_key($fields, array_flip(['name', 'industry', 'website', 'phone', 'status']));
        $this->companies->update($id, $allowed);

        return $this->companies->find($id);
    }

    public function deleteCompany(int $id, ?array $ownerScope): void
    {
        $this->getCompany($id, $ownerScope);

        if ($this->companies->hasOpenDeals($id)) {
            throw new ApiException(
                'This company has open deals and cannot be deleted.',
                409,
                'COMPANY_HAS_OPEN_DEALS',
            );
        }

        $this->companies->delete($id);
    }

    // ---------------------------------------------------------------
    // Contacts
    // ---------------------------------------------------------------

    public function createContact(
        int $companyId,
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $phone,
        ?string $jobTitle,
        int $ownerId,
        ?array $ownerScope,
    ): Contact {
        $result = $this->contacts->createViaProcedure($companyId, $firstName, $lastName, $email, $phone, $jobTitle, $ownerId, $ownerScope);

        if ($result['statusCode'] === 'COMPANY_NOT_FOUND') {
            throw new RecordNotFoundException('company', 'COMPANY_NOT_FOUND');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        return $this->contacts->find($result['contactId']);
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listContacts(ListQuery $query, ?array $ownerScope, ?int $companyId): array
    {
        return $this->contacts->list($query, $ownerScope, $companyId);
    }

    public function getContact(int $id, ?array $ownerScope): Contact
    {
        $contact = $this->contacts->findScoped($id, $ownerScope);

        if ($contact === null) {
            throw new RecordNotFoundException('contact', 'CONTACT_NOT_FOUND');
        }

        return $contact;
    }

    public function updateContact(int $id, array $fields, ?array $ownerScope): Contact
    {
        $this->getContact($id, $ownerScope);

        $allowed = array_intersect_key($fields, array_flip(['first_name', 'last_name', 'email', 'phone', 'job_title']));
        $this->contacts->update($id, $allowed);

        return $this->contacts->find($id);
    }

    public function deleteContact(int $id, ?array $ownerScope): void
    {
        $this->getContact($id, $ownerScope);
        $this->contacts->delete($id);
    }
}
