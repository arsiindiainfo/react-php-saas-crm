import { NavLink, Outlet } from 'react-router-dom'
import { useAuth } from '@/features/auth/AuthContext'
import arsiLogo from '@/assets/arsi-logo.png'

const NAV_ITEMS = [
  { to: '/dashboard', label: 'Dashboard' },
  { to: '/companies', label: 'Companies' },
  { to: '/contacts', label: 'Contacts' },
  { to: '/leads', label: 'Leads' },
  { to: '/deals', label: 'Deals' },
  { to: '/tasks', label: 'Tasks' },
  { to: '/reports', label: 'Reports' },
]

const ADMIN_NAV_ITEMS = [
  { to: '/admin/users', label: 'Team' },
  { to: '/admin/audit-log', label: 'Audit Log' },
]

export function Layout() {
  const { user, logout } = useAuth()

  return (
    <div className="flex min-h-screen">
      <aside className="w-56 shrink-0 bg-gray-900 text-gray-200">
        <div className="border-b border-white/10 px-5 py-4">
          <div className="mb-2 inline-block rounded-md bg-white px-2 py-1.5">
            <img src={arsiLogo} alt="Arsi India Info" className="h-6 w-auto" />
          </div>
          <div className="text-sm font-bold text-white">Arsi CRM</div>
          <div className="text-xs text-gray-400">Brightfield Business Solutions</div>
        </div>
        <nav className="p-3">
          {NAV_ITEMS.map((item) => (
            <NavLink
              key={item.to}
              to={item.to}
              className={({ isActive }) =>
                `block rounded-md px-3 py-2 text-sm ${isActive ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5'}`
              }
            >
              {item.label}
            </NavLink>
          ))}
          {user?.role === 'ADMIN' && (
            <>
              <div className="mt-4 mb-1 px-3 text-[10px] font-bold uppercase tracking-wide text-gray-500">Admin</div>
              {ADMIN_NAV_ITEMS.map((item) => (
                <NavLink
                  key={item.to}
                  to={item.to}
                  className={({ isActive }) =>
                    `block rounded-md px-3 py-2 text-sm ${isActive ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5'}`
                  }
                >
                  {item.label}
                </NavLink>
              ))}
            </>
          )}
        </nav>
      </aside>

      <div className="flex-1">
        <header className="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-3">
          <div />
          <div className="flex items-center gap-3 text-sm">
            <span className="text-gray-700">{user?.name}</span>
            <button onClick={() => void logout()} className="text-gray-400 hover:text-gray-700">
              Sign out
            </button>
          </div>
        </header>
        <main className="p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}
