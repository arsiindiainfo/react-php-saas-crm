<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Resend API key, sourced from .env — production invite email
 * (ResendMailer) sends through this. Left empty in local dev, where mail
 * goes to Mailhog instead (see ResendMailer's ENVIRONMENT check).
 */
class Resend extends BaseConfig
{
    public string $apiKey = '';

    public function __construct()
    {
        parent::__construct();

        $this->apiKey = (string) env('resend.apiKey', '');
    }
}
