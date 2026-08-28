<?php

namespace App\Exceptions;

class DuplicateNameException extends ApiException
{
    public function __construct(string $message = 'A record with this name already exists.')
    {
        parent::__construct($message, 409, 'DUPLICATE_NAME');
    }
}
