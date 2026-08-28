/**
 * Enum-like lists mirrored 1:1 with `backend/app/Config/Crm.php`. TypeScript
 * and PHP can't literally share a module across this repo's two runtimes, so
 * this file (and its backend counterpart) must be kept in sync by hand —
 * see React-PHP-SaaS-CRM-PLAN.html §5 "Logging & configuration".
 */

export const USER_ROLES = ['ADMIN', 'SALES_MANAGER', 'SALES_REP'] as const
export type UserRole = (typeof USER_ROLES)[number]

export const COMPANY_STATUSES = ['PROSPECT', 'CUSTOMER', 'CHURNED'] as const
export type CompanyStatus = (typeof COMPANY_STATUSES)[number]

export const LEAD_SOURCES = ['WEBSITE', 'REFERRAL', 'COLD_CALL', 'EVENT', 'OTHER'] as const
export type LeadSource = (typeof LEAD_SOURCES)[number]

export const LEAD_STATUSES = ['NEW', 'CONTACTED', 'QUALIFIED', 'CONVERTED', 'DISQUALIFIED'] as const
export type LeadStatus = (typeof LEAD_STATUSES)[number]

export const DEAL_STAGES = ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION', 'WON', 'LOST'] as const
export type DealStage = (typeof DEAL_STAGES)[number]

export const CLOSED_DEAL_STAGES: readonly DealStage[] = ['WON', 'LOST']

export const TASK_PRIORITIES = ['LOW', 'MEDIUM', 'HIGH'] as const
export type TaskPriority = (typeof TASK_PRIORITIES)[number]

export const ACTIVITY_TYPES = ['NOTE', 'CALL', 'EMAIL', 'MEETING'] as const
export type ActivityType = (typeof ACTIVITY_TYPES)[number]

export const RELATABLE_TYPES = ['COMPANY', 'CONTACT', 'LEAD', 'DEAL'] as const
export type RelatableType = (typeof RELATABLE_TYPES)[number]
