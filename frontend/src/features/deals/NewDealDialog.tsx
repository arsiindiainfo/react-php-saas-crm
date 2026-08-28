import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateDeal } from './api'
import { useToast } from '@/components/Toast'
import { CompanySelect } from '@/components/CompanySelect'

const schema = z.object({
  name: z.string().min(1, 'Required').max(180),
  valueAmount: z.coerce.number().min(0).optional(),
  expectedCloseDate: z.string().optional().or(z.literal('')),
})
type FormInput = z.input<typeof schema>
type FormValues = z.output<typeof schema>

interface NewDealDialogProps {
  isOpen: boolean
  onClose: () => void
  fixedCompanyId?: number
}

export function NewDealDialog({ isOpen, onClose, fixedCompanyId }: NewDealDialogProps) {
  const { notify } = useToast()
  const createDeal = useCreateDeal()
  const [companyId, setCompanyId] = useState<number | null>(fixedCompanyId ?? null)
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormInput, unknown, FormValues>({ resolver: zodResolver(schema) })

  if (!isOpen) return null

  async function onSubmit(values: FormValues) {
    if (!companyId) {
      notify('Choose a company first.', 'error')
      return
    }
    try {
      await createDeal.mutateAsync({ ...values, companyId, expectedCloseDate: values.expectedCloseDate || undefined })
      notify('Deal created')
      reset()
      onClose()
    } catch {
      notify('Could not create the deal.', 'error')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-base font-semibold text-gray-900">New Deal</h2>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          {!fixedCompanyId && (
            <div>
              <label className="mb-1 block text-xs font-medium text-gray-700">Company*</label>
              <CompanySelect value={companyId} onChange={(id) => setCompanyId(id)} />
            </div>
          )}
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Deal name*</label>
            <input {...register('name')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Value ($)</label>
            <input type="number" min={0} step="0.01" {...register('valueAmount')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Expected close date</label>
            <input type="date" {...register('expectedCloseDate')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={createDeal.isPending}
              className="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {createDeal.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
