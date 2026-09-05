import { useState, type ReactNode } from 'react'

interface ConfirmDialogProps {
  title: string
  description: ReactNode
  confirmLabel?: string
  danger?: boolean
  /** When set, the user must type this exact reason before confirming (e.g. lost reason). */
  requireReason?: boolean
  reasonLabel?: string
  isOpen: boolean
  onCancel: () => void
  onConfirm: (reason?: string) => void
  isSubmitting?: boolean
}

/** §23 — destructive/terminal actions always name the record and, where applicable, require a reason first. */
export function ConfirmDialog({
  title,
  description,
  confirmLabel = 'Confirm',
  danger,
  requireReason,
  reasonLabel = 'Reason',
  isOpen,
  onCancel,
  onConfirm,
  isSubmitting,
}: ConfirmDialogProps) {
  const [reason, setReason] = useState('')

  if (!isOpen) return null

  const canConfirm = !requireReason || reason.trim().length > 0

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
        <h2 className="text-base font-semibold text-gray-900">{title}</h2>
        <div className="mt-2 text-sm text-gray-600">{description}</div>

        {requireReason && (
          <div className="mt-4">
            <label className="mb-1 block text-xs font-medium text-gray-700">{reasonLabel}*</label>
            <textarea
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              rows={2}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
            />
          </div>
        )}

        <div className="mt-5 flex justify-end gap-2">
          <button
            type="button"
            onClick={onCancel}
            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
          >
            Cancel
          </button>
          <button
            type="button"
            disabled={!canConfirm || isSubmitting}
            onClick={() => onConfirm(requireReason ? reason.trim() : undefined)}
            className={`rounded-md px-3 py-1.5 text-sm font-semibold text-white disabled:opacity-50 ${
              danger ? 'bg-red-600 hover:bg-red-700' : 'bg-indigo-600 hover:bg-indigo-700'
            }`}
          >
            {isSubmitting ? 'Working…' : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  )
}
