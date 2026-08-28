<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateDeals extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'company_id'          => ['type' => 'BIGINT', 'unsigned' => true],
            'contact_id'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'lead_id'             => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'name'                => ['type' => 'VARCHAR', 'constraint' => 180],
            'value_amount'        => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'expected_close_date' => ['type' => 'DATE', 'null' => true],
            'stage'               => ['type' => 'ENUM', 'constraint' => ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION', 'WON', 'LOST'], 'default' => 'PROSPECTING'],
            'lost_reason'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'owner_id'            => ['type' => 'BIGINT', 'unsigned' => true],
            'closed_at'           => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => false],
            'updated_at'          => ['type' => 'DATETIME', 'null' => false],
            'deleted_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['owner_id', 'stage'], false, false, 'idx_deal_owner_stage');
        $this->forge->addKey('company_id', false, false, 'idx_deal_company');
        $this->forge->addForeignKey('company_id', 'companies', 'id', '', 'RESTRICT', 'fk_deal_company');
        $this->forge->addForeignKey('contact_id', 'contacts', 'id', '', 'SET NULL', 'fk_deal_contact');
        $this->forge->addForeignKey('lead_id', 'leads', 'id', '', 'SET NULL', 'fk_deal_lead');
        $this->forge->addForeignKey('owner_id', 'users', 'id', '', 'RESTRICT', 'fk_deal_owner');
        $this->forge->createTable('deals', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE deals
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            ADD CONSTRAINT ck_deal_lost_reason CHECK (stage <> \'LOST\' OR lost_reason IS NOT NULL)');
    }

    public function down(): void
    {
        $this->forge->dropTable('deals', true);
    }
}
