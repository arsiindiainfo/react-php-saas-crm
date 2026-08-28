import { useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { KanbanBoard, type KanbanColumn } from '@/components/KanbanBoard'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { useToast } from '@/components/Toast'
import { useListQuery } from '@/lib/useListQuery'
import { useChangeDealStage } from './api'
import type { Deal } from '@/types/entities'
import { DEAL_STAGES, type DealStage } from '@shared/constants'
import { NewDealDialog } from './NewDealDialog'
import { resolveStageMoveAction } from './stageMove'

const COLUMN_TITLES: Record<DealStage, string> = {
  PROSPECTING: 'Prospecting',
  PROPOSAL: 'Proposal',
  NEGOTIATION: 'Negotiation',
  WON: 'Won',
  LOST: 'Lost',
}

export function DealsBoardPage() {
  const navigate = useNavigate()
  const { notify } = useToast()
  const { data, isLoading } = useListQuery<Deal>('deals', { limit: 200, sort: 'createdAt', direction: 'asc' })
  const changeStage = useChangeDealStage()
  const [isCreating, setIsCreating] = useState(false)
  const [pendingMove, setPendingMove] = useState<{ deal: Deal; stage: DealStage } | null>(null)

  const itemsByColumn = useMemo(() => {
    const grouped: Record<string, Deal[]> = Object.fromEntries(DEAL_STAGES.map((s) => [s, []]))
    for (const deal of data?.data ?? []) {
      grouped[deal.stage]?.push(deal)
    }
    return grouped
  }, [data])

  const columns: KanbanColumn[] = DEAL_STAGES.map((stage) => {
    const items = itemsByColumn[stage] ?? []
    const total = items.reduce((sum, d) => sum + Number(d.valueAmount), 0)
    return {
      key: stage,
      title: COLUMN_TITLES[stage],
      headerRight: (
        <span className="text-[11px] text-gray-400">
          {items.length} · ${total.toLocaleString()}
        </span>
      ),
    }
  })

  function handleCardMoved(deal: Deal, toColumn: string) {
    const action = resolveStageMoveAction(deal.stage, toColumn)

    if (action.type === 'noop') return

    if (action.type === 'blocked') {
      notify(action.reason, 'error')
      return
    }

    if (action.type === 'confirm') {
      setPendingMove({ deal, stage: action.stage })
      return
    }

    changeStage.mutate(
      { id: deal.id, stage: action.stage },
      { onSuccess: () => notify('Deal moved'), onError: () => notify('Could not move the deal.', 'error') },
    )
  }

  function confirmMove(reason?: string) {
    if (!pendingMove) return
    changeStage.mutate(
      { id: pendingMove.deal.id, stage: pendingMove.stage, lostReason: reason },
      {
        onSuccess: () => {
          notify(pendingMove.stage === 'WON' ? 'Deal won — company marked as a customer' : 'Deal marked as lost')
          setPendingMove(null)
        },
        onError: () => {
          notify('Could not update the deal.', 'error')
          setPendingMove(null)
        },
      },
    )
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">Deals Pipeline</h1>
        <button
          onClick={() => setIsCreating(true)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + New Deal
        </button>
      </div>

      {isLoading ? (
        <p className="text-sm text-gray-400">Loading…</p>
      ) : (
        <KanbanBoard
          columns={columns}
          itemsByColumn={itemsByColumn}
          cardKey={(d) => d.id}
          findItem={(id) => data?.data.find((d) => d.id === id)}
          onCardMoved={handleCardMoved}
          renderCard={(deal) => (
            <div onClick={() => navigate(`/deals/${deal.id}`)}>
              <p className="font-medium text-gray-900">{deal.name}</p>
              <p className="mt-1 text-xs text-gray-500">${Number(deal.valueAmount).toLocaleString()}</p>
            </div>
          )}
        />
      )}

      <NewDealDialog isOpen={isCreating} onClose={() => setIsCreating(false)} />

      <ConfirmDialog
        isOpen={pendingMove !== null}
        title={pendingMove?.stage === 'WON' ? `Mark "${pendingMove.deal.name}" as won?` : `Mark "${pendingMove?.deal.name}" as lost?`}
        description={
          pendingMove?.stage === 'WON'
            ? 'The company will be marked as a customer.'
            : 'This deal will move to the Lost column and cannot be reopened.'
        }
        confirmLabel={pendingMove?.stage === 'WON' ? 'Mark as customer' : 'Mark as lost'}
        danger={pendingMove?.stage === 'LOST'}
        requireReason={pendingMove?.stage === 'LOST'}
        reasonLabel="Reason for losing this deal"
        onCancel={() => setPendingMove(null)}
        onConfirm={confirmMove}
        isSubmitting={changeStage.isPending}
      />
    </div>
  )
}
