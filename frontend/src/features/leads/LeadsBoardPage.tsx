import { useMemo, useState } from 'react'
import { Users, UserPlus2, Phone, CheckCircle2, XCircle } from 'lucide-react'
import { KanbanBoard, type KanbanColumn } from '@/components/KanbanBoard'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { PageSpinner } from '@/components/Spinner'
import { StatCard } from '@/components/StatCard'
import { useToast } from '@/components/Toast'
import { useListQuery } from '@/lib/useListQuery'
import { LEAD_STATUS_THEME, LEAD_STATUS_LABELS, LEAD_SOURCE_LABELS } from '@/lib/stageTheme'
import { useDisqualifyLead, useUpdateLeadStatus } from './api'
import type { Lead } from '@/types/entities'
import type { LeadStatus } from '@shared/constants'
import { NewLeadDialog } from './NewLeadDialog'
import { ConvertLeadDialog } from './ConvertLeadDialog'

const BOARD_STATUSES: LeadStatus[] = ['NEW', 'CONTACTED', 'QUALIFIED', 'DISQUALIFIED']
const COLUMN_TITLES = LEAD_STATUS_LABELS

export function LeadsBoardPage() {
  const { notify } = useToast()
  // Converted leads drop off the board (§22.5) — fetch every non-converted status.
  const { data, isLoading } = useListQuery<Lead>('leads', { limit: 200, sort: 'createdAt', direction: 'asc' })
  const updateStatus = useUpdateLeadStatus()
  const disqualifyLead = useDisqualifyLead()
  const [isCreating, setIsCreating] = useState(false)
  const [pendingDisqualify, setPendingDisqualify] = useState<Lead | null>(null)
  const [convertingLead, setConvertingLead] = useState<Lead | null>(null)

  const visibleLeads = useMemo(() => (data?.data ?? []).filter((l) => l.status !== 'CONVERTED'), [data])

  const itemsByColumn = useMemo(() => {
    const grouped: Record<string, Lead[]> = Object.fromEntries(BOARD_STATUSES.map((s) => [s, []]))
    for (const lead of visibleLeads) {
      grouped[lead.status]?.push(lead)
    }
    return grouped
  }, [visibleLeads])

  const columns: KanbanColumn[] = BOARD_STATUSES.map((status) => ({
    key: status,
    title: COLUMN_TITLES[status],
    headerClassName: LEAD_STATUS_THEME[status].header,
    dotClassName: LEAD_STATUS_THEME[status].dot,
    headerRight: <span className="text-xs font-bold">{itemsByColumn[status]?.length ?? 0}</span>,
  }))

  // Each card below mirrors one board column exactly (§10) — "Total Leads" comes
  // from the API's meta.total (the true count), not the page's row count, since
  // a large lead list would otherwise under-report once it spans multiple pages.
  const allLeads = data?.data ?? []
  const stats = {
    total: data?.meta.total ?? allLeads.length,
    new: allLeads.filter((l) => l.status === 'NEW').length,
    contacted: allLeads.filter((l) => l.status === 'CONTACTED').length,
    qualified: allLeads.filter((l) => l.status === 'QUALIFIED').length,
    disqualified: allLeads.filter((l) => l.status === 'DISQUALIFIED').length,
  }

  function handleCardMoved(lead: Lead, toColumn: string) {
    const status = toColumn as LeadStatus
    if (status === lead.status || lead.status === 'DISQUALIFIED') return

    if (status === 'DISQUALIFIED') {
      setPendingDisqualify(lead)
      return
    }

    updateStatus.mutate(
      { id: lead.id, status },
      { onSuccess: () => notify('Lead moved'), onError: () => notify('Could not move the lead.', 'error') },
    )
  }

  function confirmDisqualify(reason?: string) {
    if (!pendingDisqualify || !reason) return
    disqualifyLead.mutate(
      { id: pendingDisqualify.id, reason },
      {
        onSuccess: () => {
          notify('Lead disqualified')
          setPendingDisqualify(null)
        },
        onError: () => notify('Could not disqualify the lead.', 'error'),
      },
    )
  }

  return (
    <div>
      <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-lg font-bold text-gray-900">Leads</h1>
          <p className="text-sm text-gray-500">Manage and track your leads pipeline</p>
        </div>
        <button
          onClick={() => setIsCreating(true)}
          className="self-start rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 sm:self-auto"
        >
          + New Lead
        </button>
      </div>

      <div className="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <StatCard label="Total Leads" value={String(stats.total)} icon={Users} tone="indigo" />
        <StatCard label="New Leads" value={String(stats.new)} icon={UserPlus2} tone="blue" />
        <StatCard label="Contacted" value={String(stats.contacted)} icon={Phone} tone="orange" />
        <StatCard label="Qualified" value={String(stats.qualified)} icon={CheckCircle2} tone="green" />
        <StatCard label="Disqualified" value={String(stats.disqualified)} icon={XCircle} tone="gray" />
      </div>

      {isLoading ? (
        <PageSpinner label="Loading leads…" />
      ) : (
        <KanbanBoard
          columns={columns}
          itemsByColumn={itemsByColumn}
          cardKey={(l) => l.id}
          findItem={(id) => visibleLeads.find((l) => l.id === id)}
          onCardMoved={handleCardMoved}
          renderCard={(lead) => (
            <div>
              <p className="font-medium text-gray-900">
                {lead.firstName} {lead.lastName}
              </p>
              {lead.companyName && <p className="mt-0.5 text-xs text-gray-500">{lead.companyName}</p>}
              <span className="mt-2 inline-block rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-medium text-gray-600">
                {LEAD_SOURCE_LABELS[lead.source]}
              </span>
              {lead.status === 'QUALIFIED' && (
                <button
                  type="button"
                  onClick={() => setConvertingLead(lead)}
                  className="mt-2 block rounded-md bg-emerald-600 px-2 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                >
                  Convert
                </button>
              )}
            </div>
          )}
        />
      )}

      <NewLeadDialog isOpen={isCreating} onClose={() => setIsCreating(false)} />
      <ConvertLeadDialog lead={convertingLead} onClose={() => setConvertingLead(null)} />

      <ConfirmDialog
        isOpen={pendingDisqualify !== null}
        title={`Disqualify ${pendingDisqualify?.firstName} ${pendingDisqualify?.lastName}?`}
        description="This lead will be marked disqualified and removed from the active board."
        confirmLabel="Disqualify"
        danger
        requireReason
        reasonLabel="Reason"
        onCancel={() => setPendingDisqualify(null)}
        onConfirm={confirmDisqualify}
        isSubmitting={disqualifyLead.isPending}
      />
    </div>
  )
}
