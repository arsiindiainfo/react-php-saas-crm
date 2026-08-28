<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id';
    protected $returnType    = User::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['name', 'email', 'password_hash', 'role', 'manager_id', 'status'];

    /**
     * @return array{id:int,name:string,passwordHash:string,role:string,managerId:?int,status:string}|null
     */
    public function authenticate(string $email): ?array
    {
        $this->db->query('CALL sp_user_authenticate(?, @o_id, @o_name, @o_hash, @o_role, @o_manager, @o_status)', [$email]);
        $row = $this->db->query('SELECT @o_id AS id, @o_name AS name, @o_hash AS hash, @o_role AS role, @o_manager AS managerId, @o_status AS status')
            ->getRowArray();

        if ($row === null || $row['id'] === null) {
            return null;
        }

        return [
            'id'           => (int) $row['id'],
            'name'         => $row['name'],
            'passwordHash' => $row['hash'],
            'role'         => $row['role'],
            'managerId'    => $row['managerId'] !== null ? (int) $row['managerId'] : null,
            'status'       => $row['status'],
        ];
    }

    /**
     * @return array{userId:?int,statusCode:string,message:string}
     */
    public function invite(string $name, string $email, string $passwordHash, string $role, ?int $managerId, int $invitedBy): array
    {
        $this->db->query(
            'CALL sp_user_invite(?, ?, ?, ?, ?, ?, @o_id, @o_status, @o_message)',
            [$name, $email, $passwordHash, $role, $managerId, $invitedBy],
        );
        $row = $this->db->query('SELECT @o_id AS id, @o_status AS statusCode, @o_message AS message')->getRowArray();

        return [
            'userId'     => $row['id'] !== null ? (int) $row['id'] : null,
            'statusCode' => $row['statusCode'],
            'message'    => $row['message'],
        ];
    }

    /**
     * All user ids reporting (directly) to the given manager.
     *
     * @return list<int>
     */
    public function directReportIds(int $managerId): array
    {
        return array_map(
            static fn (User $row) => $row->id,
            $this->select('id')->where('manager_id', $managerId)->findAll(),
        );
    }
}
