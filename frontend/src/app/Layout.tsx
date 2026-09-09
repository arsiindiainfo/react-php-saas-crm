import { useState } from 'react'
import { NavLink, Outlet } from 'react-router-dom'
import {
  LayoutDashboard,
  Building2,
  Users,
  UserPlus,
  Briefcase,
  CheckSquare,
  BarChart3,
  UserCog,
  FileClock,
  Menu,
  X,
  ChevronDown,
  LogOut,
} from 'lucide-react'
import { useAuth } from '@/features/auth/AuthContext'
import { Footer } from '@/components/Footer'
import arsiLogo from '@/assets/arsi-logo.png'

const NAV_ITEMS = [
  { to: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { to: '/companies', label: 'Companies', icon: Building2 },
  { to: '/contacts', label: 'Contacts', icon: Users },
  { to: '/leads', label: 'Leads', icon: UserPlus },
  { to: '/deals', label: 'Deals', icon: Briefcase },
  { to: '/tasks', label: 'Tasks', icon: CheckSquare },
  { to: '/reports', label: 'Reports', icon: BarChart3 },
]

const ADMIN_NAV_ITEMS = [
  { to: '/admin/users', label: 'Team', icon: UserCog },
  { to: '/admin/audit-log', label: 'Audit Log', icon: FileClock },
]

const ROLE_LABELS: Record<string, string> = {
  ADMIN: 'Admin',
  SALES_MANAGER: 'Sales Manager',
  SALES_REP: 'Sales Rep',
}

function initialsOf(name: string | undefined) {
  if (!name) return '?'
  const parts = name.trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase()
}

export function Layout() {
  const { user, logout } = useAuth()
  const [isMobileNavOpen, setIsMobileNavOpen] = useState(false)
  const [isUserMenuOpen, setIsUserMenuOpen] = useState(false)

  function navLinkClasses({ isActive }: { isActive: boolean }) {
    return `flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors ${
      isActive ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'
    }`
  }

  function closeMobileNav() {
    setIsMobileNavOpen(false)
  }

  const sidebarContent = (
    <>
      <div className="flex items-center justify-between border-b border-white/10 px-5 py-4">
        <div>
          <div className="mb-2 inline-block rounded-md bg-white px-3 py-2.5">
            <img src={arsiLogo} alt="Arsi India Info" className="h-12 w-auto" />
          </div>
          <div className="text-sm font-bold text-white">Arsi CRM</div>
          <div className="text-xs text-gray-400">Brightfield Business Solutions</div>
        </div>
        <button
          type="button"
          onClick={closeMobileNav}
          className="rounded-md p-1.5 text-gray-400 hover:bg-white/5 hover:text-white lg:hidden"
          aria-label="Close menu"
        >
          <X size={20} />
        </button>
      </div>

      <nav className="flex-1 overflow-y-auto p-3">
        <div className="space-y-1">
          {NAV_ITEMS.map((item) => (
            <NavLink key={item.to} to={item.to} className={navLinkClasses} onClick={closeMobileNav}>
              <item.icon size={18} />
              {item.label}
            </NavLink>
          ))}
        </div>
        {user?.role === 'ADMIN' && (
          <>
            <div className="mt-5 mb-1 px-3 text-[10px] font-bold uppercase tracking-wide text-gray-500">Admin</div>
            <div className="space-y-1">
              {ADMIN_NAV_ITEMS.map((item) => (
                <NavLink key={item.to} to={item.to} className={navLinkClasses} onClick={closeMobileNav}>
                  <item.icon size={18} />
                  {item.label}
                </NavLink>
              ))}
            </div>
          </>
        )}
      </nav>

      <div className="relative border-t border-white/10 p-3">
        {isUserMenuOpen && (
          <button
            type="button"
            onClick={() => {
              setIsUserMenuOpen(false)
              void logout()
            }}
            className="absolute inset-x-3 bottom-[calc(100%+4px)] flex items-center gap-2 rounded-lg border border-white/10 bg-gray-800 px-3 py-2 text-sm font-medium text-gray-200 shadow-lg hover:bg-gray-700"
          >
            <LogOut size={16} />
            Sign out
          </button>
        )}
        <button
          type="button"
          onClick={() => setIsUserMenuOpen((v) => !v)}
          className="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left hover:bg-white/5"
        >
          <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
            {initialsOf(user?.name)}
          </span>
          <span className="min-w-0 flex-1">
            <span className="block truncate text-sm font-semibold text-white">{user?.name}</span>
            <span className="block truncate text-xs text-gray-400">{user ? ROLE_LABELS[user.role] : ''}</span>
          </span>
          <ChevronDown size={16} className={`shrink-0 text-gray-400 transition-transform ${isUserMenuOpen ? 'rotate-180' : ''}`} />
        </button>
      </div>
    </>
  )

  return (
    <div className="flex min-h-screen flex-col">
      <div className="flex flex-1">
        {/* Mobile overlay */}
        {isMobileNavOpen && (
          <div className="fixed inset-0 z-30 bg-black/40 lg:hidden" onClick={closeMobileNav} aria-hidden="true" />
        )}

        {/* Sidebar — off-canvas drawer on mobile, fixed column on desktop */}
        <aside
          className={`fixed inset-y-0 left-0 z-40 flex w-64 shrink-0 flex-col bg-gray-900 text-gray-200 transition-transform duration-200 lg:static lg:translate-x-0 ${
            isMobileNavOpen ? 'translate-x-0' : '-translate-x-full'
          }`}
        >
          {sidebarContent}
        </aside>

        <div className="flex min-w-0 flex-1 flex-col">
          <header className="flex items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 lg:hidden">
            <button
              type="button"
              onClick={() => setIsMobileNavOpen(true)}
              className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100"
              aria-label="Open menu"
            >
              <Menu size={22} />
            </button>
            <span className="text-sm font-bold text-gray-900">Arsi CRM</span>
          </header>
          <main className="flex-1 p-4 sm:p-6">
            <Outlet />
          </main>
        </div>
      </div>
      <Footer />
    </div>
  )
}
