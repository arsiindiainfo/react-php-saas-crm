<?php

namespace App\Services;

use App\Entities\Activity;
use App\Entities\Task;
use App\Exceptions\ApiException;
use App\Exceptions\RecordNotFoundException;
use App\Libraries\ListQuery;
use App\Models\ActivityModel;
use App\Models\CompanyModel;
use App\Models\ContactModel;
use App\Models\DealModel;
use App\Models\LeadModel;
use App\Models\TaskModel;

/**
 * Tasks (follow-up to-dos) and Activities (the unified note/call/email/
 * meeting timeline) — grouped in one Service the same way the spec groups
 * them into one build phase and one API section (§19, Phase 4).
 */
class TaskService
{
    private TaskModel $tasks;
    private ActivityModel $activities;

    public function __construct()
    {
        $this->tasks      = new TaskModel();
        $this->activities = new ActivityModel();
    }

    // ---------------------------------------------------------------
    // Tasks
    // ---------------------------------------------------------------

    public function createTask(
        string $subject,
        ?string $dueDate,
        string $priority,
        ?string $relatedToType,
        ?int $relatedToId,
        int $assignedTo,
        int $createdBy,
        ?array $ownerScope,
    ): Task {
        if ($relatedToType !== null) {
            $this->assertRelatedRecordVisible($relatedToType, $relatedToId, $ownerScope);
        }

        $id = $this->tasks->insert([
            'subject'         => $subject,
            'due_date'        => $dueDate,
            'priority'        => $priority,
            'related_to_type' => $relatedToType,
            'related_to_id'   => $relatedToId,
            'assigned_to'     => $assignedTo,
            'created_by'      => $createdBy,
        ]);

        return $this->tasks->find($id);
    }

    /**
     * @return array{rows:list<array<string,mixed>>,total:int}
     */
    public function listTasks(ListQuery $query, ?array $ownerScope, ?int $assignedTo, ?string $status): array
    {
        return $this->tasks->list($query, $ownerScope, $assignedTo, $status);
    }

    public function getTask(int $id, ?array $ownerScope): Task
    {
        $task = $this->tasks->findScoped($id, $ownerScope);

        if ($task === null) {
            throw new RecordNotFoundException('task', 'TASK_NOT_FOUND');
        }

        return $task;
    }

    public function updateTask(int $id, array $fields, ?array $ownerScope): Task
    {
        $this->getTask($id, $ownerScope);

        $allowed = array_intersect_key($fields, array_flip(['subject', 'due_date', 'priority', 'assigned_to']));
        $this->tasks->update($id, $allowed);

        return $this->tasks->find($id);
    }

    public function completeTask(int $id, ?array $ownerScope, int $completedBy): Task
    {
        $result = $this->tasks->completeViaProcedure($id, $ownerScope, $completedBy);

        if ($result['statusCode'] === 'NOT_FOUND') {
            throw new RecordNotFoundException('task', 'TASK_NOT_FOUND');
        }

        if ($result['statusCode'] !== 'OK') {
            throw new ApiException($result['message'], 500, 'INTERNAL_ERROR');
        }

        return $this->tasks->find($id);
    }

    // ---------------------------------------------------------------
    // Activities
    // ---------------------------------------------------------------

    public function logActivity(
        string $type,
        ?string $subject,
        string $body,
        ?string $occurredAt,
        string $relatedToType,
        int $relatedToId,
        int $createdBy,
        ?array $ownerScope,
    ): Activity {
        $this->assertRelatedRecordVisible($relatedToType, $relatedToId, $ownerScope);

        $id = $this->activities->insert([
            'type'            => $type,
            'subject'         => $subject,
            'body'            => $body,
            'occurred_at'     => $occurredAt ?? date('Y-m-d H:i:s'),
            'related_to_type' => $relatedToType,
            'related_to_id'   => $relatedToId,
            'created_by'      => $createdBy,
        ]);

        return $this->activities->find($id);
    }

    /**
     * @return list<Activity>
     */
    public function timeline(string $relatedToType, int $relatedToId, ?string $typeFilter, ?array $ownerScope): array
    {
        // 404s if the parent record itself isn't visible to the caller
        $this->assertRelatedRecordVisible($relatedToType, $relatedToId, $ownerScope);

        return $this->activities->timelineFor($relatedToType, $relatedToId, $typeFilter);
    }

    /**
     * §7.4: the polymorphic link is application-enforced — the target must
     * exist and be visible to the caller before the insert.
     */
    private function assertRelatedRecordVisible(string $relatedToType, ?int $relatedToId, ?array $ownerScope): void
    {
        if ($relatedToId === null) {
            throw new ApiException('relatedToId is required when relatedToType is set.', 400, 'VALIDATION_ERROR');
        }

        $found = match ($relatedToType) {
            'COMPANY' => (new CompanyModel())->findScoped($relatedToId, $ownerScope),
            'CONTACT' => (new ContactModel())->findScoped($relatedToId, $ownerScope),
            'LEAD'    => (new LeadModel())->findScoped($relatedToId, $ownerScope),
            'DEAL'    => (new DealModel())->findScoped($relatedToId, $ownerScope),
            default   => throw new ApiException('Unknown relatedToType.', 400, 'VALIDATION_ERROR'),
        };

        if ($found === null) {
            throw new RecordNotFoundException(strtolower($relatedToType), $relatedToType . '_NOT_FOUND');
        }
    }
}
