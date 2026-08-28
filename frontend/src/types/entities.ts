import type {
  ActivityType,
  CompanyStatus,
  DealStage,
  LeadSource,
  LeadStatus,
  RelatableType,
  TaskPriority,
  UserRole,
} from '@shared/constants'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
  managerId: number | null
  status: 'ACTIVE' | 'DISABLED'
  createdAt: string
  updatedAt: string
}

export interface Company {
  id: number
  name: string
  industry: string | null
  website: string | null
  phone: string | null
  status: CompanyStatus
  ownerId: number
  createdAt: string
  updatedAt: string
}

export interface Contact {
  id: number
  companyId: number
  firstName: string
  lastName: string
  email: string | null
  phone: string | null
  jobTitle: string | null
  ownerId: number
  createdAt: string
  updatedAt: string
}

export interface Lead {
  id: number
  firstName: string
  lastName: string
  email: string | null
  phone: string | null
  companyName: string | null
  source: LeadSource
  status: LeadStatus
  disqualifyReason: string | null
  ownerId: number
  convertedAt: string | null
  convertedCompanyId: number | null
  convertedContactId: number | null
  convertedDealId: number | null
  createdAt: string
  updatedAt: string
}

export interface Deal {
  id: number
  companyId: number
  contactId: number | null
  leadId: number | null
  name: string
  valueAmount: string
  expectedCloseDate: string | null
  stage: DealStage
  lostReason: string | null
  ownerId: number
  closedAt: string | null
  createdAt: string
  updatedAt: string
}

export interface Task {
  id: number
  subject: string
  dueDate: string | null
  priority: TaskPriority
  relatedToType: RelatableType | null
  relatedToId: number | null
  assignedTo: number
  createdBy: number
  completedAt: string | null
  createdAt: string
  updatedAt: string
}

export interface Activity {
  id: number
  type: ActivityType
  subject: string | null
  body: string
  occurredAt: string
  relatedToType: RelatableType
  relatedToId: number
  createdBy: number
  createdAt: string
}

export interface AuditLogEntry {
  id: number
  userId: number | null
  action: string
  entityType: string
  entityId: number | null
  details: Record<string, unknown> | null
  createdAt: string
}
