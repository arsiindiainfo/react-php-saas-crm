<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Hashed refresh tokens for rotation (§7.1) — the raw token is only ever
 * returned to the client once, at issuance; we store sha256(token).
 */
class CreateRefreshTokens extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'BIGINT', 'unsigned' => true],
            'token_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'expires_at' => ['type' => 'DATETIME'],
            'revoked_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('token_hash', 'uq_refresh_token_hash');
        $this->forge->addKey('user_id', false, false, 'idx_refresh_token_user');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'CASCADE', 'fk_refresh_token_user');
        $this->forge->createTable('refresh_tokens', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE refresh_tokens MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('refresh_tokens', true);
    }
}
