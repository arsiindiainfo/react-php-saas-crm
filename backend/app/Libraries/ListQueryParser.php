<?php

namespace App\Libraries;

use CodeIgniter\HTTP\RequestInterface;
use Config\Crm;

/**
 * Parses and validates `page/limit/search/sort/direction` (§10.1) — the one
 * contract every list-style Controller shares. `$sortable` is the
 * per-resource allow-listed map of API field name => itself (only the keys
 * matter; sp_records_search re-derives the actual column internally, §30).
 */
class ListQueryParser
{
    public function parse(RequestInterface $request, array $sortable, string $defaultSort = 'createdAt'): ListQuery
    {
        $crm = config(Crm::class);

        $page  = max(1, (int) ($request->getGet('page') ?? 1));
        $limit = (int) ($request->getGet('limit') ?? $crm->defaultPageLimit);
        $limit = $limit < 1 ? $crm->defaultPageLimit : min($limit, $crm->maxPageLimit);

        $sort = (string) ($request->getGet('sort') ?? $defaultSort);
        if (! in_array($sort, $sortable, true)) {
            $sort = $defaultSort;
        }

        $direction = strtolower((string) ($request->getGet('direction') ?? 'asc'));
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $search = trim((string) ($request->getGet('search') ?? ''));

        return new ListQuery($page, $limit, $search, $sort, $direction);
    }
}
