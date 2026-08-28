<?php

namespace App\Models;

use App\Entities\Deal;
use App\Libraries\ListQuery;
use App\Models\Concerns\SearchesRecords;
use CodeIgniter\Model;

class DealModel extends Model
{
    use SearchesRecords;

    protected $table          = 'deals';
    protected $primaryKey     = 'id';
    protected $returnType     = Deal::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'company_id', 'contact_id', 'lead_id', 'name', 'value_amount',
        'expected_close_date', 'stage', 'lost_reason', 'owner_id',
    ];

    /**
     * @param list<int>|null $ownerScope
     */
    public function findScoped(int $id, ?array $ownerScope): ?Deal
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
    public function list(ListQuery $query, ?array $ownerScope, ?string $stage, ?int $companyId): array
    {
        return $this->searchRecords('deals', $query, $ownerScope, ['stage' => $stage, 'companyId' => $companyId]);
    }

    /**
     * @return array{dealId:?int,statusCode:string,message:string}
     */
    public function createViaProcedure(
        int $companyId,
        ?int $contactId,
        ?array $ownerScope,
        string $name,
        float $valueAmount,
        ?string $expectedCloseDate,
        int $ownerId,
    ): array {
        $this->db->query(
            'CALL sp_deal_create(?, ?, ?, ?, ?, ?, ?, @o_id, @o_status, @o_message)',
            [$companyId, $contactId, $ownerScope === null ? null : implode(',', $ownerScope), $name, $valueAmount, $expectedCloseDate, $ownerId],
        );
        $row = $this->db->query('SELECT @o_id AS id, @o_status AS statusCode, @o_message AS message')->getRowArray();

        return [
            'dealId'     => $row['id'] !== null ? (int) $row['id'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{statusCode:string,message:string}
     */
    public function changeStageViaProcedure(int $dealId, ?array $ownerScope, string $newStage, ?string $lostReason, int $changedBy): array
    {
        $this->db->query(
            'CALL sp_deal_change_stage(?, ?, ?, ?, ?, @o_status, @o_message)',
            [$dealId, $ownerScope === null ? null : implode(',', $ownerScope), $newStage, $lostReason, $changedBy],
        );
        $row = $this->db->query('SELECT @o_status AS statusCode, @o_message AS message')->getRowArray();

        return ['statusCode' => $row['statusCode'], 'message' => $row['message']];
    }
}
