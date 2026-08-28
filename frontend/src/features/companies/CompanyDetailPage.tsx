import { useState } from 'react'
import { Link, useParams, useSearchParams } from 'react-router-dom'
import { useCompany } from './api'
import { useListQuery } from '@/lib/useListQuery'
import { Pill } from '@/components/Pill'
import { ActivityTimeline } from '@/components/ActivityTimeline'
import { ConfirmDialog } from '@/components/ConfirmDialog'
import { useToast } from '@/components/Toast'
import { useDeleteCompany } from './api'
import type { Contact, Deal } from '@/types/entities'
import { NewContactDialog } from '@/features/contacts/NewContactDialog'
import { NewDealDialog } from '@/features/deals/NewDealDialog'
import { Pagination } from '@/components/Pagination'

const TABS = ['overview', 'contacts', 'deals', 'activity', 'notes'] as const
type Tab = (typeof TABS)[number]

export function CompanyDetailPage() {
  const { id } = useParams()
  const companyId = Number(id)
  const { notify } = useToast()
  const [searchParams, setSearchParams] = useSearchParams()
  const tab = (searchParams.get('tab') as Tab) ?? 'overview'
  const [isAddingContact, setIsAddingContact] = useState(false)
  const [isAddingDeal, setIsAddingDeal] = useState(false)
  const [confirmingDelete, setConfirmingDelete] = useState(false)

  const { data: company, isLoading } = useCompany(companyId)
  const deleteCompany = useDeleteCompany()

  if (isLoading || !company) {
    return <p className="text-sm text-gray-400">Loading…</p>
  }

  function setTab(next: Tab) {
    setSearchParams({ tab: next })
  }

  return (
    <div className="mx-auto max-w-4xl">
      <Link to="/companies" className="text-sm text-blue-600">
        ← Back to Companies
      </Link>

      <div className="mt-2 mb-4 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <h1 className="text-lg font-bold text-gray-900">{company.name}</h1>
          <Pill value={company.status} />
        </div>
        <button
          onClick={() => setConfirmingDelete(true)}
          className="rounded-md border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
        >
          Delete
        </button>
      </div>

      <div className="mb-4 flex gap-1 border-b border-gray-200">
        {TABS.map((t) => (
          <button
            key={t}
            onClick={() => setTab(t)}
            className={`px-3 py-2 text-sm font-medium capitalize ${
              tab === t ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-500 hover:text-gray-800'
            }`}
          >
            {t}
          </button>
        ))}
      </div>

      {tab === 'overview' && (
        <dl className="grid grid-cols-2 gap-4 rounded-lg border border-gray-200 bg-white p-4 text-sm">
          <div>
            <dt className="text-xs font-semibold uppercase text-gray-400">Industry</dt>
            <dd className="mt-1 text-gray-900">{company.industry ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase text-gray-400">Website</dt>
            <dd className="mt-1 text-gray-900">{company.website ?? '—'}</dd>
          </div>
          <div>
            <dt className="text-xs font-semibold uppercase text-gray-400">Phone</dt>
            <dd className="mt-1 text-gray-900">{company.phone ?? '—'}</dd>
          </div>
        </dl>
      )}

      {tab === 'contacts' && <CompanyContactsTab companyId={companyId} onAdd={() => setIsAddingContact(true)} />}
      {tab === 'deals' && <CompanyDealsTab companyId={companyId} onAdd={() => setIsAddingDeal(true)} />}
      {tab === 'activity' && <ActivityTimeline relatedToType="COMPANY" relatedToId={companyId} />}
      {tab === 'notes' && <ActivityTimeline relatedToType="COMPANY" relatedToId={companyId} typeFilter="NOTE" />}

      <NewContactDialog isOpen={isAddingContact} onClose={() => setIsAddingContact(false)} onCreated={() => undefined} fixedCompanyId={companyId} />
      <NewDealDialog isOpen={isAddingDeal} onClose={() => setIsAddingDeal(false)} fixedCompanyId={companyId} />

      <ConfirmDialog
        isOpen={confirmingDelete}
        title={`Delete "${company.name}"?`}
        description="This cannot be undone. Companies with open deals cannot be deleted."
        confirmLabel="Delete"
        danger
        onCancel={() => setConfirmingDelete(false)}
        onConfirm={() =>
          deleteCompany.mutate(company.id, {
            onSuccess: () => {
              notify('Company deleted')
              window.history.back()
            },
            onError: (err: unknown) => {
              const message = err && typeof err === 'object' && 'message' in err ? String(err.message) : 'Could not delete this company.'
              notify(message, 'error')
              setConfirmingDelete(false)
            },
          })
        }
        isSubmitting={deleteCompany.isPending}
      />
    </div>
  )
}

function CompanyContactsTab({ companyId, onAdd }: { companyId: number; onAdd: () => void }) {
  const [page, setPage] = useState(1)
  const { data } = useListQuery<Contact>('contacts', { companyId, page, limit: 10 })

  return (
    <div>
      <div className="mb-2 flex justify-end">
        <button onClick={onAdd} className="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
          + Add Contact
        </button>
      </div>
      {!data || data.data.length === 0 ? (
        <p className="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-400">No contacts yet.</p>
      ) : (
        <ul className="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
          {data.data.map((c) => (
            <li key={c.id} className="px-4 py-3 text-sm">
              <span className="font-medium text-gray-900">
                {c.firstName} {c.lastName}
              </span>
              {c.jobTitle && <span className="text-gray-500"> · {c.jobTitle}</span>}
              {c.email && <span className="ml-2 text-gray-400">{c.email}</span>}
            </li>
          ))}
        </ul>
      )}
      {data && <Pagination page={data.meta.page} totalPages={data.meta.totalPages} onPageChange={setPage} />}
    </div>
  )
}

function CompanyDealsTab({ companyId, onAdd }: { companyId: number; onAdd: () => void }) {
  const { data } = useListQuery<Deal>('deals', { companyId, limit: 20 })

  return (
    <div>
      <div className="mb-2 flex justify-end">
        <button onClick={onAdd} className="rounded-md bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">
          + Add Deal
        </button>
      </div>
      {!data || data.data.length === 0 ? (
        <p className="rounded-md border border-dashed border-gray-300 p-6 text-center text-sm text-gray-400">No deals yet.</p>
      ) : (
        <ul className="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
          {data.data.map((d) => (
            <li key={d.id} className="flex items-center justify-between px-4 py-3 text-sm">
              <Link to={`/deals/${d.id}`} className="font-medium text-blue-600">
                {d.name}
              </Link>
              <div className="flex items-center gap-2">
                <span className="text-gray-500">${Number(d.valueAmount).toLocaleString()}</span>
                <Pill value={d.stage} />
              </div>
            </li>
          ))}
        </ul>
      )}
    </div>
  )
}
