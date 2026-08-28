import { useParams, Link } from 'react-router-dom'
import { useDeal } from './api'
import { Pill } from '@/components/Pill'
import { ActivityTimeline } from '@/components/ActivityTimeline'

export function DealDetailPage() {
  const { id } = useParams()
  const { data: deal, isLoading } = useDeal(Number(id))

  if (isLoading || !deal) return <p className="text-sm text-gray-400">Loading…</p>

  return (
    <div className="mx-auto max-w-3xl">
      <Link to="/deals" className="text-sm text-blue-600">
        ← Back to Pipeline
      </Link>
      <div className="mt-2 mb-6 flex items-center gap-3">
        <h1 className="text-lg font-bold text-gray-900">{deal.name}</h1>
        <Pill value={deal.stage} />
      </div>

      <div className="mb-6 grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-white p-4 text-sm">
        <div>
          <div className="text-xs font-semibold uppercase text-gray-400">Value</div>
          <div className="mt-1 text-gray-900">${Number(deal.valueAmount).toLocaleString()}</div>
        </div>
        <div>
          <div className="text-xs font-semibold uppercase text-gray-400">Company</div>
          <Link to={`/companies/${deal.companyId}`} className="mt-1 block text-blue-600">
            View company
          </Link>
        </div>
        {deal.lostReason && (
          <div className="col-span-2">
            <div className="text-xs font-semibold uppercase text-gray-400">Lost reason</div>
            <div className="mt-1 text-gray-900">{deal.lostReason}</div>
          </div>
        )}
      </div>

      <ActivityTimeline relatedToType="DEAL" relatedToId={deal.id} />
    </div>
  )
}
