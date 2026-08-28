<?php

namespace App\Models;

use App\Entities\Contact;
use App\Libraries\ListQuery;
use App\Models\Concerns\SearchesRecords;
use CodeIgniter\Model;

class ContactModel extends Model
{
    use SearchesRecords;

    protected $table          = 'contacts';
    protected $primaryKey     = 'id';
    protected $returnType     = Contact::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['company_id', 'first_name', 'last_name', 'email', 'phone', 'job_title', 'owner_id'];

    /**
     * @param list<int>|null $ownerScope
     */
    public function findScoped(int $id, ?array $ownerScope): ?Contact
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
    public function list(ListQuery $query, ?array $ownerScope, ?int $companyId): array
    {
        return $this->searchRecords('contacts', $query, $ownerScope, ['companyId' => $companyId]);
    }

    /**
     * @return array{contactId:?int,statusCode:string,message:string}
     */
    public function createViaProcedure(
        int $companyId,
        string $firstName,
        string $lastName,
        ?string $email,
        ?string $phone,
        ?string $jobTitle,
        int $ownerId,
        ?array $ownerScope,
    ): array {
        $this->db->query(
            'CALL sp_contact_create(?, ?, ?, ?, ?, ?, ?, ?, @o_id, @o_status, @o_message)',
            [$companyId, $firstName, $lastName, $email, $phone, $jobTitle, $ownerId, $ownerScope === null ? null : implode(',', $ownerScope)],
        );
        $row = $this->db->query('SELECT @o_id AS id, @o_status AS statusCode, @o_message AS message')->getRowArray();

        return [
            'contactId'  => $row['id'] !== null ? (int) $row['id'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }
}
