<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Base for every exception that should reach the client as the §13.3
 * `{ success:false, error:{ code, message } }` envelope.
 */
class ApiException extends RuntimeException
{
    protected int $httpStatus = 500;
    protected string $errorCode = 'INTERNAL_ERROR';

    /** @var array<string,string>|null field => message, for VALIDATION_ERROR */
    protected ?array $fieldErrors = null;

    public function __construct(string $message, ?int $httpStatus = null, ?string $errorCode = null)
    {
        parent::__construct($message);

        if ($httpStatus !== null) {
            $this->httpStatus = $httpStatus;
        }

        if ($errorCode !== null) {
            $this->errorCode = $errorCode;
        }
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string,string>|null
     */
    public function getFieldErrors(): ?array
    {
        return $this->fieldErrors;
    }
}
