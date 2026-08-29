<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * The full "Brightfield Business Solutions" portfolio dataset (§27, §32) —
 * a realistic team, companies across every status, leads across every
 * status (including converted ones), deals across every stage spread over
 * the last 12 months (so the dashboard trend chart and KPIs aren't empty),
 * tasks, an activity timeline, and a few illustrative audit-log rows.
 *
 * Safe to re-run: truncates the tables it owns first (children before
 * parents, to respect FKs) rather than trying to be additive.
 *
 * Usage: `php spark db:seed DemoSeeder` against `backend/.env`'s configured
 * database (after `php spark migrate --all`).
 */
class DemoSeeder extends Seeder
{
    private string $passwordHash;
    /** @var array<string,int> name => id */
    private array $userIds = [];
    /** @var array<string,int> name => id */
    private array $companyIds = [];
    /** @var array<string,int> "Company::First Last" => id */
    private array $contactIds = [];

    public function run(): void
    {
        $this->passwordHash = password_hash('Passw0rd!', PASSWORD_BCRYPT);

        $this->wipe();
        $this->seedUsers();
        $this->seedCompaniesAndContacts();
        $this->seedDeals();
        $this->seedLeads();
        $this->seedTasks();
        $this->seedActivities();
        $this->seedAuditLogs();
    }

    private function wipe(): void
    {
        foreach (['audit_logs', 'activities', 'tasks', 'deals', 'contacts', 'leads', 'companies', 'refresh_tokens', 'users'] as $table) {
            $this->db->table($table)->emptyTable();
        }
    }

    private function now(int $daysAgo = 0): string
    {
        return date('Y-m-d H:i:s', strtotime("-{$daysAgo} days"));
    }

    // ---------------------------------------------------------------
    // Team
    // ---------------------------------------------------------------

    private function seedUsers(): void
    {
        $this->insertUser('Ava Admin', 'admin@brightfield.test', 'ADMIN', null);
        $this->insertUser('Priya Manager', 'priya.manager@brightfield.test', 'SALES_MANAGER', null);
        $this->insertUser('Rohan Verma', 'rohan.manager@brightfield.test', 'SALES_MANAGER', null);
        $this->insertUser('Arjun Rep', 'arjun.rep@brightfield.test', 'SALES_REP', 'Priya Manager');
        // Meera intentionally has no manager — the §6.2 guardrail fixture (a rep with no shared manager).
        $this->insertUser('Meera Rep', 'meera.rep@brightfield.test', 'SALES_REP', null);
        $this->insertUser('Karan Malhotra', 'karan.rep@brightfield.test', 'SALES_REP', 'Rohan Verma');
        $this->insertUser('Sana Iyer', 'sana.rep@brightfield.test', 'SALES_REP', 'Rohan Verma');
    }

    private function insertUser(string $name, string $email, string $role, ?string $managerName): void
    {
        $this->db->table('users')->insert([
            'name'          => $name,
            'email'         => $email,
            'password_hash' => $this->passwordHash,
            'role'          => $role,
            'manager_id'    => $managerName !== null ? $this->userIds[$managerName] : null,
            'status'        => 'ACTIVE',
            'created_at'    => $this->now(180),
            'updated_at'    => $this->now(180),
        ]);
        $this->userIds[$name] = (int) $this->db->insertID();
    }

    // ---------------------------------------------------------------
    // Companies & contacts
    // ---------------------------------------------------------------

    private function seedCompaniesAndContacts(): void
    {
        $owners = ['Arjun Rep', 'Meera Rep', 'Karan Malhotra', 'Sana Iyer'];
        $i      = 0;
        $next   = function () use ($owners, &$i) {
            return $owners[$i++ % count($owners)];
        };

        $companies = [
            ['NovaTrail Logistics', 'Logistics', 'CUSTOMER'],
            ['BrightPeak Retail', 'Retail', 'CUSTOMER'],
            ['Solstice Manufacturing', 'Manufacturing', 'CUSTOMER'],
            ['Alder & Finch Consulting', 'Professional Services', 'CUSTOMER'],
            ['Cedarline Foods', 'Food & Beverage', 'CUSTOMER'],
            ['Vantage Point Realty', 'Real Estate', 'CUSTOMER'],
            ['Meridian Health Partners', 'Healthcare', 'PROSPECT'],
            ['Foundry Robotics', 'Manufacturing', 'PROSPECT'],
            ['Lumen Analytics', 'Software', 'PROSPECT'],
            ['Harborview Hospitality', 'Hospitality', 'PROSPECT'],
            ['Crestline Insurance', 'Insurance', 'PROSPECT'],
            ['Old Mill Textiles', 'Manufacturing', 'CHURNED'],
            ['Redwood Freight', 'Logistics', 'CHURNED'],
        ];

        $contactNames = [
            ['Jordan', 'Blake'], ['Riley', 'Chen'], ['Morgan', 'Patel'], ['Casey', 'Nguyen'],
            ['Taylor', 'Osei'], ['Avery', 'Kim'], ['Reese', 'Alvarez'], ['Quinn', 'Fischer'],
            ['Sydney', 'Rao'], ['Dakota', 'Silva'], ['Harper', 'Ito'], ['Emerson', 'Novak'],
            ['Rowan', 'Duarte'], ['Finley', 'Haas'],
        ];
        $titles = ['Operations Manager', 'VP of Sales', 'Procurement Lead', 'CEO', 'Director of Finance', 'Head of Marketing'];

        foreach ($companies as $idx => [$name, $industry, $status]) {
            $owner = $next();
            $this->db->table('companies')->insert([
                'name'       => $name,
                'industry'   => $industry,
                'website'    => 'https://www.' . strtolower(str_replace([' ', '&'], ['', 'and'], $name)) . '.example.com',
                'phone'      => '+1-555-' . str_pad((string) (1000 + $idx), 4, '0', STR_PAD_LEFT),
                'status'     => $status,
                'owner_id'   => $this->userIds[$owner],
                'created_at' => $this->now(150 - $idx * 3),
                'updated_at' => $this->now(10),
            ]);
            $companyId                = (int) $this->db->insertID();
            $this->companyIds[$name]  = $companyId;

            [$first, $last] = $contactNames[$idx];
            $this->db->table('contacts')->insert([
                'company_id' => $companyId,
                'first_name' => $first,
                'last_name'  => $last,
                'email'      => strtolower("{$first}.{$last}") . '@' . strtolower(str_replace([' ', '&'], ['', 'and'], $name)) . '.example.com',
                'phone'      => '+1-555-' . str_pad((string) (2000 + $idx), 4, '0', STR_PAD_LEFT),
                'job_title'  => $titles[$idx % count($titles)],
                'owner_id'   => $this->userIds[$owner],
                'created_at' => $this->now(149 - $idx * 3),
                'updated_at' => $this->now(10),
            ]);
            $this->contactIds["{$name}::{$first} {$last}"] = (int) $this->db->insertID();
        }
    }

    // ---------------------------------------------------------------
    // Deals — spread across every stage and the last 12 months
    // ---------------------------------------------------------------

    private function seedDeals(): void
    {
        $owners = ['Arjun Rep', 'Meera Rep', 'Karan Malhotra', 'Sana Iyer'];
        $companiesInOrder = array_keys($this->companyIds);

        // 8 WON deals, one per of the last 8 months (including this month) — feeds the trend chart + revenue KPI.
        $wonPlan = [
            ['NovaTrail Logistics', 42000, 0],
            ['BrightPeak Retail', 18500, 1],
            ['Solstice Manufacturing', 61200, 2],
            ['Alder & Finch Consulting', 9800, 3],
            ['Cedarline Foods', 27600, 4],
            ['Vantage Point Realty', 53400, 5],
            ['NovaTrail Logistics', 15200, 6],
            ['BrightPeak Retail', 33750, 7],
        ];
        foreach ($wonPlan as $idx => [$companyName, $value, $monthsAgo]) {
            $owner     = $owners[$idx % count($owners)];
            $closedAt  = date('Y-m-d H:i:s', strtotime("-{$monthsAgo} months +5 days"));
            $this->insertDeal(
                companyName: $companyName,
                name: $companyName . ' — Renewal & Expansion',
                value: $value,
                stage: 'WON',
                owner: $owner,
                closedAt: $closedAt,
                createdAt: date('Y-m-d H:i:s', strtotime($closedAt . ' -45 days')),
            );
        }

        // 3 LOST deals, with reasons.
        $lostPlan = [
            ['Meridian Health Partners', 22000, 'Chose a competitor with a lower price point'],
            ['Old Mill Textiles', 15000, 'Budget frozen for the fiscal year'],
            ['Redwood Freight', 31000, 'Went with an in-house solution'],
        ];
        foreach ($lostPlan as $idx => [$companyName, $value, $reason]) {
            $owner = $owners[$idx % count($owners)];
            $this->insertDeal(
                companyName: $companyName,
                name: $companyName . ' — New Business',
                value: $value,
                stage: 'LOST',
                owner: $owner,
                closedAt: $this->now(20 + $idx * 10),
                lostReason: $reason,
                createdAt: $this->now(80 + $idx * 10),
            );
        }

        // Open pipeline — Prospecting / Proposal / Negotiation.
        $openPlan = [
            ['Foundry Robotics', 48000, 'PROSPECTING'],
            ['Lumen Analytics', 19500, 'PROSPECTING'],
            ['Harborview Hospitality', 26000, 'PROSPECTING'],
            ['Crestline Insurance', 37000, 'PROPOSAL'],
            ['Meridian Health Partners', 21000, 'PROPOSAL'],
            ['Foundry Robotics', 12500, 'PROPOSAL'],
            ['Lumen Analytics', 44000, 'NEGOTIATION'],
            ['Harborview Hospitality', 29500, 'NEGOTIATION'],
            ['Crestline Insurance', 58000, 'NEGOTIATION'],
        ];
        foreach ($openPlan as $idx => [$companyName, $value, $stage]) {
            $owner = $owners[$idx % count($owners)];
            $this->insertDeal(
                companyName: $companyName,
                name: $companyName . ' — New Business',
                value: $value,
                stage: $stage,
                owner: $owner,
                createdAt: $this->now(5 + $idx * 4),
            );
        }

        unset($companiesInOrder);
    }

    private function insertDeal(
        string $companyName,
        string $name,
        float $value,
        string $stage,
        string $owner,
        string $createdAt,
        ?string $closedAt = null,
        ?string $lostReason = null,
        ?int $leadId = null,
        ?int $contactId = null,
    ): int {
        $this->db->table('deals')->insert([
            'company_id'          => $this->companyIds[$companyName],
            'contact_id'          => $contactId,
            'lead_id'             => $leadId,
            'name'                => $name,
            'value_amount'        => $value,
            'expected_close_date' => $closedAt !== null ? substr($closedAt, 0, 10) : date('Y-m-d', strtotime($createdAt . ' +30 days')),
            'stage'               => $stage,
            'lost_reason'         => $lostReason,
            'owner_id'            => $this->userIds[$owner],
            'closed_at'           => $closedAt,
            'created_at'          => $createdAt,
            'updated_at'          => $closedAt ?? $createdAt,
        ]);

        return (int) $this->db->insertID();
    }

    // ---------------------------------------------------------------
    // Leads — every status, including a couple of full conversions
    // ---------------------------------------------------------------

    private function seedLeads(): void
    {
        $owners = ['Arjun Rep', 'Meera Rep', 'Karan Malhotra', 'Sana Iyer'];

        $new = [
            ['Jamie', 'Torres', 'Palisade Energy', 'WEBSITE'],
            ['Skyler', 'Brooks', 'Northbridge Logistics', 'REFERRAL'],
            ['Cameron', 'Diaz', 'Fernwood Studios', 'EVENT'],
        ];
        $contacted = [
            ['Peyton', 'Sato', 'Ashgrove Dental Group', 'COLD_CALL'],
            ['Drew', 'Okafor', 'Millbrook Financial', 'WEBSITE'],
            ['Bailey', 'Costa', 'Ironwood Fitness', 'REFERRAL'],
        ];
        $qualified = [
            ['Emerson', 'Volkov', 'Stonegate Architecture', 'WEBSITE'],
            ['Marlowe', 'Ahn', 'Brightwell Pharmacy', 'REFERRAL'],
        ];
        $disqualified = [
            ['Charlie', 'Reyes', 'Thistledown Media', 'COLD_CALL', 'No budget for at least two quarters'],
            ['Frankie', 'Larsen', 'Copperline Retailers', 'EVENT', 'Not a decision-maker, no follow-up'],
        ];

        foreach ($new as $idx => [$first, $last, $companyName, $source]) {
            $this->insertLead($first, $last, $companyName, $source, 'NEW', $owners[$idx % count($owners)], $this->now(2 + $idx));
        }
        foreach ($contacted as $idx => [$first, $last, $companyName, $source]) {
            $this->insertLead($first, $last, $companyName, $source, 'CONTACTED', $owners[$idx % count($owners)], $this->now(6 + $idx));
        }
        foreach ($qualified as $idx => [$first, $last, $companyName, $source]) {
            $this->insertLead($first, $last, $companyName, $source, 'QUALIFIED', $owners[$idx % count($owners)], $this->now(10 + $idx));
        }
        foreach ($disqualified as $idx => [$first, $last, $companyName, $source, $reason]) {
            $this->insertLead($first, $last, $companyName, $source, 'DISQUALIFIED', $owners[$idx % count($owners)], $this->now(15 + $idx), $reason);
        }

        // Two fully converted leads — company + contact + deal all created "by hand" the
        // way sp_lead_convert would, so the Lead detail screen's "View deal" link works.
        $this->convertDemoLead('Logan', 'Whitfield', 'Northfield Manufacturing', 'WEBSITE', 'Arjun Rep', 26000);
        $this->convertDemoLead('Rowan', 'Delgado', 'Cascade Retail Group', 'REFERRAL', 'Sana Iyer', 17500);
    }

    private function insertLead(
        string $first,
        string $last,
        string $companyName,
        string $source,
        string $status,
        string $owner,
        string $createdAt,
        ?string $disqualifyReason = null,
    ): void {
        $this->db->table('leads')->insert([
            'first_name'        => $first,
            'last_name'         => $last,
            'email'             => strtolower("{$first}.{$last}@example.com"),
            'phone'             => '+1-555-' . random_int(1000, 9999),
            'company_name'      => $companyName,
            'source'            => $source,
            'status'            => $status,
            'disqualify_reason' => $disqualifyReason,
            'owner_id'          => $this->userIds[$owner],
            'created_at'        => $createdAt,
            'updated_at'        => $createdAt,
        ]);
    }

    private function convertDemoLead(string $first, string $last, string $companyName, string $source, string $owner, float $dealValue): void
    {
        $createdAt   = $this->now(60);
        $convertedAt = $this->now(30);

        $this->db->table('leads')->insert([
            'first_name'   => $first,
            'last_name'    => $last,
            'email'        => strtolower("{$first}.{$last}@example.com"),
            'phone'        => '+1-555-' . random_int(1000, 9999),
            'company_name' => $companyName,
            'source'       => $source,
            'status'       => 'CONVERTED',
            'owner_id'     => $this->userIds[$owner],
            'converted_at' => $convertedAt,
            'created_at'   => $createdAt,
            'updated_at'   => $convertedAt,
        ]);
        $leadId = (int) $this->db->insertID();

        $this->db->table('companies')->insert([
            'name'       => $companyName,
            'owner_id'   => $this->userIds[$owner],
            'status'     => 'CUSTOMER',
            'created_at' => $convertedAt,
            'updated_at' => $convertedAt,
        ]);
        $companyId                    = (int) $this->db->insertID();
        $this->companyIds[$companyName] = $companyId;

        $this->db->table('contacts')->insert([
            'company_id' => $companyId,
            'first_name' => $first,
            'last_name'  => $last,
            'email'      => strtolower("{$first}.{$last}@example.com"),
            'owner_id'   => $this->userIds[$owner],
            'created_at' => $convertedAt,
            'updated_at' => $convertedAt,
        ]);
        $contactId = (int) $this->db->insertID();
        $this->contactIds["{$companyName}::{$first} {$last}"] = $contactId;

        $dealId = $this->insertDeal(
            companyName: $companyName,
            name: $companyName . ' — New Business',
            value: $dealValue,
            stage: 'WON',
            owner: $owner,
            closedAt: $this->now(5),
            leadId: $leadId,
            contactId: $contactId,
            createdAt: $convertedAt,
        );

        $this->db->table('leads')->where('id', $leadId)->update([
            'converted_company_id' => $companyId,
            'converted_contact_id' => $contactId,
            'converted_deal_id'    => $dealId,
        ]);
    }

    // ---------------------------------------------------------------
    // Tasks
    // ---------------------------------------------------------------

    private function seedTasks(): void
    {
        $companyNames = array_slice(array_keys($this->companyIds), 0, 8);
        $owners       = ['Arjun Rep', 'Meera Rep', 'Karan Malhotra', 'Sana Iyer'];

        $plan = [
            ['Follow up on renewal terms', 'HIGH', -2, null],
            ['Send updated pricing sheet', 'MEDIUM', 1, null],
            ['Schedule quarterly business review', 'MEDIUM', 5, null],
            ['Prepare proposal deck', 'HIGH', 2, null],
            ['Check in after onboarding', 'LOW', 10, null],
            ['Confirm contract signature', 'HIGH', -1, null],
            ['Call back about support ticket', 'MEDIUM', 0, null],
            ['Review contract redlines', 'HIGH', 3, null],
            ['Send holiday greeting note', 'LOW', 20, null],
            ['Warm intro to procurement lead', 'MEDIUM', 7, null],
        ];

        foreach ($plan as $idx => [$subject, $priority, $dueInDays, $_unused]) {
            $owner       = $owners[$idx % count($owners)];
            $companyName = $companyNames[$idx % count($companyNames)];
            $isOverdueButDone = $dueInDays < 0 && $idx % 2 === 0;

            $this->db->table('tasks')->insert([
                'subject'         => $subject,
                'due_date'        => date('Y-m-d', strtotime("{$dueInDays} days")),
                'priority'        => $priority,
                'related_to_type' => 'COMPANY',
                'related_to_id'   => $this->companyIds[$companyName],
                'assigned_to'     => $this->userIds[$owner],
                'created_by'      => $this->userIds[$owner],
                'completed_at'    => $isOverdueButDone ? $this->now(1) : null,
                'created_at'      => $this->now(14 - $idx),
                'updated_at'      => $this->now(1),
            ]);
        }
    }

    // ---------------------------------------------------------------
    // Activities
    // ---------------------------------------------------------------

    private function seedActivities(): void
    {
        $companyNames = array_slice(array_keys($this->companyIds), 0, 10);
        $owners       = ['Arjun Rep', 'Meera Rep', 'Karan Malhotra', 'Sana Iyer'];

        $notes = [
            ['CALL', 'Kickoff call', 'Walked through onboarding timeline and next steps.'],
            ['EMAIL', 'Sent proposal', 'Emailed the updated proposal with revised pricing.'],
            ['MEETING', 'Quarterly review', 'Reviewed usage metrics and expansion opportunities.'],
            ['NOTE', null, 'Prefers email over phone — mentioned in first call.'],
            ['CALL', 'Renewal discussion', 'Discussed renewal terms; they want a multi-year discount.'],
            ['NOTE', null, 'Champion is moving to a new role in Q2 — flag for relationship risk.'],
            ['EMAIL', 'Follow-up after demo', 'Sent a recap and answered their security questionnaire.'],
            ['MEETING', 'Contract review', 'Legal walked through redlines; two clauses still open.'],
            ['CALL', 'Check-in', 'Quick check-in call, all going well post-launch.'],
            ['NOTE', null, 'Budget cycle resets in January — revisit expansion conversation then.'],
            ['EMAIL', 'Pricing questions', 'Answered questions about volume discount tiers.'],
            ['MEETING', 'Executive intro', 'Introduced our VP of Sales to their COO.'],
        ];

        foreach ($notes as $idx => [$type, $subject, $body]) {
            $owner       = $owners[$idx % count($owners)];
            $companyName = $companyNames[$idx % count($companyNames)];

            $this->db->table('activities')->insert([
                'type'            => $type,
                'subject'         => $subject,
                'body'            => $body,
                'occurred_at'     => $this->now($idx * 2),
                'related_to_type' => 'COMPANY',
                'related_to_id'   => $this->companyIds[$companyName],
                'created_by'      => $this->userIds[$owner],
                'created_at'      => $this->now($idx * 2),
            ]);
        }
    }

    // ---------------------------------------------------------------
    // Audit log — a handful of illustrative entries
    // ---------------------------------------------------------------

    private function seedAuditLogs(): void
    {
        $entries = [
            ['COMPANY_CREATED', 'COMPANY', $this->companyIds['NovaTrail Logistics'], ['name' => 'NovaTrail Logistics'], 'Arjun Rep', 149],
            ['LEAD_CONVERTED', 'LEAD', 1, ['companyId' => $this->companyIds['Northfield Manufacturing'] ?? null], 'Arjun Rep', 30],
            ['DEAL_STAGE_CHANGED', 'DEAL', 1, ['from' => 'NEGOTIATION', 'to' => 'WON'], 'Meera Rep', 5],
            ['TASK_COMPLETED', 'TASK', 1, (object) [], 'Karan Malhotra', 1],
            ['USER_INVITED', 'USER', $this->userIds['Sana Iyer'], ['email' => 'sana.rep@brightfield.test', 'role' => 'SALES_REP'], 'Ava Admin', 179],
            ['LEAD_DISQUALIFIED', 'LEAD', 2, ['reason' => 'No budget for at least two quarters'], 'Karan Malhotra', 15],
            ['COMPANY_CREATED', 'COMPANY', $this->companyIds['Foundry Robotics'], ['name' => 'Foundry Robotics'], 'Meera Rep', 120],
            ['DEAL_STAGE_CHANGED', 'DEAL', 2, ['from' => 'PROPOSAL', 'to' => 'LOST'], 'Sana Iyer', 20],
        ];

        foreach ($entries as [$action, $entityType, $entityId, $details, $actor, $daysAgo]) {
            $this->db->table('audit_logs')->insert([
                'user_id'     => $this->userIds[$actor],
                'action'      => $action,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
                'details'     => json_encode($details),
                'created_at'  => $this->now($daysAgo),
            ]);
        }
    }
}
