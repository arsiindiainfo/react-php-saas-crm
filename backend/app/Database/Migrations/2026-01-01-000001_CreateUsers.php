<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUsers extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'            => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 190],
            'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'          => ['type' => 'ENUM', 'constraint' => ['ADMIN', 'SALES_MANAGER', 'SALES_REP'], 'default' => 'SALES_REP'],
            'manager_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'status'        => ['type' => 'ENUM', 'constraint' => ['ACTIVE', 'DISABLED'], 'default' => 'ACTIVE'],
            'created_at'    => ['type' => 'DATETIME', 'null' => false],
            'updated_at'    => ['type' => 'DATETIME', 'null' => false],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('email', 'uq_users_email');
        $this->forge->addKey('manager_id', false, false, 'idx_users_manager');
        $this->forge->addForeignKey('manager_id', 'users', 'id', '', 'SET NULL', 'fk_users_manager');
        $this->forge->createTable('users', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE users
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('users', true);
    }
}
