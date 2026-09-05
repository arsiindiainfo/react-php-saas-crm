import { Bar, BarChart, Cell, LabelList, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { Briefcase, DollarSign, Trophy, TrendingUp, XCircle, Download } from 'lucide-react'
import { useAuth } from '@/features/auth/AuthContext'
import { useListQuery } from '@/lib/useListQuery'
import { Spinner, PageSpinner } from '@/components/Spinner'
import { StatCard } from '@/components/StatCard'
import { DEAL_STAGE_LABELS, LEAD_SOURCE_LABELS } from '@/lib/stageTheme'
import { DEAL_STAGES } from '@shared/constants'
import type { LeadSource } from '@shared/constants'
import type { Lead } from '@/types/entities'
import { usePipelineByStage, useMonthlySales, useSalespersonPerformance } from './api'

const SOURCE_COLORS: Record<LeadSource, string> = {
  WEBSITE: '#4f46e5',
  REFERRAL: '#0d9488',
  COLD_CALL: '#f59e0b',
  EVENT: '#ec4899',
  OTHER: '#6b7280',
}

function initialsOf(name: string) {
  const parts = name.trim().split(/\s+/)
  return ((parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '')).toUpperCase()
}

function formatK(value: number) {
  return value >= 1000 ? `${Math.round(value / 1000)}K` : String(value)
}

function downloadCsv(filename: string, rows: (string | number)[][]) {
  const csv = rows.map((row) => row.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(',')).join('\n')
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  link.click()
  URL.revokeObjectURL(url)
}

export function ReportsPage() {
  const { user } = useAuth()
  const canSeeLeaderboard = user?.role === 'ADMIN' || user?.role === 'SALES_MANAGER'
  const { data: pipeline, isLoading: isPipelineLoading } = usePipelineByStage()
  const { data: monthly, isLoading: isMonthlyLoading } = useMonthlySales()
  const { data: performance, isLoading: isPerformanceLoading } = useSalespersonPerformance(canSeeLeaderboard)
  const { data: leadsPage, isLoading: isLeadsLoading } = useListQuery<Lead>('leads', { limit: 200 })

  const isLoading = isPipelineLoading || isMonthlyLoading

  const pipelineByStage = DEAL_STAGES.map((stage) => {
    const bucket = pipeline?.find((p) => p.stage === stage)
    return { stage, label: DEAL_STAGE_LABELS[stage], count: Number(bucket?.count ?? 0), value: Number(bucket?.value ?? 0) }
  })
  const totalDeals = pipelineByStage.reduce((sum, s) => sum + s.count, 0)
  const totalRevenue = pipelineByStage.reduce((sum, s) => sum + s.value, 0)
  const wonBucket = pipelineByStage.find((s) => s.stage === 'WON')
  const lostBucket = pipelineByStage.find((s) => s.stage === 'LOST')
  const wonDeals = wonBucket?.count ?? 0
  const lostDeals = lostBucket?.count ?? 0
  const winRate = totalDeals > 0 ? ((wonDeals / totalDeals) * 100).toFixed(1) : '0.0'
  const lostRate = totalDeals > 0 ? ((lostDeals / totalDeals) * 100).toFixed(1) : '0.0'
  const avgDealValue = totalDeals > 0 ? totalRevenue / totalDeals : 0

  const leadsBySource = (leadsPage?.data ?? []).reduce<Record<string, number>>((acc, lead) => {
    acc[lead.source] = (acc[lead.source] ?? 0) + 1
    return acc
  }, {})
  const totalLeadsForSource = Object.values(leadsBySource).reduce((sum, n) => sum + n, 0)
  const sourceBreakdown = (Object.keys(leadsBySource) as LeadSource[])
    .map((source) => ({ source, count: leadsBySource[source] }))
    .sort((a, b) => b.count - a.count)

  function exportPipelineCsv() {
    downloadCsv('pipeline-by-stage.csv', [
      ['Stage', 'Count', 'Value'],
      ...pipelineByStage.map((s) => [s.label, s.count, s.value]),
    ])
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-lg font-bold text-gray-900">Reports</h1>
          <p className="text-sm text-gray-500">Track performance and gain insights across your sales pipeline</p>
        </div>
        <button
          onClick={exportPipelineCsv}
          className="flex items-center gap-1.5 self-start rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 sm:self-auto"
        >
          <Download size={16} />
          Export CSV
        </button>
      </div>

      {isLoading ? (
        <PageSpinner label="Loading reports…" />
      ) : (
        <>
          <div className="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
            <StatCard label="Total Deals" value={String(totalDeals)} icon={Briefcase} tone="indigo" />
            <StatCard label="Total Revenue" value={`$${totalRevenue.toLocaleString()}`} icon={DollarSign} tone="green" />
            <StatCard label={`Won Deals (${winRate}% win rate)`} value={String(wonDeals)} icon={Trophy} tone="blue" />
            <StatCard label="Avg Deal Value" value={`$${Math.round(avgDealValue).toLocaleString()}`} icon={TrendingUp} tone="orange" />
            <StatCard label={`Lost Deals (${lostRate}% lost rate)`} value={String(lostDeals)} icon={XCircle} tone="red" />
          </div>

          <div className="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-gray-700">Pipeline by Stage</h2>
            {totalDeals === 0 ? (
              <p className="py-6 text-center text-sm text-gray-400">No deals yet.</p>
            ) : (
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={pipelineByStage}>
                  <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} allowDecimals={false} />
                  <Tooltip formatter={(value) => [`${value} deal(s)`, 'Count']} />
                  <Bar dataKey="count" fill="#4f46e5" radius={[4, 4, 0, 0]}>
                    <LabelList dataKey="count" position="top" style={{ fontSize: 11, fill: '#374151' }} />
                  </Bar>
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>

          <div className="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <h2 className="mb-3 text-sm font-semibold text-gray-700">Monthly Sales (Revenue)</h2>
            {!monthly || monthly.every((m) => Number(m.revenue) === 0) ? (
              <p className="py-6 text-center text-sm text-gray-400">No won deals yet.</p>
            ) : (
              <ResponsiveContainer width="100%" height={220}>
                <BarChart data={monthly}>
                  <XAxis dataKey="month" tick={{ fontSize: 11 }} />
                  <YAxis tick={{ fontSize: 11 }} tickFormatter={formatK} />
                  <Tooltip formatter={(value) => [`$${Number(value).toLocaleString()}`, 'Revenue']} />
                  <Bar dataKey="revenue" fill="#0d9488" radius={[4, 4, 0, 0]} />
                </BarChart>
              </ResponsiveContainer>
            )}
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {canSeeLeaderboard && (
              <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h2 className="mb-3 text-sm font-semibold text-gray-700">Salesperson Performance</h2>
                {isPerformanceLoading ? (
                  <Spinner label="Loading…" className="justify-center py-6" />
                ) : !performance || performance.length === 0 ? (
                  <p className="py-6 text-center text-sm text-gray-400">Not enough closed deals yet to show performance.</p>
                ) : (
                  <ul className="divide-y divide-gray-100">
                    {performance.map((row) => (
                      <li key={row.userId} className="flex items-center gap-3 py-2.5 text-sm">
                        <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600">
                          {initialsOf(row.name)}
                        </span>
                        <span className="min-w-0 flex-1 truncate font-medium text-gray-900">{row.name}</span>
                        <span className="shrink-0 text-gray-500">{row.dealsWon} won</span>
                        <span className="shrink-0 font-semibold text-gray-900">${Number(row.revenue).toLocaleString()}</span>
                      </li>
                    ))}
                  </ul>
                )}
              </div>
            )}

            <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
              <h2 className="mb-3 text-sm font-semibold text-gray-700">Leads by Source</h2>
              {isLeadsLoading ? (
                <Spinner label="Loading…" className="justify-center py-6" />
              ) : sourceBreakdown.length === 0 ? (
                <p className="py-6 text-center text-sm text-gray-400">No leads yet.</p>
              ) : (
                <div className="flex items-center gap-4">
                  <div className="relative h-[130px] w-[130px] shrink-0">
                    <ResponsiveContainer width="100%" height="100%">
                      <PieChart>
                        <Pie data={sourceBreakdown} dataKey="count" nameKey="source" innerRadius={38} outerRadius={62} startAngle={90} endAngle={-270}>
                          {sourceBreakdown.map((s) => (
                            <Cell key={s.source} fill={SOURCE_COLORS[s.source]} />
                          ))}
                        </Pie>
                        <Tooltip
                          formatter={(value, _name, entry) => {
                            const source = (entry.payload as { source: LeadSource }).source
                            return [`${value} lead(s)`, LEAD_SOURCE_LABELS[source]]
                          }}
                        />
                      </PieChart>
                    </ResponsiveContainer>
                    <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                      <span className="text-lg font-bold text-gray-900">{totalLeadsForSource}</span>
                      <span className="text-[10px] uppercase tracking-wide text-gray-400">Total</span>
                    </div>
                  </div>
                  <ul className="min-w-0 flex-1 space-y-1.5 text-xs">
                    {sourceBreakdown.map((s) => (
                      <li key={s.source} className="flex items-center justify-between gap-2">
                        <span className="flex items-center gap-1.5 truncate text-gray-600">
                          <span className="h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: SOURCE_COLORS[s.source] }} />
                          {LEAD_SOURCE_LABELS[s.source]}
                        </span>
                        <span className="shrink-0 font-semibold text-gray-900">
                          {s.count} ({totalLeadsForSource > 0 ? ((s.count / totalLeadsForSource) * 100).toFixed(1) : '0.0'}%)
                        </span>
                      </li>
                    ))}
                  </ul>
                </div>
              )}
            </div>
          </div>
        </>
      )}
    </div>
  )
}
