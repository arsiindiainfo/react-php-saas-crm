import { useForm } from 'react-hook-form'
import { zodResolver } from '@hookform/resolvers/zod'
import { z } from 'zod'
import { useToast } from '@/components/Toast'
import { ApiError } from '@/types/api'
import { useChangePassword } from '@/features/auth/api'

const schema = z
  .object({
    currentPassword: z.string().min(1, 'Required'),
    newPassword: z.string().min(8, 'Must be at least 8 characters'),
    confirmPassword: z.string().min(1, 'Required'),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: 'Passwords do not match',
    path: ['confirmPassword'],
  })
type FormValues = z.infer<typeof schema>

interface ChangePasswordDialogProps {
  isOpen: boolean
  onClose: () => void
}

export function ChangePasswordDialog({ isOpen, onClose }: ChangePasswordDialogProps) {
  const { notify } = useToast()
  const changePassword = useChangePassword()
  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors },
  } = useForm<FormValues>({ resolver: zodResolver(schema) })

  if (!isOpen) return null

  function close() {
    reset()
    onClose()
  }

  async function onSubmit(values: FormValues) {
    try {
      await changePassword.mutateAsync({ currentPassword: values.currentPassword, newPassword: values.newPassword })
      notify('Password changed')
      close()
    } catch (err) {
      if (err instanceof ApiError) {
        setError('currentPassword', { message: err.message })
      } else {
        notify('Could not change your password.', 'error')
      }
    }
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
      <div className="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
        <h2 className="text-base font-semibold text-gray-900">Change Password</h2>

        <form onSubmit={handleSubmit(onSubmit)} className="mt-4 space-y-3">
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Current Password*</label>
            <input
              type="password"
              {...register('currentPassword')}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
            {errors.currentPassword && <p className="mt-1 text-xs text-red-600">{errors.currentPassword.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">New Password*</label>
            <input
              type="password"
              {...register('newPassword')}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
            {errors.newPassword && <p className="mt-1 text-xs text-red-600">{errors.newPassword.message}</p>}
          </div>
          <div>
            <label className="mb-1 block text-xs font-medium text-gray-700">Confirm New Password*</label>
            <input
              type="password"
              {...register('confirmPassword')}
              className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
            {errors.confirmPassword && <p className="mt-1 text-xs text-red-600">{errors.confirmPassword.message}</p>}
          </div>

          <div className="flex justify-end gap-2 pt-2">
            <button type="button" onClick={close} className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
              Cancel
            </button>
            <button
              type="submit"
              disabled={changePassword.isPending}
              className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:opacity-60"
            >
              {changePassword.isPending ? 'Saving…' : 'Change Password'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}
