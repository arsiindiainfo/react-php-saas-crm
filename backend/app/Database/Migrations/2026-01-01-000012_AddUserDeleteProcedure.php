<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Adds sp_user_delete (permanent user removal, §22.10) — loaded the same
 * way 000011_LoadStoredProcedures.php loads every other procedure file,
 * scoped to just this one new file.
 */
class AddUserDeleteProcedure extends Migration
{
    public function up(): void
    {
        $path = APPPATH . 'Database/Procedures/sp_user_delete.sql';
        $this->db->query('DROP PROCEDURE IF EXISTS sp_user_delete');
        $this->db->query(file_get_contents($path));
    }

    public function down(): void
    {
        $this->db->query('DROP PROCEDURE IF EXISTS sp_user_delete');
    }
}
