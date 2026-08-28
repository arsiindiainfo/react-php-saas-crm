<?php

namespace App\Models;

use CodeIgniter\Model;

class RefreshTokenModel extends Model
{
    protected $table        = 'refresh_tokens';
    protected $primaryKey   = 'id';
    protected $returnType   = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['user_id', 'token_hash', 'expires_at', 'revoked_at'];

    public static function hash(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function issue(int $userId, string $rawToken, int $ttlSeconds): void
    {
        $this->insert([
            'user_id'    => $userId,
            'token_hash' => self::hash($rawToken),
            'expires_at' => date('Y-m-d H:i:s', time() + $ttlSeconds),
        ]);
    }

    /**
     * Returns the row if the token is valid (exists, unexpired, unrevoked).
     *
     * @return array<string,mixed>|null
     */
    public function findValid(string $rawToken): ?array
    {
        return $this->where('token_hash', self::hash($rawToken))
            ->where('revoked_at', null)
            ->where('expires_at >', date('Y-m-d H:i:s'))
            ->first();
    }

    public function revoke(string $rawToken): void
    {
        $this->where('token_hash', self::hash($rawToken))->set('revoked_at', date('Y-m-d H:i:s'))->update();
    }

    public function revokeAllForUser(int $userId): void
    {
        $this->where('user_id', $userId)->where('revoked_at', null)->set('revoked_at', date('Y-m-d H:i:s'))->update();
    }
}
