<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Root OpenAPI document metadata + reusable components (§26). Doesn't need
 * to be instantiated or routed to anywhere — swagger-php's Generator scans
 * every file under app/ for these attributes, this is just where the
 * top-level ones (that don't belong to any one controller) live.
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Arsi India Info — React + PHP SaaS CRM API',
    description: 'Companies, contacts, a lead pipeline with real conversion logic, a deal pipeline, '
        . 'tasks, activities, and reporting — see the §13 envelope and §14 error catalog for the shape every response follows.',
    contact: new OA\Contact(name: 'Arsi India Info'),
    license: new OA\License(name: 'MIT', url: 'https://opensource.org/licenses/MIT'),
)]
#[OA\Server(url: '/api/v1', description: 'Base path for every endpoint below')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'JWT',
    description: 'Short-lived (15 min) access token returned by POST /auth/login or /auth/refresh.',
)]
#[OA\Schema(
    schema: 'ErrorEnvelope',
    description: 'Shape of every non-2xx response (§13.3).',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(
            property: 'error',
            properties: [
                new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_ERROR'),
                new OA\Property(property: 'message', type: 'string', example: 'One or more fields are invalid.'),
                new OA\Property(property: 'fields', type: 'object', nullable: true),
            ],
            type: 'object',
        ),
    ],
    type: 'object',
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    description: 'Attached to every list response (§13.2).',
    properties: [
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'limit', type: 'integer', example: 20),
        new OA\Property(property: 'total', type: 'integer', example: 64),
        new OA\Property(property: 'totalPages', type: 'integer', example: 4),
    ],
    type: 'object',
)]
class Base
{
}
