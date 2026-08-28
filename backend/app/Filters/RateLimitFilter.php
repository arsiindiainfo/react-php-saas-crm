<?php

namespace App\Filters;

use App\Exceptions\ApiException;
use App\Libraries\ExceptionResponder;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Crm;
use Config\Services;

/**
 * Simple fixed-window per-IP limiter, used on /auth/login (§15: 10/min/IP).
 * Argument is the limit-per-minute; defaults to Crm::$loginRateLimitPerMinute.
 */
class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $limit = isset($arguments[0]) ? (int) $arguments[0] : config(Crm::class)->loginRateLimitPerMinute;
        $cache = Services::cache();
        $key   = 'ratelimit_' . md5($request->getIPAddress() . '_' . $request->getUri()->getPath());

        $count = (int) ($cache->get($key) ?? 0);

        if ($count >= $limit) {
            return ExceptionResponder::toResponse(
                new ApiException('Too many requests. Please try again shortly.', 429, 'RATE_LIMITED'),
                Services::response(),
            );
        }

        $cache->save($key, $count + 1, 60);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
