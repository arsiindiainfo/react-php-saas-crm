import type { LucideIcon } from 'lucide-react'

interface StatCardProps {
  label: string
  value: string
  emptyHint?: string
  icon?: LucideIcon
  tone?: 'indigo' | 'green' | 'orange' | 'blue' | 'pink' | 'red' | 'gray'
}

const TONE_CLASSES: Record<NonNullable<StatCardProps['tone']>, string> = {
  indigo: 'bg-indigo-100 text-indigo-600',
  green: 'bg-emerald-100 text-emerald-600',
  orange: 'bg-orange-100 text-orange-600',
  blue: 'bg-blue-100 text-blue-600',
  pink: 'bg-pink-100 text-pink-600',
  red: 'bg-red-100 text-red-600',
  gray: 'bg-gray-100 text-gray-500',
}

/** §22.2 — a zero-value KPI shows an explanatory hint, never a blank card. */
export function StatCard({ label, value, emptyHint, icon: Icon, tone = 'indigo' }: StatCardProps) {
  const isEmpty = value === '0' || value === '$0' || value === '0%'

  return (
    <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
      <div className="flex items-start gap-3">
        {Icon && (
          <span className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-full ${TONE_CLASSES[tone]}`}>
            <Icon size={20} />
          </span>
        )}
        <div className="min-w-0">
          <div className="text-2xl font-bold text-gray-900">{value}</div>
          <div className="mt-0.5 text-xs font-semibold uppercase tracking-wide text-gray-500">{label}</div>
        </div>
      </div>
      {isEmpty && emptyHint && <div className="mt-2 text-xs text-gray-400">{emptyHint}</div>}
    </div>
  )
}
