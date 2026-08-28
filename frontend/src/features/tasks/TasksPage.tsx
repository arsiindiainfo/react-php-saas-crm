import { useState } from 'react'
import { DataTable, type Column } from '@/components/DataTable'
import { Pill } from '@/components/Pill'
import { useListQuery } from '@/lib/useListQuery'
import { useCompleteTask } from './api'
import { useToast } from '@/components/Toast'
import type { Task } from '@/types/entities'
import { NewTaskDialog } from './NewTaskDialog'

export function TasksPage() {
  const { notify } = useToast()
  const [isCreating, setIsCreating] = useState(false)
  const { data, isLoading } = useListQuery<Task>('tasks', { sort: 'dueDate', direction: 'asc', limit: 50 })
  const completeTask = useCompleteTask()

  const columns: Column<Task>[] = [
    {
      key: 'subject',
      header: 'Subject',
      render: (t) => (
        <span className={t.completedAt ? 'text-gray-400 line-through' : 'font-medium text-gray-900'}>{t.subject}</span>
      ),
    },
    { key: 'dueDate', header: 'Due', render: (t) => (t.dueDate ? new Date(t.dueDate).toLocaleDateString() : '—') },
    { key: 'priority', header: 'Priority', render: (t) => <Pill value={t.priority} /> },
    {
      key: 'actions',
      header: '',
      render: (t) =>
        !t.completedAt && (
          <button
            onClick={(e) => {
              e.stopPropagation()
              completeTask.mutate(t.id, {
                onSuccess: () => notify('Task completed'),
                onError: () => notify('Could not complete this task.', 'error'),
              })
            }}
            className="rounded-md border border-gray-300 px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-50"
          >
            Mark done
          </button>
        ),
    },
  ]

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">My Tasks</h1>
        <button
          onClick={() => setIsCreating(true)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + New Task
        </button>
      </div>

      <DataTable
        columns={columns}
        rows={data?.data ?? []}
        rowKey={(t) => t.id}
        isLoading={isLoading}
        emptyState={<p className="font-medium text-gray-700">Nothing due — nice work</p>}
      />

      <NewTaskDialog isOpen={isCreating} onClose={() => setIsCreating(false)} />
    </div>
  )
}
