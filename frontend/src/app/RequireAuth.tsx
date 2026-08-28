import { Navigate, Outlet } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthContext'
import type { UserRole } from '@shared/constants'

export function RequireAuth() {
  const { user, isLoading } = useAuth()

  if (isLoading) return null
  if (!user) return <Navigate to="/login" replace />

  return <Outlet />
}

/** §22.10/§22.11 — admin-only routes. A non-admin who reaches these via a stale link gets a plain 404, not an access-denied page (§23). */
export function RequireRole({ roles }: { roles: UserRole[] }) {
  const { user } = useAuth()

  if (!user || !roles.includes(user.role)) {
    return <Navigate to="/not-found" replace />
  }

  return <Outlet />
}
