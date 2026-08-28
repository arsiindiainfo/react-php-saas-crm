<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAuditLogs extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id'     => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'action'      => ['type' => 'VARCHAR', 'constraint' => 60],
            'entity_type' => ['type' => 'VARCHAR', 'constraint' => 30],
            'entity_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'details'     => ['type' => 'JSON', 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['entity_type', 'entity_id'], false, false, 'idx_audit_entity');
        $this->forge->addKey('created_at', false, false, 'idx_audit_created');
        $this->forge->addForeignKey('user_id', 'users', 'id', '', 'SET NULL', 'fk_audit_user');
        $this->forge->createTable('audit_logs', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE audit_logs MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('audit_logs', true);
    }
}
