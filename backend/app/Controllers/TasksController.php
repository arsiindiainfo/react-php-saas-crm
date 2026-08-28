<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\TaskService;

class TasksController extends BaseApiController
{
    private TaskService $tasks;

    private const SORTABLE = ['subject', 'dueDate', 'priority', 'createdAt'];

    public function __construct()
    {
        $this->tasks = new TaskService();
    }

    public function create()
    {
        $data = $this->validateBody('taskCreate');

        $task = $this->tasks->createTask(
            $data['subject'],
            $data['dueDate'] ?? null,
            $data['priority'],
            $data['relatedToType'] ?? null,
            isset($data['relatedToId']) ? (int) $data['relatedToId'] : null,
            isset($data['assignedTo']) ? (int) $data['assignedTo'] : $this->authUser()->id,
            $this->authUser()->id,
            AuthContext::ownerScope(),
        );

        return $this->created($task);
    }

    public function index()
    {
        $query      = $this->parseListQuery(self::SORTABLE, 'dueDate');
        $assignedTo = $this->request->getGet('assignedTo');
        $status     = $this->request->getGet('status'); // OPEN|DONE|ALL, default OPEN (§22.7)
        $status     = match ($status) {
            'OPEN', 'DONE' => $status,
            'ALL'          => null,
            default        => 'OPEN',
        };

        $result = $this->tasks->listTasks(
            $query,
            AuthContext::ownerScope(),
            $assignedTo !== null ? (int) $assignedTo : $this->authUser()->id,
            $status,
        );

        return $this->okPaginated($result['rows'], $query->page, $query->limit, $result['total']);
    }

    public function show($id)
    {
        return $this->ok($this->tasks->getTask((int) $id, AuthContext::ownerScope()));
    }

    public function update($id)
    {
        $data = $this->validateBody('taskUpdate');

        $fields = [];
        foreach (['subject' => 'subject', 'dueDate' => 'due_date', 'priority' => 'priority', 'assignedTo' => 'assigned_to'] as $in => $column) {
            if (array_key_exists($in, $data)) {
                $fields[$column] = $data[$in];
            }
        }

        return $this->ok($this->tasks->updateTask((int) $id, $fields, AuthContext::ownerScope()));
    }

    public function complete($id)
    {
        return $this->ok($this->tasks->completeTask((int) $id, AuthContext::ownerScope(), $this->authUser()->id));
    }
}
