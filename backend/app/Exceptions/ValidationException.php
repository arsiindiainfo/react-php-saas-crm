<?php

namespace App\Exceptions;

class ValidationException extends ApiException
{
    /**
     * @param array<string,string> $fieldErrors
     */
    public function __construct(array $fieldErrors, string $message = 'One or more fields are invalid.')
    {
        parent::__construct($message, 400, 'VALIDATION_ERROR');
        $this->fieldErrors = $fieldErrors;
    }
}
