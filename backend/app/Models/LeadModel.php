<?php

namespace App\Models;

use App\Entities\Lead;
use App\Libraries\ListQuery;
use App\Models\Concerns\SearchesRecords;
use CodeIgniter\Model;

class LeadModel extends Model
{
    use SearchesRecords;

    protected $table          = 'leads';
    protected $primaryKey     = 'id';
    protected $returnType     = Lead::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'first_name', 'last_name', 'email', 'phone', 'company_name', 'source', 'status', 'owner_id',
    ];

    /**
     * @param list<int>|null $ownerScope
     */
    public function findScoped(int $id, ?array $ownerScope): ?Lead
    {
        $builder = $this->where('id', $id);

        if ($ownerScope !== null) {
            $scopeCsv = $this->db->escape(implode(',', $ownerScope));
            $builder->where("FIND_IN_SET(owner_id, {$scopeCsv}) > 0", null, false);
        }

        return $builder->first();
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function list(ListQuery $query, ?array $ownerScope, ?string $status): array
    {
        return $this->searchRecords('leads', $query, $ownerScope, ['status' => $status]);
    }

    /**
     * @return array{leadId:?int,statusCode:string,message:string}
     */
    public function createViaProcedure(
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $phone,
        ?string $companyName,
        string $source,
        int $ownerId,
    ): array {
        $this->db->query(
            'CALL sp_lead_create(?, ?, ?, ?, ?, ?, ?, @o_id, @o_status, @o_message)',
            [$firstName, $lastName, $email, $phone, $companyName, $source, $ownerId],
        );
        $row = $this->db->query('SELECT @o_id AS id, @o_status AS statusCode, @o_message AS message')->getRowArray();

        return [
            'leadId'     => $row['id'] !== null ? (int) $row['id'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{companyId:?int,contactId:?int,dealId:?int,statusCode:string,message:string}
     */
    public function convertViaProcedure(
        int $leadId,
        ?array $ownerScope,
        ?int $existingCompanyId,
        string $dealName,
        float $dealValue,
        int $convertedBy,
    ): array {
        $this->db->query(
            'CALL sp_lead_convert(?, ?, ?, ?, ?, ?, @o_company, @o_contact, @o_deal, @o_status, @o_message)',
            [$leadId, $ownerScope === null ? null : implode(',', $ownerScope), $existingCompanyId, $dealName, $dealValue, $convertedBy],
        );
        $row = $this->db->query(
            'SELECT @o_company AS companyId, @o_contact AS contactId, @o_deal AS dealId, @o_status AS statusCode, @o_message AS message',
        )->getRowArray();

        return [
            'companyId'  => $row['companyId'] !== null ? (int) $row['companyId'] : null,
            'contactId'  => $row['contactId'] !== null ? (int) $row['contactId'] : null,
            'dealId'     => $row['dealId'] !== null ? (int) $row['dealId'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{statusCode:string,message:string}
     */
    public function disqualifyViaProcedure(int $leadId, ?array $ownerScope, string $reason, int $disqualifiedBy): array
    {
        $this->db->query(
            'CALL sp_lead_disqualify(?, ?, ?, ?, @o_status, @o_message)',
            [$leadId, $ownerScope === null ? null : implode(',', $ownerScope), $reason, $disqualifiedBy],
        );
        $row = $this->db->query('SELECT @o_status AS statusCode, @o_message AS message')->getRowArray();

        return ['statusCode' => $row['statusCode'], 'message' => $row['message']];
    }
}
