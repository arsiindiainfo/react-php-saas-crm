import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateCompany, type CompanyInput } from './api'
import { useToast } from '@/components/Toast'
import { ApiError } from '@/types/api'
import type { Company } from '@/types/entities'

const schema = z.object({
  name: z.string().min(2, 'Name must be at least 2 characters.').max(180),
  industry: z.string().max(100).optional().or(z.literal('')),
  website: z.url('Enter a valid URL.').optional().or(z.literal('')),
  phone: z.string().max(30).optional().or(z.literal('')),
})

interface NewCompanyDialogProps {
  isOpen: boolean
  onClose: () => void
  onCreated: (company: Company) => void
}

export function NewCompanyDialog({ isOpen, onClose, onCreated }: NewCompanyDialogProps) {
  const { notify } = useToast()
  const createCompany = useCreateCompany()
  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm<CompanyInput>({ resolver: zodResolver(schema) })

  if (!isOpen) return null

  async function onSubmit(values: CompanyInput) {
    try {
      const company = await createCompany.mutateAsync(values)
      notify('Company created')
      reset()
      onCreated(company)
      onClose()
    } catch (err) {
      if (err instanceof ApiError && err.code === 'DUPLICATE_NAME') {
        setError('name', { message: err.message })
      } else {
        notify('Could not create the company.', 'error')
      }
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-base font-semibold text-gray-900">New Company</h2>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Name*</label>
            <input {...register('name')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Industry</label>
            <input {...register('industry')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Website</label>
            <input {...register('website')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.website && <p className="mt-1 text-xs text-red-600">{errors.website.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Phone</label>
            <input {...register('phone')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={createCompany.isPending}
              className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
            >
              {createCompany.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
