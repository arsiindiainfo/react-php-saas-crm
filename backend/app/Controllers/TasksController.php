<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\TaskService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Tasks')]
class TasksController extends BaseApiController
{
    private TaskService $tasks;

    private const SORTABLE = ['subject', 'dueDate', 'priority', 'createdAt'];

    public function __construct()
    {
        $this->tasks = new TaskService();
    }

    #[OA\Post(
        path: '/tasks',
        summary: 'Create a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['subject', 'priority'],
            properties: [
                new OA\Property(property: 'subject', type: 'string', maxLength: 200),
                new OA\Property(property: 'dueDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'priority', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH']),
                new OA\Property(property: 'relatedToType', type: 'string', enum: ['COMPANY', 'CONTACT', 'LEAD', 'DEAL'], nullable: true),
                new OA\Property(property: 'relatedToId', type: 'integer', nullable: true),
                new OA\Property(property: 'assignedTo', type: 'integer', nullable: true, description: 'Defaults to the caller; a manager may assign to a report'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Task created'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'The related record does not exist or isn\'t visible to the caller', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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

    #[OA\Get(
        path: '/tasks',
        summary: 'List tasks — defaults to the caller\'s own open tasks, due soonest first (§22.7)',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: self::SORTABLE)),
            new OA\Parameter(name: 'direction', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'assignedTo', in: 'query', schema: new OA\Schema(type: 'integer'), description: 'Defaults to the caller'),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', enum: ['OPEN', 'DONE', 'ALL'], default: 'OPEN')),
        ],
        responses: [new OA\Response(response: 200, description: 'Paginated, ownership-scoped list of tasks')],
    )]
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

    #[OA\Get(
        path: '/tasks/{id}',
        summary: 'Task detail',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'The task'),
            new OA\Response(response: 404, description: 'TASK_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function show($id)
    {
        return $this->ok($this->tasks->getTask((int) $id, AuthContext::ownerScope()));
    }

    #[OA\Put(
        path: '/tasks/{id}',
        summary: 'Edit a task',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'subject', type: 'string'),
                new OA\Property(property: 'dueDate', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'priority', type: 'string', enum: ['LOW', 'MEDIUM', 'HIGH']),
                new OA\Property(property: 'assignedTo', type: 'integer'),
            ],
        )),
        responses: [
            new OA\Response(response: 200, description: 'Updated task'),
            new OA\Response(response: 404, description: 'TASK_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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

    #[OA\Post(
        path: '/tasks/{id}/complete',
        summary: 'Mark a task done (idempotent)',
        tags: ['Tasks'],
        security: [['bearerAuth' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Task marked complete (or already was — same response either way)'),
            new OA\Response(response: 404, description: 'TASK_NOT_FOUND', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
    public function complete($id)
    {
        return $this->ok($this->tasks->completeTask((int) $id, AuthContext::ownerScope(), $this->authUser()->id));
    }
}
