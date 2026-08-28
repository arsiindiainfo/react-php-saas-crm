<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * `converted_deal_id`'s FK is added later (see AddLeadDealForeignKey) once
 * the `deals` table exists — leads/deals have a circular reference.
 */
class CreateLeads extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'                   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'first_name'           => ['type' => 'VARCHAR', 'constraint' => 80],
            'last_name'            => ['type' => 'VARCHAR', 'constraint' => 80],
            'email'                => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true],
            'phone'                => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'company_name'         => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'source'               => ['type' => 'ENUM', 'constraint' => ['WEBSITE', 'REFERRAL', 'COLD_CALL', 'EVENT', 'OTHER'], 'default' => 'OTHER'],
            'status'               => ['type' => 'ENUM', 'constraint' => ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'DISQUALIFIED'], 'default' => 'NEW'],
            'disqualify_reason'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'owner_id'             => ['type' => 'BIGINT', 'unsigned' => true],
            'converted_at'         => ['type' => 'DATETIME', 'null' => true],
            'converted_company_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'converted_contact_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'converted_deal_id'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'created_at'           => ['type' => 'DATETIME', 'null' => false],
            'updated_at'           => ['type' => 'DATETIME', 'null' => false],
            'deleted_at'           => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['owner_id', 'status'], false, false, 'idx_lead_owner_status');
        $this->forge->addForeignKey('owner_id', 'users', 'id', '', 'RESTRICT', 'fk_lead_owner');
        $this->forge->addForeignKey('converted_company_id', 'companies', 'id', '', 'SET NULL', 'fk_lead_company');
        $this->forge->addForeignKey('converted_contact_id', 'contacts', 'id', '', 'SET NULL', 'fk_lead_contact');
        $this->forge->createTable('leads', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4']);

        $this->db->query('ALTER TABLE leads
            MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    public function down(): void
    {
        $this->forge->dropTable('leads', true);
    }
}
