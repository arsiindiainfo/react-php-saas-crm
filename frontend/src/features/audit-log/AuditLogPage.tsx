import { Fragment, useState } from 'react'
import { useListQuery } from '@/lib/useListQuery'
import type { AuditLogEntry } from '@/types/entities'
import { Pagination } from '@/components/Pagination'

/** §22.11 — ADMIN only (route-guarded). Each row expands to show the JSON details payload. */
export function AuditLogPage() {
  const [page, setPage] = useState(1)
  const [expandedId, setExpandedId] = useState<number | null>(null)
  const { data, isLoading } = useListQuery<AuditLogEntry>('audit-logs', { page, limit: 20 })

  return (
    <div>
      <h1 className="mb-4 text-lg font-bold text-gray-900">Audit Log</h1>

      {isLoading ? (
        <p className="text-sm text-gray-400">Loading…</p>
      ) : (
        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <table className="w-full text-sm">
            <thead className="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
              <tr>
                <th className="px-4 py-3 text-left">When</th>
                <th className="px-4 py-3 text-left">Action</th>
                <th className="px-4 py-3 text-left">Entity</th>
                <th className="px-4 py-3 text-left">User</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-100">
              {(data?.data ?? []).map((entry) => (
                <Fragment key={entry.id}>
                  <tr
                    onClick={() => setExpandedId(expandedId === entry.id ? null : entry.id)}
                    className="cursor-pointer hover:bg-gray-50"
                  >
                    <td className="px-4 py-3 text-gray-500">{new Date(entry.createdAt).toLocaleString()}</td>
                    <td className="px-4 py-3 font-medium text-gray-900">{entry.action}</td>
                    <td className="px-4 py-3 text-gray-600">
                      {entry.entityType} #{entry.entityId}
                    </td>
                    <td className="px-4 py-3 text-gray-600">{entry.userId ?? 'system'}</td>
                  </tr>
                  {expandedId === entry.id && (
                    <tr>
                      <td colSpan={4} className="bg-gray-50 px-4 py-2">
                        <pre className="overflow-x-auto text-xs text-gray-600">{JSON.stringify(entry.details, null, 2)}</pre>
                      </td>
                    </tr>
                  )}
                </Fragment>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {data && <Pagination page={data.meta.page} totalPages={data.meta.totalPages} onPageChange={setPage} />}
    </div>
  )
}
