import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { DataTable, type Column } from '@/components/DataTable'
import { Pagination } from '@/components/Pagination'
import { useListQuery } from '@/lib/useListQuery'
import type { Contact } from '@/types/entities'
import { NewContactDialog } from './NewContactDialog'

export function ContactsListPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [isCreating, setIsCreating] = useState(false)

  const { data, isLoading } = useListQuery<Contact>('contacts', { page, search, sort: 'createdAt', direction: 'desc', limit: 20 })

  const columns: Column<Contact>[] = [
    {
      key: 'firstName',
      header: 'Name',
      sortable: true,
      render: (c) => (
        <span className="font-medium text-gray-900">
          {c.firstName} {c.lastName}
        </span>
      ),
    },
    { key: 'email', header: 'Email', render: (c) => c.email ?? '—' },
    { key: 'jobTitle', header: 'Job title', render: (c) => c.jobTitle ?? '—' },
  ]

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">Contacts</h1>
        <button
          onClick={() => setIsCreating(true)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + New Contact
        </button>
      </div>

      <input
        value={search}
        onChange={(e) => {
          setSearch(e.target.value)
          setPage(1)
        }}
        placeholder="Search contacts…"
        className="mb-4 w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm"
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(c) => c.id}
        isLoading={isLoading}
        onRowClick={(c) => navigate(`/companies/${c.companyId}?tab=contacts`)}
        emptyState={
          <>
            <p className="font-medium text-gray-700">You haven't added any contacts yet</p>
            <button onClick={() => setIsCreating(true)} className="mt-1 text-sm text-blue-600">
              Add your first contact
            </button>
          </>
        }
      />

      {data && <Pagination page={data.meta.page} totalPages={data.meta.totalPages} onPageChange={setPage} />}

      <NewContactDialog isOpen={isCreating} onClose={() => setIsCreating(false)} onCreated={() => undefined} />
    </div>
  )
}
