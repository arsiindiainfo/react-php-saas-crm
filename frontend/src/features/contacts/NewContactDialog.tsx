import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateContact } from './api'
import { useToast } from '@/components/Toast'
import { CompanySelect } from '@/components/CompanySelect'
import type { Contact } from '@/types/entities'

const schema = z.object({
  firstName: z.string().min(1, 'Required').max(80),
  lastName: z.string().min(1, 'Required').max(80),
  email: z.email().optional().or(z.literal('')),
  phone: z.string().max(30).optional().or(z.literal('')),
  jobTitle: z.string().max(100).optional().or(z.literal('')),
})
type FormValues = z.infer<typeof schema>

interface NewContactDialogProps {
  isOpen: boolean
  onClose: () => void
  onCreated: (contact: Contact) => void
  /** When set (from Company Detail), the company field is fixed and hidden. */
  fixedCompanyId?: number
}

export function NewContactDialog({ isOpen, onClose, onCreated, fixedCompanyId }: NewContactDialogProps) {
  const { notify } = useToast()
  const createContact = useCreateContact()
  const [companyId, setCompanyId] = useState<number | null>(fixedCompanyId ?? null)
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  if (!isOpen) return null

  async function onSubmit(values: FormValues) {
    if (!companyId) {
      notify('Choose a company first.', 'error')
      return
    }
    try {
      const contact = await createContact.mutateAsync({ ...values, companyId })
      notify('Contact created')
      reset()
      onCreated(contact)
      onClose()
    } catch {
      notify('Could not create the contact.', 'error')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-base font-semibold text-gray-900">New Contact</h2>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          {!fixedCompanyId && (
            <div>
              <label className="mb-1 block text-xs font-medium text-gray-700">Company*</label>
              <CompanySelect value={companyId} onChange={(id) => setCompanyId(id)} />
            </div>
          )}
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
            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Phone</label>
            <input {...register('phone')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Job title</label>
            <input {...register('jobTitle')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>

          <div className="mt-5 flex justify-end gap-2">
            <button type="button" onClick={onClose} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={createContact.isPending}
              className="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {createContact.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
