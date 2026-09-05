import type { DealStage, LeadStatus, LeadSource } from '@shared/constants'

interface StageTheme {
  /** Tailwind classes for a tinted Kanban column header. */
  header: string
  dot: string
  /** Hex used by Recharts, which doesn't resolve Tailwind classes. */
  hex: string
}

export const DEAL_STAGE_THEME: Record<DealStage, StageTheme> = {
  PROSPECTING: { header: 'bg-violet-50 text-violet-700', dot: 'bg-violet-500', hex: '#8b5cf6' },
  PROPOSAL: { header: 'bg-blue-50 text-blue-700', dot: 'bg-blue-500', hex: '#3b82f6' },
  NEGOTIATION: { header: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500', hex: '#f59e0b' },
  WON: { header: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', hex: '#10b981' },
  LOST: { header: 'bg-red-50 text-red-700', dot: 'bg-red-500', hex: '#ef4444' },
}

export const LEAD_STATUS_THEME: Record<LeadStatus, StageTheme> = {
  NEW: { header: 'bg-violet-50 text-violet-700', dot: 'bg-violet-500', hex: '#8b5cf6' },
  CONTACTED: { header: 'bg-blue-50 text-blue-700', dot: 'bg-blue-500', hex: '#3b82f6' },
  QUALIFIED: { header: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500', hex: '#f59e0b' },
  CONVERTED: { header: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500', hex: '#10b981' },
  DISQUALIFIED: { header: 'bg-gray-100 text-gray-600', dot: 'bg-gray-400', hex: '#9ca3af' },
}

export const DEAL_STAGE_LABELS: Record<DealStage, string> = {
  PROSPECTING: 'Prospecting',
  PROPOSAL: 'Proposal',
  NEGOTIATION: 'Negotiation',
  WON: 'Won',
  LOST: 'Lost',
}

/** Stages that still count as "in the pipeline" — excludes closed outcomes. */
export const OPEN_DEAL_STAGES: readonly DealStage[] = ['PROSPECTING', 'PROPOSAL', 'NEGOTIATION']

export const LEAD_STATUS_LABELS: Record<LeadStatus, string> = {
  NEW: 'New',
  CONTACTED: 'Contacted',
  QUALIFIED: 'Qualified',
  CONVERTED: 'Converted',
  DISQUALIFIED: 'Disqualified',
}

export const LEAD_SOURCE_LABELS: Record<LeadSource, string> = {
  WEBSITE: 'Website',
  REFERRAL: 'Referral',
  COLD_CALL: 'Cold Call',
  EVENT: 'Event',
  OTHER: 'Other',
}
