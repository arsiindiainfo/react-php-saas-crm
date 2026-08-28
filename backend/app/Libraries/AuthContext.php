<?php

namespace App\Libraries;

use App\Entities\User;

/**
 * Request-scoped holder for the authenticated caller, set once by
 * JwtAuthFilter and read by Controllers/Services — avoids stashing dynamic
 * properties on the CI4 Request object.
 */
class AuthContext
{
    private static ?User $user = null;

    /** @var list<int>|null null = unrestricted (ADMIN), else visible owner_id set (§6.2) */
    private static ?array $ownerScope = null;

    public static function setUser(User $user): void
    {
        self::$user = $user;
    }

    public static function user(): ?User
    {
        return self::$user;
    }

    /**
     * @param list<int>|null $ownerIds
     */
    public static function setOwnerScope(?array $ownerIds): void
    {
        self::$ownerScope = $ownerIds;
    }

    /**
     * @return list<int>|null
     */
    public static function ownerScope(): ?array
    {
        return self::$ownerScope;
    }

    /** Comma-joined ids for the `p_owner_scope` stored-procedure parameter, or null for ADMIN (unrestricted). */
    public static function ownerScopeCsv(): ?string
    {
        return self::$ownerScope === null ? null : implode(',', self::$ownerScope);
    }

    /** Test-only: reset between test cases. */
    public static function reset(): void
    {
        self::$user       = null;
        self::$ownerScope = null;
    }
}
