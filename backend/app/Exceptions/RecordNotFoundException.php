<?php

namespace App\Exceptions;

/**
 * Used for every "no such record, or outside the caller's ownership scope"
 * case (§6, §14) — deliberately identical response either way.
 */
class RecordNotFoundException extends ApiException
{
    public function __construct(string $entityLabel, string $errorCode)
    {
        parent::__construct("No such {$entityLabel}.", 404, $errorCode);
    }
}
