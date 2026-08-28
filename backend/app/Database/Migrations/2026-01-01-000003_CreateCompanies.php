<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanies extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'industry'   => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'website'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'status'     => ['type' => 'ENUM', 'constraint' => ['PROSPECT', 'CUSTOMER', 'CHURNED'], 'default' => 'PROSPECT'],
            'owner_id'   => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => false],
            'updated_at' => ['type' => 'DATETIME', 'null' => false],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey('name', 'uq_company_name');
        $this->forge->addKey('owner_id', false, false, 'idx_company_owner');
        $this->forge->addForeignKey('owner_id', 'users', 'id', '', 'RESTRICT', 'fk_company_owner');
        $this->forge->createTable('companies', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE companies
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ADD FULLTEXT KEY ftx_company_search (name, industry)');
    }

    public function down(): void
    {
        $this->forge->dropTable('companies', true);
    }
}
