<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Unified note/call/email/meeting timeline (§7.3 decision) — a "Note" is
 * just an Activity of type NOTE.
 */
class CreateActivities extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'type'            => ['type' => 'ENUM', 'constraint' => ['NOTE', 'CALL', 'EMAIL', 'MEETING']],
            'subject'         => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'body'            => ['type' => 'VARCHAR', 'constraint' => 2000],
            'occurred_at'     => ['type' => 'DATETIME'],
            'related_to_type' => ['type' => 'ENUM', 'constraint' => ['COMPANY', 'CONTACT', 'LEAD', 'DEAL']],
            'related_to_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'created_by'      => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['related_to_type', 'related_to_id', 'occurred_at'], false, false, 'idx_activity_related');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'RESTRICT', 'fk_activity_creator');
        $this->forge->createTable('activities', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE activities MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('activities', true);
    }
}
