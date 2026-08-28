<?php

namespace App\Models;

use App\Entities\Company;
use App\Models\Concerns\SearchesRecords;
use CodeIgniter\Model;

class CompanyModel extends Model
{
    use SearchesRecords;

    protected $table          = 'companies';
    protected $primaryKey     = 'id';
    protected $returnType     = Company::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'industry', 'website', 'phone', 'status', 'owner_id'];

    /**
     * Detail fetch, scoped by ownership (§6.2) — a record outside the
     * caller's visibility is treated identically to a missing one.
     *
     * @param list<int>|null $ownerScope
     */
    public function findScoped(int $id, ?array $ownerScope): ?Company
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
    public function list(\App\Libraries\ListQuery $query, ?array $ownerScope, ?string $status): array
    {
        return $this->searchRecords('companies', $query, $ownerScope, ['status' => $status]);
    }

    /** True while any non-closed deal still references this company (§16). */
    public function hasOpenDeals(int $companyId): bool
    {
        return (new DealModel())
            ->where('company_id', $companyId)
            ->whereNotIn('stage', ['WON', 'LOST'])
            ->countAllResults() > 0;
    }

    /**
     * @return array{companyId:?int,statusCode:string,message:string}
     */
    public function createViaProcedure(string $name, ?string $industry, ?string $website, ?string $phone, int $ownerId): array
    {
        $this->db->query(
            'CALL sp_company_create(?, ?, ?, ?, ?, @o_id, @o_status, @o_message)',
            [$name, $industry, $website, $phone, $ownerId],
        );
        $row = $this->db->query('SELECT @o_id AS id, @o_status AS statusCode, @o_message AS message')->getRowArray();

        return [
            'companyId'  => $row['id'] !== null ? (int) $row['id'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }
}
