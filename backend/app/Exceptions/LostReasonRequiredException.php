<?php

namespace App\Exceptions;

class LostReasonRequiredException extends ApiException
{
    public function __construct(string $message = 'A reason is required when marking a deal as lost.')
    {
        parent::__construct($message, 400, 'LOST_REASON_REQUIRED');
    }
}
