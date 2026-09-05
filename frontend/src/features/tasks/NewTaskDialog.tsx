import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useCreateTask } from './api'
import { useToast } from '@/components/Toast'
import { TASK_PRIORITIES } from '@shared/constants'

const schema = z.object({
  subject: z.string().min(1, 'Required').max(200),
  dueDate: z.string().optional().or(z.literal('')),
  priority: z.enum(TASK_PRIORITIES),
})
type FormValues = z.infer<typeof schema>

export function NewTaskDialog({ isOpen, onClose }: { isOpen: boolean; onClose: () => void }) {
  const { notify } = useToast()
  const createTask = useCreateTask()
  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema), defaultValues: { priority: 'MEDIUM' } })

  if (!isOpen) return null

  async function onSubmit(values: FormValues) {
    try {
      await createTask.mutateAsync({ ...values, dueDate: values.dueDate || undefined })
      notify('Task created')
      reset()
      onClose()
    } catch {
      notify('Could not create the task.', 'error')
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
        <h2 className="mb-4 text-base font-semibold text-gray-900">New Task</h2>
        <form onSubmit={handleSubmit(onSubmit)} className="space-y-3" noValidate>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Subject*</label>
            <input {...register('subject')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.subject && <p className="mt-1 text-xs text-red-600">{errors.subject.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Due date</label>
            <input type="date" {...register('dueDate')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Priority*</label>
            <select {...register('priority')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              {TASK_PRIORITIES.map((p) => (
                <option key={p} value={p}>
                  {p}
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
              disabled={createTask.isPending}
              className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
            >
              {createTask.isPending ? 'Creating…' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
