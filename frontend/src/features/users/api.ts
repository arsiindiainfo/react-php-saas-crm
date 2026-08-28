import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { User } from '@/types/entities'
import type { UserRole } from '@shared/constants'

export function useUsers() {
  return useQuery({
    queryKey: ['users'],
    queryFn: async () => (await apiClient.get<ApiSuccess<User[]>>('/users')).data.data,
  })
}

export interface InviteUserInput {
  name: string
  email: string
  role: UserRole
  managerId?: number
}

export function useInviteUser() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: InviteUserInput) => (await apiClient.post<ApiSuccess<User>>('/users', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
}

export function useUpdateUser() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, ...input }: { id: number; role?: UserRole; status?: string; managerId?: number }) =>
      (await apiClient.put<ApiSuccess<User>>(`/users/${id}`, input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['users'] }),
  })
}
