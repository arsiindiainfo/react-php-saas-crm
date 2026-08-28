<?php

namespace App\Libraries;

use App\Exceptions\ApiException;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

/**
 * Builds the §13.3 error envelope from any Throwable. Shared by:
 *  - BaseApiController::_remap() — catches everything a Controller/Service
 *    throws, since that's the majority of business-rule violations;
 *  - every custom Filter — a `before()` filter runs ahead of the Controller,
 *    so a Controller-level catch alone would miss JwtAuthFilter/RoleFilter/
 *    RateLimitFilter/OwnershipScopeFilter throwing;
 *  - ApiExceptionHandler — the global fallback for anything thrown outside
 *    both of the above (kept for defense in depth in real HTTP requests).
 *
 * Both call sites above run inside the same request lifecycle whether that
 * request came from a real HTTP call or from FeatureTestTrait — unlike the
 * global exception handler, which PHPUnit's own per-test try/catch prevents
 * from ever firing (an exception thrown inside `$this->app->run()` during a
 * feature test never becomes a truly *uncaught* exception; PHPUnit catches
 * it first to report the test result).
 */
class ExceptionResponder
{
    public static function toResponse(Throwable $exception, ResponseInterface $response, ?int $fallbackStatus = null): ResponseInterface
    {
        [$httpStatus, $errorCode, $message, $extra] = self::resolve($exception, $fallbackStatus);

        if ($httpStatus >= 500) {
            Services::logger()->error(
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

        return $response
            ->setStatusCode($httpStatus)
            ->setHeader('X-Request-Id', RequestId::get())
            ->setJSON([
                'success' => false,
                'error'   => array_merge(['code' => $errorCode, 'message' => $message], $extra),
            ]);
    }

    /**
     * @return array{0:int,1:string,2:string,3:array<string,mixed>}
     */
    private static function resolve(Throwable $exception, ?int $fallbackStatus): array
    {
        if ($exception instanceof ApiException) {
            $extra = $exception->getFieldErrors() !== null ? ['fields' => $exception->getFieldErrors()] : [];

            return [$exception->getHttpStatus(), $exception->getErrorCode(), $exception->getMessage(), $extra];
        }

        if ($exception instanceof PageNotFoundException) {
            return [404, 'NOT_FOUND', 'The requested resource does not exist.', []];
        }

        $status = $fallbackStatus >= 400 && $fallbackStatus < 600 ? $fallbackStatus : 500;

        return [$status, 'INTERNAL_ERROR', 'An unexpected error occurred. Please try again.', [
            'requestId' => RequestId::get(),
        ]];
    }
}
