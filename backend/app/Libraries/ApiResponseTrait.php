<?php

namespace App\Libraries;

/**
 * Builds the §13 success/error response envelope. Used by every Controller.
 */
trait ApiResponseTrait
{
    protected function ok(mixed $data, int $status = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => true,
            'data'    => $data,
        ]);
    }

    protected function created(mixed $data): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->ok($data, 201);
    }

    /**
     * @param list<array<string,mixed>> $rows raw snake_case rows straight from sp_records_search
     */
    protected function okPaginated(array $rows, int $page, int $limit, int $total): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setStatusCode(200)->setJSON([
            'success' => true,
            'data'    => \App\Libraries\Camel::rowsToCamel($rows),
            'meta'    => [
                'page'       => $page,
                'limit'      => $limit,
                'total'      => $total,
                'totalPages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            ],
        ]);
    }

    protected function fail(string $errorCode, string $message, int $status): \CodeIgniter\HTTP\ResponseInterface
    {
        return $this->response->setStatusCode($status)->setJSON([
            'success' => false,
            'error'   => [
                'code'    => $errorCode,
                'message' => $message,
            ],
        ]);
    }
}
