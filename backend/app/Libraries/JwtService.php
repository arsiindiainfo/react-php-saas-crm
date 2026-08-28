<?php

namespace App\Libraries;

use Config\Crm;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Short-lived (15 min) HS256 access tokens (§5 Security practices). Refresh
 * tokens themselves are opaque random strings, hashed at rest and rotated —
 * see AuthService — this class only handles the JWT access token.
 */
class JwtService
{
    private string $secret;
    private Crm $crm;

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', getenv('JWT_SECRET') ?: 'insecure-dev-secret-change-me');
        $this->crm    = config(Crm::class);
    }

    /**
     * @param array<string,mixed> $claims
     */
    public function issueAccessToken(array $claims): string
    {
        $now = time();

        return JWT::encode(array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $this->crm->accessTokenTtl,
        ]), $this->secret, 'HS256');
    }

    /**
     * @return array<string,mixed>|null null if invalid/expired
     */
    public function decode(string $token): ?array
    {
        try {
            return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable) {
            return null;
        }
    }
}
