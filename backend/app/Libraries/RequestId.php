<?php

namespace App\Libraries;

/**
 * A short id generated once per request, attached to error logs and the
 * `X-Request-Id` response header so a reported 500 can be traced in the logs.
 */
class RequestId
{
    private static ?string $id = null;

    public static function get(): string
    {
        if (self::$id === null) {
            self::$id = bin2hex(random_bytes(6));
        }

        return self::$id;
    }
}
