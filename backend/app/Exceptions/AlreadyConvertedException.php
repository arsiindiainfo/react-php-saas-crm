<?php

namespace App\Exceptions;

class AlreadyConvertedException extends ApiException
{
    public function __construct(string $message = 'This lead has already been converted.')
    {
        parent::__construct($message, 409, 'ALREADY_CONVERTED');
    }
}
