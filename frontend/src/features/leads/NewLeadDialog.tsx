import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateLead } from './api'
import { useToast } from '@/components/Toast'
import { LEAD_SOURCES } from '@shared/constants'

const schema = z.object({
  firstName: z.string().min(1, 'Required').max(80),
  lastName: z.string().min(1, 'Required').max(80),
  email: z.email().optional().or(z.literal('')),
  phone: z.string().max(30).optional().or(z.literal('')),
  companyName: z.string().max(180).optional().or(z.literal('')),
  source: z.enum(LEAD_SOURCES),
})
type FormValues = z.infer<typeof schema>

export function NewLeadDialog({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const { notify } = useToast()
  const createLead = useCreateLead()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { source: 'WEBSITE' } })

  if (!isOpen) return null

  async function onSubmit(values: FormValues) {
    try {
      await createLead.mutateAsync(values)
      notify('Lead created')
      reset()
      onClose()
    } catch {
      notify('Could not create the lead.', 'error')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-base font-semibold text-gray-900">New Lead</h2>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          <div className="grid grid-cols-2 gap-3">
            <div>
              <label className="mb-1 block text-xs font-medium text-gray-700">First name*</label>
              <input {...register('firstName')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
              {errors.firstName && <p className="mt-1 text-xs text-red-600">{errors.firstName.message}</p>}
            </div>
            <div>
              <label className="mb-1 block text-xs font-medium text-gray-700">Last name*</label>
              <input {...register('lastName')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
              {errors.lastName && <p className="mt-1 text-xs text-red-600">{errors.lastName.message}</p>}
            </div>
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Email</label>
            <input {...register('email')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Phone</label>
            <input {...register('phone')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Company name</label>
            <input {...register('companyName')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Source*</label>
            <select {...register('source')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              {LEAD_SOURCES.map((s) => (
                <option key={s} value={s}>
                  {s}
                </option>
              ))}
            </select>
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={createLead.isPending}
              className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
            >
              {createLead.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
