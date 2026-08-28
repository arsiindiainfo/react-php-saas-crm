<?php

namespace App\Libraries;

/**
 * The parsed, validated form of the §10.1 list-query contract:
 * `page/limit/search/sort/direction`.
 */
final class ListQuery
{
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly string $search,
        public readonly string $sort,
        public readonly string $direction,
    ) {
    }
}
