<?php

namespace App\Controllers;

use App\Libraries\AuthContext;
use App\Services\TaskService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Activities')]
class ActivitiesController extends BaseApiController
{
    private TaskService $tasks;

    public function __construct()
    {
        $this->tasks = new TaskService();
    }

    #[OA\Post(
        path: '/activities',
        summary: 'Log a note/call/email/meeting against a Company, Contact, Lead, or Deal',
        tags: ['Activities'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ['type', 'body', 'relatedToType', 'relatedToId'],
            properties: [
                new OA\Property(property: 'type', type: 'string', enum: ['NOTE', 'CALL', 'EMAIL', 'MEETING']),
                new OA\Property(property: 'subject', type: 'string', nullable: true),
                new OA\Property(property: 'body', type: 'string', minLength: 1, maxLength: 2000),
                new OA\Property(property: 'occurredAt', type: 'string', format: 'date-time', nullable: true, description: 'Defaults to now'),
                new OA\Property(property: 'relatedToType', type: 'string', enum: ['COMPANY', 'CONTACT', 'LEAD', 'DEAL']),
                new OA\Property(property: 'relatedToId', type: 'integer'),
            ],
        )),
        responses: [
            new OA\Response(response: 201, description: 'Activity logged'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'The related record does not exist or isn\'t visible to the caller', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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

    #[OA\Get(
        path: '/activities',
        summary: 'Timeline for one record — backs the embedded Activity/Notes tabs',
        tags: ['Activities'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'relatedToType', in: 'query', required: true, schema: new OA\Schema(type: 'string', enum: ['COMPANY', 'CONTACT', 'LEAD', 'DEAL'])),
            new OA\Parameter(name: 'relatedToId', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'type', in: 'query', schema: new OA\Schema(type: 'string', enum: ['NOTE', 'CALL', 'EMAIL', 'MEETING']), description: 'Filter to one type — e.g. NOTE for the Company "Notes" tab'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Activities for the record, newest first'),
            new OA\Response(response: 400, description: 'VALIDATION_ERROR — relatedToType/relatedToId are required', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
            new OA\Response(response: 404, description: 'The related record does not exist or isn\'t visible to the caller', content: new OA\JsonContent(ref: '#/components/schemas/ErrorEnvelope')),
        ],
    )]
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
