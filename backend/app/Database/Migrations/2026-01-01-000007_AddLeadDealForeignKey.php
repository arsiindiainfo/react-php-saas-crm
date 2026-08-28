<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Closes the leads<->deals circular reference now that `deals` exists.
 */
class AddLeadDealForeignKey extends Migration
{
    public function up(): void
    {
        $this->forge->addForeignKey('converted_deal_id', 'deals', 'id', '', 'SET NULL', 'fk_lead_deal');
        $this->forge->processIndexes('leads');
    }

    public function down(): void
    {
        $this->forge->dropForeignKey('leads', 'fk_lead_deal');
    }
}
