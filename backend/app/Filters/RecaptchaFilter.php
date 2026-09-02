<?php

namespace App\Filters;

use App\Exceptions\ApiException;
use App\Libraries\ExceptionResponder;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Throwable;

/**
 * Verifies a Google reCAPTCHA v2 token on /auth/login before the controller
 * runs, guarding against scripted/bot login attempts (§15). Skipped under
 * automated tests — verification is a live call to Google's siteverify API,
 * which feature tests must not depend on.
 */
class RecaptchaFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (ENVIRONMENT === 'testing') {
            return null;
        }

        $secret = env('RECAPTCHA_SECRET_KEY');

        if (! $secret) {
            return null;
        }

        $body  = json_decode($request->getBody() ?: '{}', true) ?: [];
        $token = $body['recaptchaToken'] ?? null;

        if (! is_string($token) || $token === '') {
            return ExceptionResponder::toResponse(
                new ApiException('reCAPTCHA verification is required.', 400, 'RECAPTCHA_REQUIRED'),
                Services::response(),
            );
        }

        try {
            $response = Services::curlrequest()->post('https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret'   => $secret,
                    'response' => $token,
                    'remoteip' => $request->getIPAddress(),
                ],
            ]);

            $result = json_decode((string) $response->getBody(), true) ?: [];
        } catch (Throwable) {
            return ExceptionResponder::toResponse(
                new ApiException('Could not verify reCAPTCHA right now. Please try again.', 503, 'RECAPTCHA_UNAVAILABLE'),
                Services::response(),
            );
        }

        if (($result['success'] ?? false) !== true) {
            return ExceptionResponder::toResponse(
                new ApiException('reCAPTCHA verification failed. Please try again.', 400, 'RECAPTCHA_FAILED'),
                Services::response(),
            );
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return $response;
    }
}
