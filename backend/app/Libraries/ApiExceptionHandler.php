<?php

namespace App\Libraries;

use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Throwable;

/**
 * Global fallback registered in Config\Exceptions — catches anything that
 * escapes both BaseApiController::_remap() and every custom Filter (see
 * ExceptionResponder's docblock for why both of those exist). In a real
 * HTTP request this is the last line of defense against truly unexpected
 * throws (e.g. something thrown from framework code itself); it is not
 * reachable from PHPUnit feature tests, which is exactly why the other two
 * call sites exist.
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
        ExceptionResponder::toResponse($exception, $response, $statusCode)->send();

        exit($exitCode);
    }
}
