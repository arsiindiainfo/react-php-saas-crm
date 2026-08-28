<?php

namespace App\Exceptions;

class ForbiddenRoleException extends ApiException
{
    public function __construct(string $message = 'Your role does not permit this action.')
    {
        parent::__construct($message, 403, 'FORBIDDEN_ROLE');
    }
}
