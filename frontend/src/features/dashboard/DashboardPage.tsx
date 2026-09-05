import { Bar, BarChart, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Users, UserPlus2, TrendingUp, Briefcase, DollarSign } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthContext'
import { useSalespersonPerformance, usePipelineByStage } from '@/features/reports/api'
import { StatCard } from '@/components/StatCard'
import { PageSpinner } from '@/components/Spinner'
import { DEAL_STAGE_THEME, DEAL_STAGE_LABELS, OPEN_DEAL_STAGES } from '@/lib/stageTheme'
import { useDashboardSummary } from './api'

export function DashboardPage() {
  const { user } = useAuth()
  const { data, isLoading } = useDashboardSummary()
  const canSeeLeaderboard = user?.role === 'ADMIN' || user?.role === 'SALES_MANAGER'
  const { data: leaderboard } = useSalespersonPerformance(canSeeLeaderboard)
  const { data: pipeline } = usePipelineByStage()

  if (isLoading || !data) {
    return <PageSpinner label="Loading dashboard…" />
  }

  const { totals, monthlyTrend } = data
  const firstName = user?.name?.split(' ')[0]

  const openPipeline = OPEN_DEAL_STAGES.map((stage) => {
    const bucket = pipeline?.find((p) => p.stage === stage)
    return { stage, count: Number(bucket?.count ?? 0), value: Number(bucket?.value ?? 0) }
  }).filter((s) => s.count > 0)
  const totalOpenDeals = openPipeline.reduce((sum, s) => sum + s.count, 0)

  return (
    <div>
      <h1 className="text-lg font-bold text-gray-900">Dashboard</h1>
      <p className="mb-4 text-sm text-gray-500">
        {firstName ? `Welcome back, ${firstName}! Here's what's happening with your business today.` : 'Here’s what’s happening with your business today.'}
      </p>

      <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <StatCard label="Customers" value={String(totals.totalCompanies)} emptyHint="No customers yet" icon={Users} tone="indigo" />
        <StatCard label="New Leads" value={String(totals.newLeadsThisMonth)} emptyHint="No activity yet this month" icon={UserPlus2} tone="green" />
        <StatCard label="Conv. Rate" value={`${totals.conversionRate}%`} emptyHint="No activity yet this month" icon={TrendingUp} tone="orange" />
        <StatCard label="Open Deals" value={`${totals.openDealsCount} · $${Number(totals.openDealsValue).toLocaleString()}`} emptyHint="No open deals" icon={Briefcase} tone="blue" />
        <StatCard label="Revenue MTD" value={`$${Number(totals.revenueThisMonth).toLocaleString()}`} emptyHint="No activity yet this month" icon={DollarSign} tone="pink" />
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
                <Bar dataKey="revenue" fill="#4f46e5" radius={[4, 4, 0, 0]} />
              </BarChart>
            </ResponsiveContainer>
          )}
        </div>

        <div className="flex flex-col gap-4">
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

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-gray-700">Deal Pipeline Overview</h2>
            {openPipeline.length === 0 ? (
              <p className="py-6 text-center text-sm text-gray-400">No open deals right now.</p>
            ) : (
              <div className="flex items-center gap-4">
                <div className="relative h-[140px] w-[140px] shrink-0">
                  <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                      <Pie data={openPipeline} dataKey="count" nameKey="stage" innerRadius={40} outerRadius={65} startAngle={90} endAngle={-270}>
                        {openPipeline.map((s) => (
                          <Cell key={s.stage} fill={DEAL_STAGE_THEME[s.stage].hex} />
                        ))}
                      </Pie>
                      <Tooltip
                        formatter={(value, _name, entry) => {
                          const stage = (entry.payload as { stage: keyof typeof DEAL_STAGE_LABELS }).stage
                          return [`${value} deal(s)`, DEAL_STAGE_LABELS[stage]]
                        }}
                      />
                    </PieChart>
                  </ResponsiveContainer>
                  <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-xl font-bold text-gray-900">{totalOpenDeals}</span>
                    <span className="text-[10px] uppercase tracking-wide text-gray-400">Open Deals</span>
                  </div>
                </div>
                <ul className="min-w-0 flex-1 space-y-1.5 text-xs">
                  {openPipeline.map((s) => (
                    <li key={s.stage} className="flex items-center justify-between gap-2">
                      <span className="flex items-center gap-1.5 truncate text-gray-600">
                        <span className="h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: DEAL_STAGE_THEME[s.stage].hex }} />
                        {DEAL_STAGE_LABELS[s.stage]}
                      </span>
                      <span className="shrink-0 font-semibold text-gray-900">${s.value.toLocaleString()}</span>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  )
}
