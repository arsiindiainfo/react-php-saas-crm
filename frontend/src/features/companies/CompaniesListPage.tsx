import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { DataTable, type Column } from '@/components/DataTable'
import { Pagination } from '@/components/Pagination'
import { Pill } from '@/components/Pill'
import { useListQuery } from '@/lib/useListQuery'
import type { Company } from '@/types/entities'
import { NewCompanyDialog } from './NewCompanyDialog'

export function CompaniesListPage() {
  const navigate = useNavigate()
  const [page, setPage] = useState(1)
  const [search, setSearch] = useState('')
  const [sort, setSort] = useState('createdAt')
  const [direction, setDirection] = useState<'asc' | 'desc'>('desc')
  const [isCreating, setIsCreating] = useState(false)

  const { data, isLoading } = useListQuery<Company>('companies', { page, search, sort, direction, limit: 20 })

  const columns: Column<Company>[] = [
    { key: 'name', header: 'Name', sortable: true, render: (c) => <span className="font-medium text-gray-900">{c.name}</span> },
    { key: 'industry', header: 'Industry', render: (c) => c.industry ?? '—' },
    { key: 'status', header: 'Status', sortable: true, render: (c) => <Pill value={c.status} /> },
    { key: 'createdAt', header: 'Created', sortable: true, render: (c) => new Date(c.createdAt).toLocaleDateString() },
  ]

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">Companies</h1>
        <button
          onClick={() => setIsCreating(true)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + New Company
        </button>
      </div>

      <input
        value={search}
        onChange={(e) => {
          setSearch(e.target.value)
          setPage(1)
        }}
        placeholder="Search companies…"
        className="mb-4 w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm"
      />

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(c) => c.id}
        isLoading={isLoading}
        sort={sort}
        direction={direction}
        onSortChange={(s, d) => {
          setSort(s)
          setDirection(d)
        }}
        onRowClick={(c) => navigate(`/companies/${c.id}`)}
        emptyState={
          search ? (
            <>
              <p className="font-medium text-gray-700">No companies match your search</p>
              <button onClick={() => setSearch('')} className="mt-1 text-sm text-blue-600">
                Clear search
              </button>
            </>
          ) : (
            <>
              <p className="font-medium text-gray-700">You haven't added any companies yet</p>
              <button onClick={() => setIsCreating(true)} className="mt-1 text-sm text-blue-600">
                Add your first company
              </button>
            </>
          )
        }
      />

      {data && <Pagination page={data.meta.page} totalPages={data.meta.totalPages} onPageChange={setPage} />}

      <NewCompanyDialog
        isOpen={isCreating}
        onClose={() => setIsCreating(false)}
        onCreated={(company) => navigate(`/companies/${company.id}`)}
      />
    </div>
  )
}
