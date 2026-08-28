import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { useAuth } from '@/features/auth/AuthContext'
import { useSalespersonPerformance } from '@/features/reports/api'
import { StatCard } from '@/components/StatCard'
import { useDashboardSummary } from './api'

export function DashboardPage() {
  const { user } = useAuth()
  const { data, isLoading } = useDashboardSummary()
  const canSeeLeaderboard = user?.role === 'ADMIN' || user?.role === 'SALES_MANAGER'
  const { data: leaderboard } = useSalespersonPerformance(canSeeLeaderboard)

  if (isLoading || !data) {
    return <p className="text-sm text-gray-400">Loading…</p>
  }

  const { totals, monthlyTrend } = data

  return (
    <div>
      <h1 className="mb-4 text-lg font-bold text-gray-900">Dashboard</h1>

      <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <StatCard label="Customers" value={String(totals.totalCompanies)} emptyHint="No customers yet" />
        <StatCard label="New Leads" value={String(totals.newLeadsThisMonth)} emptyHint="No activity yet this month" />
        <StatCard label="Conv. Rate" value={`${totals.conversionRate}%`} emptyHint="No activity yet this month" />
        <StatCard label="Open Deals" value={`${totals.openDealsCount} · $${Number(totals.openDealsValue).toLocaleString()}`} emptyHint="No open deals" />
        <StatCard label="Revenue MTD" value={`$${Number(totals.revenueThisMonth).toLocaleString()}`} emptyHint="No activity yet this month" />
      </div>

      <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm lg:col-span-2">
          <h2 className="mb-3 text-sm font-semibold text-gray-700">Monthly Sales — last 12 months</h2>
          {monthlyTrend.every((m) => Number(m.revenue) === 0) ? (
            <p className="py-10 text-center text-sm text-gray-400">No won deals yet — nothing to chart.</p>
          ) : (
            <ResponsiveContainer width="100%" height={220}>
              <BarChart data={monthlyTrend}>
                <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                <YAxis tick={{ fontSize: 11 }} />
                <Tooltip />
                <Bar dataKey="revenue" fill="#2563eb" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        {canSeeLeaderboard && (
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-gray-700">Top Salespeople</h2>
            {!leaderboard || leaderboard.length === 0 ? (
              <p className="text-sm text-gray-400">Not enough closed deals yet to show performance.</p>
            ) : (
              <ol className="space-y-2 text-sm">
                {leaderboard.slice(0, 5).map((row, i) => (
                  <li key={row.userId} className="flex justify-between">
                    <span>
                      {i + 1}. {row.name}
                    </span>
                    <span className="text-gray-500">${Number(row.revenue).toLocaleString()} won</span>
                  </li>
                ))}
              </ol>
            )}
          </div>
        )}
      </div>
    </div>
  )
}
