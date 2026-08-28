<?php

namespace App\Libraries;

/**
 * The DB/stored-procedure layer speaks snake_case; the API contract speaks
 * camelCase (request bodies already do, e.g. `dealName`/`existingCompanyId`
 * in §17 — this keeps responses consistent with that).
 */
class Camel
{
    /**
     * @param array<string,mixed> $row
     *
     * @return array<string,mixed>
     */
    public static function keysToCamel(array $row): array
    {
        $out = [];

        foreach ($row as $key => $value) {
            $camelKey       = preg_replace_callback('/_([a-z0-9])/', static fn ($m) => strtoupper($m[1]), (string) $key);
            $out[$camelKey] = $value;
        }

        return $out;
    }

    /**
     * @param list<array<string,mixed>> $rows
     *
     * @return list<array<string,mixed>>
     */
    public static function rowsToCamel(array $rows): array
    {
        return array_map(self::keysToCamel(...), $rows);
    }
}
