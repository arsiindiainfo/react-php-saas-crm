import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useToast } from '@/components/Toast'
import { Pill } from '@/components/Pill'
import { ApiError } from '@/types/api'
import { USER_ROLES } from '@shared/constants'
import { useInviteUser, useUpdateUser, useUsers } from './api'

const schema = z.object({
  name: z.string().min(2).max(120),
  email: z.email(),
  role: z.enum(USER_ROLES),
  managerId: z.coerce.number().optional(),
})
type FormInput = z.input<typeof schema>
type FormValues = z.output<typeof schema>

/** §22.10 — ADMIN only (route-guarded). */
export function UsersPage() {
  const { notify } = useToast()
  const { data: users } = useUsers()
  const inviteUser = useInviteUser()
  const updateUser = useUpdateUser()
  const [isInviting, setIsInviting] = useState(false)

  const managers = (users ?? []).filter((u) => u.role === 'SALES_MANAGER' || u.role === 'ADMIN')

  const {
    register,
    handleSubmit,
    watch,
    reset,
    setError,
    formState: { errors },
  } = useForm<FormInput, unknown, FormValues>({ resolver: zodResolver(schema), defaultValues: { role: 'SALES_REP' } })

  async function onSubmit(values: FormValues) {
    try {
      await inviteUser.mutateAsync(values)
      notify('User invited')
      reset()
      setIsInviting(false)
    } catch (err) {
      if (err instanceof ApiError) {
        setError('email', { message: err.message })
      } else {
        notify('Could not invite this user.', 'error')
      }
    }
  }

  function toggleStatus(userId: number, currentStatus: string) {
    updateUser.mutate(
      { id: userId, status: currentStatus === 'ACTIVE' ? 'DISABLED' : 'ACTIVE' },
      { onSuccess: () => notify('User updated'), onError: () => notify('Could not update this user.', 'error') },
    )
  }

  return (
    <div>
      <div className="mb-4 flex items-center justify-between">
        <h1 className="text-lg font-bold text-gray-900">Team</h1>
        <button
          onClick={() => setIsInviting((v) => !v)}
          className="rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"
        >
          + Invite User
        </button>
      </div>

      {isInviting && (
        <form onSubmit={handleSubmit(onSubmit)} className="mb-6 grid grid-cols-2 gap-3 rounded-lg border border-gray-200 bg-white p-4">
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Name*</label>
            <input {...register('name')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Email*</label>
            <input {...register('email')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            {errors.email && <p className="mt-1 text-xs text-red-600">{errors.email.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Role*</label>
            <select {...register('role')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
              {USER_ROLES.map((r) => (
                <option key={r} value={r}>
                  {r}
                </option>
              ))}
            </select>
          </div>
          {watch('role') === 'SALES_REP' && (
            <div>
              <label className="mb-1 block text-xs font-medium text-gray-700">Manager*</label>
              <select {...register('managerId')} className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">Select a manager…</option>
                {managers.map((m) => (
                  <option key={m.id} value={m.id}>
                    {m.name}
                  </option>
                ))}
              </select>
            </div>
          )}
          <div className="col-span-2 flex justify-end gap-2">
            <button type="button" onClick={() => setIsInviting(false)} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700">
              Cancel
            </button>
            <button
              type="submit"
              disabled={inviteUser.isPending}
              className="rounded-md bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60"
            >
              {inviteUser.isPending ? 'Inviting…' : 'Invite'}
            </button>
          </div>
        </form>
      )}

      <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        <table className="w-full text-sm">
          <thead className="bg-gray-50 text-xs font-semibold uppercase text-gray-500">
            <tr>
              <th className="px-4 py-3 text-left">Name</th>
              <th className="px-4 py-3 text-left">Email</th>
              <th className="px-4 py-3 text-left">Role</th>
              <th className="px-4 py-3 text-left">Status</th>
              <th className="px-4 py-3" />
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-100">
            {(users ?? []).map((u) => (
              <tr key={u.id}>
                <td className="px-4 py-3 font-medium text-gray-900">{u.name}</td>
                <td className="px-4 py-3 text-gray-600">{u.email}</td>
                <td className="px-4 py-3">
                  <Pill value={u.role} />
                </td>
                <td className="px-4 py-3">
                  <Pill value={u.status} />
                </td>
                <td className="px-4 py-3 text-right">
                  <button onClick={() => toggleStatus(u.id, u.status)} className="text-xs font-semibold text-blue-600">
                    {u.status === 'ACTIVE' ? 'Disable' : 'Activate'}
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  )
}
