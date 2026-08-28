import { Navigate, Route, Routes } from 'react-router-dom'
import { LoginPage } from '@/features/auth/LoginPage'
import { DashboardPage } from '@/features/dashboard/DashboardPage'
import { CompaniesListPage } from '@/features/companies/CompaniesListPage'
import { CompanyDetailPage } from '@/features/companies/CompanyDetailPage'
import { ContactsListPage } from '@/features/contacts/ContactsListPage'
import { LeadsBoardPage } from '@/features/leads/LeadsBoardPage'
import { LeadDetailPage } from '@/features/leads/LeadDetailPage'
import { DealsBoardPage } from '@/features/deals/DealsBoardPage'
import { DealDetailPage } from '@/features/deals/DealDetailPage'
import { TasksPage } from '@/features/tasks/TasksPage'
import { ReportsPage } from '@/features/reports/ReportsPage'
import { UsersPage } from '@/features/users/UsersPage'
import { AuditLogPage } from '@/features/audit-log/AuditLogPage'
import { Layout } from './Layout'
import { RequireAuth, RequireRole } from './RequireAuth'
import { NotFoundPage } from './NotFoundPage'

export function AppRoutes() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />
      <Route path="/not-found" element={<NotFoundPage />} />

      <Route element={<RequireAuth />}>
        <Route element={<Layout />}>
          <Route index element={<Navigate to="/dashboard" replace />} />
          <Route path="/dashboard" element={<DashboardPage />} />
          <Route path="/companies" element={<CompaniesListPage />} />
          <Route path="/companies/:id" element={<CompanyDetailPage />} />
          <Route path="/contacts" element={<ContactsListPage />} />
          <Route path="/leads" element={<LeadsBoardPage />} />
          <Route path="/leads/:id" element={<LeadDetailPage />} />
          <Route path="/deals" element={<DealsBoardPage />} />
          <Route path="/deals/:id" element={<DealDetailPage />} />
          <Route path="/tasks" element={<TasksPage />} />
          <Route path="/reports" element={<ReportsPage />} />

          <Route element={<RequireRole roles={['ADMIN']} />}>
            <Route path="/admin/users" element={<UsersPage />} />
            <Route path="/admin/audit-log" element={<AuditLogPage />} />
          </Route>
        </Route>
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  )
}
