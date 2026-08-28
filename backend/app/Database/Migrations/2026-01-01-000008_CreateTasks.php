<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `related_to_type`/`related_to_id` are application-enforced, not a real FK
 * across four possible target tables (§7.4).
 */
class CreateTasks extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'subject'         => ['type' => 'VARCHAR', 'constraint' => 200],
            'due_date'        => ['type' => 'DATE', 'null' => true],
            'priority'        => ['type' => 'ENUM', 'constraint' => ['LOW', 'MEDIUM', 'HIGH'], 'default' => 'MEDIUM'],
            'related_to_type' => ['type' => 'ENUM', 'constraint' => ['COMPANY', 'CONTACT', 'LEAD', 'DEAL'], 'null' => true],
            'related_to_id'   => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'assigned_to'     => ['type' => 'BIGINT', 'unsigned' => true],
            'created_by'      => ['type' => 'BIGINT', 'unsigned' => true],
            'completed_at'    => ['type' => 'DATETIME', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => false],
            'updated_at'      => ['type' => 'DATETIME', 'null' => false],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['assigned_to', 'due_date'], false, false, 'idx_task_assignee_due');
        $this->forge->addKey(['related_to_type', 'related_to_id'], false, false, 'idx_task_related');
        $this->forge->addForeignKey('assigned_to', 'users', 'id', '', 'RESTRICT', 'fk_task_assignee');
        $this->forge->addForeignKey('created_by', 'users', 'id', '', 'RESTRICT', 'fk_task_creator');
        $this->forge->createTable('tasks', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE tasks
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('tasks', true);
    }
}
