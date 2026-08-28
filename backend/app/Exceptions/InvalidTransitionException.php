<?php

namespace App\Exceptions;

class InvalidTransitionException extends ApiException
{
    public function __construct(string $message = 'This status/stage change is not allowed from the current state.')
    {
        parent::__construct($message, 409, 'INVALID_TRANSITION');
    }
}
