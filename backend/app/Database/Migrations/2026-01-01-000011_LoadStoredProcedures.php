<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Loads every `app/Database/Procedures/*.sql` file as a stored procedure.
 * Each file contains exactly one `CREATE PROCEDURE <name> ... END` statement
 * with ordinary `;` terminators — no `DELIMITER` directive is needed because
 * we execute the whole file as a single query via MySQLi, not through the
 * `mysql` CLI (which is the only client that needs the DELIMITER trick).
 */
class LoadStoredProcedures extends Migration
{
    public function up(): void
    {
        foreach ($this->procedureFiles() as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $this->db->query("DROP PROCEDURE IF EXISTS {$name}");
            $this->db->query(file_get_contents($path));
        }
    }

    public function down(): void
    {
        foreach ($this->procedureFiles() as $path) {
            $name = pathinfo($path, PATHINFO_FILENAME);
            $this->db->query("DROP PROCEDURE IF EXISTS {$name}");
        }
    }

    /**
     * @return list<string>
     */
    private function procedureFiles(): array
    {
        $dir   = APPPATH . 'Database/Procedures';
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);

        return $files;
    }
}
