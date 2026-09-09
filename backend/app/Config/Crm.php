<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Centralized business config (§5) — token TTLs, pagination limits, and the
 * pipeline enum lists shared by API validation and the frontend's mirrored
 * Zod schemas (kept in sync manually with `shared/` constants).
 */
class Crm extends BaseConfig
{
    public int $accessTokenTtl  = 15 * 60;         // 15 minutes
    public int $refreshTokenTtl = 30 * 24 * 60 * 60; // 30 days

    public int $defaultPageLimit = 20;
    public int $maxPageLimit     = 100;

    public int $loginRateLimitPerMinute = 10;

    /** Owner account — can't be disabled or removed by any admin, including itself. */
    public string $protectedUserEmail = 'arsi.india.info@gmail.com';

    /** @var list<string> */
    public array $userRoles = ['ADMIN', 'SALES_MANAGER', 'SALES_REP'];

    /** @var list<string> */
    public array $companyStatuses = ['PROSPECT', 'CUSTOMER', 'CHURNED'];

    /** @var list<string> */
    public array $leadSources = ['WEBSITE', 'REFERRAL', 'COLD_CALL', 'EVENT', 'OTHER'];

    /** @var list<string> */
    public array $leadStatuses = ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'DISQUALIFIED'];

    /** @var list<string> */
    public array $dealStages = ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION', 'WON', 'LOST'];

    /** @var list<string> terminal deal stages that reject further transitions */
    public array $closedDealStages = ['WON', 'LOST'];

    /** @var list<string> */
    public array $taskPriorities = ['LOW', 'MEDIUM', 'HIGH'];

    /** @var list<string> */
    public array $activityTypes = ['NOTE', 'CALL', 'EMAIL', 'MEETING'];

    /** @var list<string> valid `related_to_type` targets for Tasks/Activities (§7.4) */
    public array $relatableTypes = ['COMPANY', 'CONTACT', 'LEAD', 'DEAL'];
}
