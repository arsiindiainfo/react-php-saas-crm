import type { ReactNode } from 'react'
import { PageSpinner } from './Spinner'

export interface Column<T> {
  key: string
  header: string
  sortable?: boolean
  render: (row: T) => ReactNode
}

interface DataTableProps<T> {
  columns: Column<T>[]
  rows: T[]
  rowKey: (row: T) => string | number
  isLoading?: boolean
  sort?: string
  direction?: 'asc' | 'desc'
  onSortChange?: (sort: string, direction: 'asc' | 'desc') => void
  emptyState: ReactNode
  onRowClick?: (row: T) => void
}

/**
 * The one table component behind Companies, Contacts, Leads (table view),
 * Deals (table view) and Tasks (§21.2) — driven by useListQuery.
 */
export function DataTable<T>({
  columns,
  rows,
  rowKey,
  isLoading,
  sort,
  direction = 'asc',
  onSortChange,
  emptyState,
  onRowClick,
}: DataTableProps<T>) {
  function handleHeaderClick(col: Column<T>) {
    if (!col.sortable || !onSortChange) return
    const nextDirection = sort === col.key && direction === 'asc' ? 'desc' : 'asc'
    onSortChange(col.key, nextDirection)
  }

  if (isLoading) {
    return (
      <div className="rounded-lg border border-gray-200 bg-white">
        <PageSpinner />
      </div>
    )
  }

  if (rows.length === 0) {
    return <div className="rounded-lg border border-dashed border-gray-300 bg-white p-10 text-center">{emptyState}</div>
  }

  return (
    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
      <table className="w-full text-sm">
        <thead className="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
          <tr>
            {columns.map((col) => (
              <th
                key={col.key}
                onClick={() => handleHeaderClick(col)}
                className={`px-4 py-3 text-left ${col.sortable ? 'cursor-pointer select-none hover:text-gray-800' : ''}`}
              >
                {col.header}
                {sort === col.key && <span className="ml-1">{direction === 'asc' ? '▲' : '▼'}</span>}
              </th>
            ))}
          </tr>
        </thead>
        <tbody className="divide-y divide-gray-100">
          {rows.map((row) => (
            <tr
              key={rowKey(row)}
              onClick={() => onRowClick?.(row)}
              className={onRowClick ? 'cursor-pointer hover:bg-gray-50' : ''}
            >
              {columns.map((col) => (
                <td key={col.key} className="px-4 py-3 text-gray-700">
                  {col.render(row)}
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  )
}
