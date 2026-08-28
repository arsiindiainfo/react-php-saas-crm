<?php

namespace App\Services;

use App\Entities\Lead;
use App\Exceptions\AlreadyConvertedException;
use App\Exceptions\ApiException;
use App\Exceptions\InvalidTransitionException;
use App\Exceptions\NotQualifiedException;
use App\Exceptions\RecordNotFoundException;
use App\Libraries\ListQuery;
use App\Models\LeadModel;

/**
 * Owns the lead-to-deal conversion workflow (§9) — the project's flagship
 * feature (§8.2).
 */
class LeadService
{
    private LeadModel $leads;

    public function __construct()
    {
        $this->leads = new LeadModel();
    }

    public function createLead(
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $phone,
        ?string $companyName,
        string $source,
        int $ownerId,
    ): Lead {
        $result = $this->leads->createViaProcedure($firstName, $lastName, $email, $phone, $companyName, $source, $ownerId);

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        return $this->leads->find($result['leadId']);
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listLeads(ListQuery $query, ?array $ownerScope, ?string $status): array
    {
        return $this->leads->list($query, $ownerScope, $status);
    }

    public function getLead(int $id, ?array $ownerScope): Lead
    {
        $lead = $this->leads->findScoped($id, $ownerScope);

        if ($lead === null) {
            throw new RecordNotFoundException('lead', 'LEAD_NOT_FOUND');
        }

        return $lead;
    }

    public function updateLead(int $id, array $fields, ?array $ownerScope): Lead
    {
        $lead = $this->getLead($id, $ownerScope);

        $allowed = array_intersect_key($fields, array_flip(['first_name', 'last_name', 'email', 'phone', 'company_name', 'source', 'status']));

        if (isset($allowed['status']) && in_array($lead->status, ['CONVERTED', 'DISQUALIFIED'], true)) {
            throw new InvalidTransitionException('This lead has already reached a terminal status.');
        }

        $this->leads->update($id, $allowed);

        return $this->leads->find($id);
    }

    /**
     * @return array{company:\App\Entities\Company,contact:\App\Entities\Contact,deal:\App\Entities\Deal}
     */
    public function convert(
        int $leadId,
        ?array $ownerScope,
        ?int $existingCompanyId,
        string $dealName,
        float $dealValue,
        int $convertedBy,
    ): array {
        $result = $this->leads->convertViaProcedure($leadId, $ownerScope, $existingCompanyId, $dealName, $dealValue, $convertedBy);

        match ($result['statusCode']) {
            'NOT_FOUND'          => throw new RecordNotFoundException('lead', 'LEAD_NOT_FOUND'),
            'ALREADY_CONVERTED'  => throw new AlreadyConvertedException(),
            'NOT_QUALIFIED'      => throw new NotQualifiedException(),
            'COMPANY_NOT_FOUND'  => throw new RecordNotFoundException('company', 'COMPANY_NOT_FOUND'),
            'OK'                 => null,
            default              => throw new ApiException($result['message'], 500, 'INTERNAL_ERROR'),
        };

        return [
            'company' => (new \App\Models\CompanyModel())->find($result['companyId']),
            'contact' => (new \App\Models\ContactModel())->find($result['contactId']),
            'deal'    => (new \App\Models\DealModel())->find($result['dealId']),
        ];
    }

    public function disqualify(int $leadId, ?array $ownerScope, string $reason, int $disqualifiedBy): Lead
    {
        $result = $this->leads->disqualifyViaProcedure($leadId, $ownerScope, $reason, $disqualifiedBy);

        match ($result['statusCode']) {
            'NOT_FOUND'          => throw new RecordNotFoundException('lead', 'LEAD_NOT_FOUND'),
            'INVALID_TRANSITION' => throw new InvalidTransitionException($result['message']),
            'OK'                 => null,
            default              => throw new ApiException($result['message'], 500, 'INTERNAL_ERROR'),
        };

        return $this->leads->find($leadId);
    }
}
