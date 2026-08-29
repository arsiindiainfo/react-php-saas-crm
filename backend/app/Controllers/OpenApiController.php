<?php

namespace App\Controllers;

use OpenApi\Generator;

/**
 * Serves the OpenAPI spec generated from the §26 doc-comment annotations —
 * non-production only, so the contract is always freshly regenerated from
 * the actual controller attributes (it can't drift from what's really
 * validated, since every RequestBody schema mirrors the CI4 rule group
 * enforced in the same method).
 */
class OpenApiController extends \CodeIgniter\Controller
{
    public function index()
    {
        if (ENVIRONMENT === 'production') {
            return $this->response->setStatusCode(404);
        }

        $openapi = (new Generator())->generate([APPPATH . 'Controllers', APPPATH . 'OpenApi']);

        return $this->response->setContentType('application/json')->setBody((string) $openapi->toJson());
    }
}
