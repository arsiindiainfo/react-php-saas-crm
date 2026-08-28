<?php

namespace App\Models;

use App\Entities\Task;
use App\Libraries\ListQuery;
use App\Models\Concerns\SearchesRecords;
use CodeIgniter\Model;

class TaskModel extends Model
{
    use SearchesRecords;

    protected $table          = 'tasks';
    protected $primaryKey     = 'id';
    protected $returnType     = Task::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'subject', 'due_date', 'priority', 'related_to_type', 'related_to_id', 'assigned_to', 'created_by',
    ];

    /**
     * @param list<int>|null $ownerScope owner scope for tasks is keyed on `assigned_to`
     */
    public function findScoped(int $id, ?array $ownerScope): ?Task
    {
        $builder = $this->where('id', $id);

        if ($ownerScope !== null) {
            $scopeCsv = $this->db->escape(implode(',', $ownerScope));
            $builder->where("FIND_IN_SET(assigned_to, {$scopeCsv}) > 0", null, false);
        }

        return $builder->first();
    }

    /**
     * @param list<int>|null $ownerScope
     * @param string|null    $status     'OPEN'|'DONE'|null (§22.7 default: OPEN)
     *
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function list(ListQuery $query, ?array $ownerScope, ?int $assignedTo, ?string $status): array
    {
        return $this->searchRecords('tasks', $query, $ownerScope, ['assignedTo' => $assignedTo, 'status' => $status]);
    }

    /**
     * @param list<int>|null $ownerScope
     *
     * @return array{statusCode:string,message:string}
     */
    public function completeViaProcedure(int $taskId, ?array $ownerScope, int $completedBy): array
    {
        $this->db->query(
            'CALL sp_task_complete(?, ?, ?, @o_status, @o_message)',
            [$taskId, $ownerScope === null ? null : implode(',', $ownerScope), $completedBy],
        );
        $row = $this->db->query('SELECT @o_status AS statusCode, @o_message AS message')->getRowArray();

        return ['statusCode' => $row['statusCode'], 'message' => $row['message']];
    }
}
