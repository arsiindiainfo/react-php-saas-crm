<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateContacts extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'first_name' => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name'  => ['type' => 'VARCHAR', 'constraint' => 80],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'job_title'  => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'owner_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('company_id', false, false, 'idx_contact_company');
        $this->forge->addKey('owner_id', false, false, 'idx_contact_owner');
        $this->forge->addForeignKey('company_id', 'companies', 'id', '', 'RESTRICT', 'fk_contact_company');
        $this->forge->addForeignKey('owner_id', 'users', 'id', '', 'RESTRICT', 'fk_contact_owner');
        $this->forge->createTable('contacts', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE contacts
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ADD FULLTEXT KEY ftx_contact_search (first_name, last_name, email)');
    }

    public function down(): void
    {
        $this->forge->dropTable('contacts', true);
    }
}
