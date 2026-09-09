import { useMutation } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'

export interface ChangePasswordInput {
  currentPassword: string
  newPassword: string
}

export function useChangePassword() {
  return useMutation({
    mutationFn: async (input: ChangePasswordInput) => apiClient.post('/auth/change-password', input),
  })
}
