<?php

namespace App\Libraries;

use App\Exceptions\ApiException;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

/**
 * Converts any thrown exception — typed ApiException, CI4 routing 404, or
 * unexpected — into the §13.3 error envelope. Registered for every status
 * code in Config\Exceptions::handler() since this backend is API-only.
 */
class ApiExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        [$httpStatus, $errorCode, $message, $extra] = $this->resolve($exception, $statusCode);

        $body = [
            'success' => false,
            'error'   => array_merge(['code' => $errorCode, 'message' => $message], $extra),
        ];

        $response
            ->setStatusCode($httpStatus)
            ->setContentType('application/json')
            ->setBody(json_encode($body))
            ->setHeader('X-Request-Id', RequestId::get())
            ->send();

        if ($httpStatus >= 500) {
            Services::log()->error(
                '[{requestId}] {message} in {file}:{line}' . PHP_EOL . '{trace}',
                [
                    'requestId' => RequestId::get(),
                    'message'   => $exception->getMessage(),
                    'file'      => $exception->getFile(),
                    'line'      => $exception->getLine(),
                    'trace'     => $exception->getTraceAsString(),
                ],
            );
        }

        exit($exitCode);
    }

    /**
     * @return array{0:int,1:string,2:string,3:array<string,mixed>}
     */
    private function resolve(Throwable $exception, int $statusCode): array
    {
        if ($exception instanceof ApiException) {
            $extra = $exception->getFieldErrors() !== null ? ['fields' => $exception->getFieldErrors()] : [];

            return [$exception->getHttpStatus(), $exception->getErrorCode(), $exception->getMessage(), $extra];
        }

        if ($exception instanceof PageNotFoundException) {
            return [404, 'NOT_FOUND', 'The requested resource does not exist.', []];
        }

        $status = $statusCode >= 400 && $statusCode < 600 ? $statusCode : 500;

        return [$status, 'INTERNAL_ERROR', 'An unexpected error occurred. Please try again.', [
            'requestId' => RequestId::get(),
        ]];
    }
}
