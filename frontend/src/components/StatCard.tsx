interface StatCardProps {
  label: string
  value: string
  emptyHint?: string
}

/** §22.2 — a zero-value KPI shows an explanatory hint, never a blank card. */
export function StatCard({ label, value, emptyHint }: StatCardProps) {
  const isEmpty = value === '0' || value === '$0' || value === '0%'

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
      <div className="text-2xl font-bold text-gray-900">{value}</div>
      <div className="mt-1 text-xs font-semibold uppercase tracking-wide text-gray-500">{label}</div>
      {isEmpty && emptyHint && <div className="mt-1 text-xs text-gray-400">{emptyHint}</div>}
    </div>
  )
}
