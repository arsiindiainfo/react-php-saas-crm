import { Bar, BarChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts'
import { useAuth } from '@/features/auth/AuthContext'
import { usePipelineByStage, useMonthlySales, useSalespersonPerformance } from './api'

export function ReportsPage() {
  const { user } = useAuth()
  const canSeeLeaderboard = user?.role === 'ADMIN' || user?.role === 'SALES_MANAGER'
  const { data: pipeline } = usePipelineByStage()
  const { data: monthly } = useMonthlySales()
  const { data: performance } = useSalespersonPerformance(canSeeLeaderboard)

  return (
    <div>
      <h1 className="mb-4 text-lg font-bold text-gray-900">Reports</h1>

      <div className="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-sm font-semibold text-gray-700">Pipeline by Stage</h2>
        {!pipeline || pipeline.length === 0 ? (
          <p className="py-6 text-center text-sm text-gray-400">No deals yet.</p>
        ) : (
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={pipeline}>
              <XAxis dataKey="stage" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip />
              <Bar dataKey="count" fill="#4f46e5" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>

      <div className="mb-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
        <h2 className="mb-3 text-sm font-semibold text-gray-700">Monthly Sales</h2>
        {!monthly || monthly.every((m) => Number(m.revenue) === 0) ? (
          <p className="py-6 text-center text-sm text-gray-400">No won deals yet.</p>
        ) : (
          <ResponsiveContainer width="100%" height={220}>
            <BarChart data={monthly}>
              <XAxis dataKey="month" tick={{ fontSize: 11 }} />
              <YAxis tick={{ fontSize: 11 }} />
              <Tooltip />
              <Bar dataKey="revenue" fill="#0d9488" radius={[4, 4, 0, 0]} />
            </BarChart>
          </ResponsiveContainer>
        )}
      </div>

      {canSeeLeaderboard && (
        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <h2 className="mb-3 text-sm font-semibold text-gray-700">Salesperson Performance</h2>
          {!performance || performance.length === 0 ? (
            <p className="py-6 text-center text-sm text-gray-400">Not enough closed deals yet to show performance.</p>
          ) : (
            <table className="w-full text-sm">
              <thead className="text-xs uppercase text-gray-400">
                <tr>
                  <th className="py-1 text-left">Rep</th>
                  <th className="py-1 text-right">Deals Won</th>
                  <th className="py-1 text-right">Revenue</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-100">
                {performance.map((row) => (
                  <tr key={row.userId}>
                    <td className="py-1.5">{row.name}</td>
                    <td className="py-1.5 text-right">{row.dealsWon}</td>
                    <td className="py-1.5 text-right">${Number(row.revenue).toLocaleString()}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </div>
      )}
    </div>
  )
}
