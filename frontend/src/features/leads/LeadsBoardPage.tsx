import { useMemo, useState } from 'react'
import { KanbanBoard, type KanbanColumn } from '@/components/KanbanBoard'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { useToast } from '@/components/Toast'
import { useListQuery } from '@/lib/useListQuery'
import { useDisqualifyLead, useUpdateLeadStatus } from './api'
import type { Lead } from '@/types/entities'
import type { LeadStatus } from '@shared/constants'
import { NewLeadDialog } from './NewLeadDialog'
import { ConvertLeadDialog } from './ConvertLeadDialog'

const BOARD_STATUSES: LeadStatus[] = ['NEW', 'CONTACTED', 'QUALIFIED', 'DISQUALIFIED']
const COLUMN_TITLES: Record<string, string> = {
  NEW: 'New',
  CONTACTED: 'Contacted',
  QUALIFIED: 'Qualified',
  DISQUALIFIED: 'Disqualified',
}

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
    headerRight: <span className="text-[11px] text-gray-400">{itemsByColumn[status]?.length ?? 0}</span>,
  }))

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
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">Leads</h1>
        <button
          onClick={() => setIsCreating(true)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + New Lead
        </button>
      </div>

      {isLoading ? (
        <p className="text-sm text-gray-400">Loading…</p>
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
              {lead.status === 'QUALIFIED' && (
                <button
                  type="button"
                  onClick={() => setConvertingLead(lead)}
                  className="mt-2 rounded-md bg-green-600 px-2 py-1 text-xs font-semibold text-white hover:bg-green-700"
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
