<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Minimal team needed to exercise auth/roles through Phases 0–5 — 1 ADMIN,
 * 1 SALES_MANAGER, 2 SALES_REP (one reporting to the manager). Expands into
 * the full "Brightfield Business Solutions" DemoSeeder in Phase 6.
 *
 * All seeded users share the password `Passw0rd!` for local/demo login.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $hash = password_hash('Passw0rd!', PASSWORD_BCRYPT);
        $now  = date('Y-m-d H:i:s');

        $this->db->table('users')->insert([
            'name' => 'Ava Admin', 'email' => 'admin@brightfield.test', 'password_hash' => $hash,
            'role' => 'ADMIN', 'manager_id' => null, 'status' => 'ACTIVE',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->db->table('users')->insert([
            'name' => 'Priya Manager', 'email' => 'priya.manager@brightfield.test', 'password_hash' => $hash,
            'role' => 'SALES_MANAGER', 'manager_id' => null, 'status' => 'ACTIVE',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $managerId = $this->db->insertID();

        $this->db->table('users')->insert([
            'name' => 'Arjun Rep', 'email' => 'arjun.rep@brightfield.test', 'password_hash' => $hash,
            'role' => 'SALES_REP', 'manager_id' => $managerId, 'status' => 'ACTIVE',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // Deliberately NOT under $managerId — the §6.2 guardrail fixture: "a
        // second seeded SALES_REP (no shared manager)".
        $this->db->table('users')->insert([
            'name' => 'Meera Rep', 'email' => 'meera.rep@brightfield.test', 'password_hash' => $hash,
            'role' => 'SALES_REP', 'manager_id' => null, 'status' => 'ACTIVE',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }
}
