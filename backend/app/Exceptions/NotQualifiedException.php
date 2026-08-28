<?php

namespace App\Exceptions;

class NotQualifiedException extends ApiException
{
    public function __construct(string $message = 'Only a qualified lead can be converted.')
    {
        parent::__construct($message, 409, 'NOT_QUALIFIED');
    }
}
