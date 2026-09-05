import { Link, useNavigate, useParams } from 'react-router-dom'
import { useLead } from './api'
import { Pill } from '@/components/Pill'
import { ActivityTimeline } from '@/components/ActivityTimeline'
import { PageSpinner } from '@/components/Spinner'

export function LeadDetailPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const { data: lead, isLoading } = useLead(Number(id))

  if (isLoading || !lead) return <PageSpinner />

  return (
    <div className="mx-auto max-w-3xl">
      <Link to="/leads" className="text-sm text-indigo-600">
        ← Back to Leads
      </Link>
      <div className="mt-2 mb-6 flex items-center gap-3">
        <h1 className="text-lg font-bold text-gray-900">
          {lead.firstName} {lead.lastName}
        </h1>
        <Pill value={lead.status} />
      </div>

      <div className="mb-6 grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-white p-4 text-sm">
        <div>
          <div className="text-xs font-semibold uppercase text-gray-400">Company</div>
          <div className="mt-1 text-gray-900">{lead.companyName ?? '—'}</div>
        </div>
        <div>
          <div className="text-xs font-semibold uppercase text-gray-400">Email</div>
          <div className="mt-1 text-gray-900">{lead.email ?? '—'}</div>
        </div>
        <div>
          <div className="text-xs font-semibold uppercase text-gray-400">Source</div>
          <div className="mt-1 text-gray-900">{lead.source}</div>
        </div>
        {lead.convertedDealId && (
          <div>
            <div className="text-xs font-semibold uppercase text-gray-400">Converted to</div>
            <button onClick={() => navigate(`/deals/${lead.convertedDealId}`)} className="mt-1 text-indigo-600">
              View deal
            </button>
          </div>
        )}
        {lead.disqualifyReason && (
          <div className="col-span-2">
            <div className="text-xs font-semibold uppercase text-gray-400">Disqualify reason</div>
            <div className="mt-1 text-gray-900">{lead.disqualifyReason}</div>
          </div>
        )}
      </div>

      <ActivityTimeline relatedToType="LEAD" relatedToId={lead.id} />
    </div>
  )
}
