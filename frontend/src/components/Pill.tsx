const COLOR_MAP: Record<string, string> = {
  // company status
  PROSPECT: 'bg-gray-100 text-gray-600',
  CUSTOMER: 'bg-green-100 text-green-700',
  CHURNED: 'bg-red-100 text-red-700',
  // lead status
  NEW: 'bg-gray-100 text-gray-600',
  CONTACTED: 'bg-blue-100 text-blue-700',
  QUALIFIED: 'bg-purple-100 text-purple-700',
  CONVERTED: 'bg-green-100 text-green-700',
  DISQUALIFIED: 'bg-red-100 text-red-700',
  // deal stage
  PROSPECTING: 'bg-gray-100 text-gray-600',
  PROPOSAL: 'bg-blue-100 text-blue-700',
  NEGOTIATION: 'bg-purple-100 text-purple-700',
  WON: 'bg-green-100 text-green-700',
  LOST: 'bg-red-100 text-red-700',
  // roles
  ADMIN: 'bg-purple-100 text-purple-700',
  SALES_MANAGER: 'bg-blue-100 text-blue-700',
  SALES_REP: 'bg-green-100 text-green-700',
  // priority
  LOW: 'bg-gray-100 text-gray-600',
  MEDIUM: 'bg-amber-100 text-amber-700',
  HIGH: 'bg-red-100 text-red-700',
  // generic
  ACTIVE: 'bg-green-100 text-green-700',
  DISABLED: 'bg-gray-100 text-gray-500',
}

export function Pill({ value }: { value: string }) {
  const classes = COLOR_MAP[value] ?? 'bg-gray-100 text-gray-600'

  return (
    <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-semibold ${classes}`}>
      {value.replace(/_/g, ' ')}
    </span>
  )
}
