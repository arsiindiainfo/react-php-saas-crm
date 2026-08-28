import { useEffect, useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useNavigate } from 'react-router-dom'
import { useConvertLead } from './api'
import { useToast } from '@/components/Toast'
import { CompanySelect } from '@/components/CompanySelect'
import { ApiError } from '@/types/api'
import type { Lead } from '@/types/entities'

const schema = z.object({
  dealName: z.string().min(1, 'Required').max(180),
  dealValue: z.coerce.number().min(0).optional(),
})
type FormInput = z.input<typeof schema>
type FormValues = z.output<typeof schema>

interface ConvertLeadDialogProps {
  lead: Lead | null
  onClose: () => void
}

/** §22.5 — the flagship interaction: qualified Lead → Company + Contact + Deal. */
export function ConvertLeadDialog({ lead, onClose }: ConvertLeadDialogProps) {
  const { notify } = useToast()
  const navigate = useNavigate()
  const convertLead = useConvertLead()
  const [existingCompanyId, setExistingCompanyId] = useState<number | null>(null)
  const {
    register,
    handleSubmit,
    reset,
    setValue,
    formState: { errors },
  } = useForm<FormInput, unknown, FormValues>({ resolver: zodResolver(schema) })

  useEffect(() => {
    if (lead) {
      setValue('dealName', `${lead.companyName ?? `${lead.firstName} ${lead.lastName}`} — New Business`)
      setExistingCompanyId(null)
    }
  }, [lead, setValue])

  if (!lead) return null

  async function onSubmit(values: FormValues) {
    try {
      const result = await convertLead.mutateAsync({
        id: lead!.id,
        dealName: values.dealName,
        dealValue: values.dealValue,
        existingCompanyId: existingCompanyId ?? undefined,
      })
      notify('Lead converted — deal created')
      reset()
      onClose()
      navigate(`/deals/${result.deal.id}`)
    } catch (err) {
      notify(err instanceof ApiError ? err.message : 'Could not convert this lead.', 'error')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-1 text-base font-semibold text-gray-900">Convert Lead</h2>
        <p className="mb-4 text-sm text-gray-500">
          {lead.firstName} {lead.lastName} will become a Company, Contact, and Deal.
        </p>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          <div>
            <label htmlFor="dealName" className="mb-1 block text-xs font-medium text-gray-700">
              Deal name*
            </label>
            <input id="dealName" {...register('dealName')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.dealName && <p className="mt-1 text-xs text-red-600">{errors.dealName.message}</p>}
          </div>
          <div>
            <label htmlFor="dealValue" className="mb-1 block text-xs font-medium text-gray-700">
              Deal value ($)
            </label>
            <input
              id="dealValue"
              type="number"
              min={0}
              step="0.01"
              {...register('dealValue')}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label htmlFor="existingCompany" className="mb-1 block text-xs font-medium text-gray-700">
              Link to an existing company (optional)
            </label>
            <CompanySelect
              id="existingCompany"
              value={existingCompanyId}
              onChange={(id) => setExistingCompanyId(id)}
              placeholder="Otherwise a new company is created"
            />
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={convertLead.isPending}
              className="rounded-md bg-green-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-green-700 disabled:opacity-60"
            >
              {convertLead.isPending ? 'Converting…' : 'Convert'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
