<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\TaskService;

class ActivitiesController extends BaseApiController
{
    private TaskService $tasks;

    public function __construct()
    {
        $this->tasks = new TaskService();
    }

    public function create()
    {
        $data = $this->validateBody('activityCreate');

        $activity = $this->tasks->logActivity(
            $data['type'],
            $data['subject'] ?? null,
            $data['body'],
            $data['occurredAt'] ?? null,
            $data['relatedToType'],
            (int) $data['relatedToId'],
            $this->authUser()->id,
            AuthContext::ownerScope(),
        );

        return $this->created($activity);
    }

    public function index()
    {
        $relatedToType = $this->request->getGet('relatedToType');
        $relatedToId   = $this->request->getGet('relatedToId');
        $typeFilter    = $this->request->getGet('type');

        if ($relatedToType === null || $relatedToId === null) {
            return $this->fail('VALIDATION_ERROR', 'relatedToType and relatedToId are required.', 400);
        }

        $activities = $this->tasks->timeline($relatedToType, (int) $relatedToId, $typeFilter ?: null, AuthContext::ownerScope());

        return $this->ok($activities);
    }
}
