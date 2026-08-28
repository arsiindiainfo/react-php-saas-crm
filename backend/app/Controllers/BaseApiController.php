<?php

namespace App\Controllers;

use App\Entities\User;
use App\Exceptions\ValidationException;
use App\Libraries\ApiResponseTrait;
use App\Libraries\AuthContext;
use App\Libraries\ListQuery;
use App\Libraries\ListQueryParser;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Thin HTTP layer: run validation, apply the list-query contract for list
 * endpoints, delegate to exactly one Service call, return the envelope.
 * Controllers never touch a Model or stored procedure directly.
 */
abstract class BaseApiController extends \CodeIgniter\Controller
{
    use ApiResponseTrait;

    /** @var array<string,mixed> */
    protected array $body = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        if ($request instanceof IncomingRequest) {
            $decoded    = json_decode($request->getBody() ?: '{}', true);
            $this->body = is_array($decoded) ? $decoded : [];
        }
    }

    /** The authenticated caller, set by JwtAuthFilter — null only on public routes. */
    protected function authUser(): ?User
    {
        return AuthContext::user();
    }

    /**
     * Validates $this->body against a named rule group; throws
     * ValidationException (→ 400 VALIDATION_ERROR) rather than returning a
     * bool, so Controllers never accidentally continue past invalid input.
     */
    protected function validateBody(string $ruleGroup): array
    {
        $validation = \Config\Services::validation();
        $validation->setRuleGroup($ruleGroup);

        if (! $validation->run($this->body)) {
            throw new ValidationException($validation->getErrors());
        }

        return $this->body;
    }

    protected function parseListQuery(array $sortable, string $defaultSort = 'id'): ListQuery
    {
        return (new ListQueryParser())->parse($this->request, $sortable, $defaultSort);
    }
}
