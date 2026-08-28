import { useMutation, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { Task } from '@/types/entities'
import type { RelatableType, TaskPriority } from '@shared/constants'

export interface TaskInput {
  subject: string
  dueDate?: string
  priority: TaskPriority
  relatedToType?: RelatableType
  relatedToId?: number
  assignedTo?: number
}

export function useCreateTask() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: TaskInput) => (await apiClient.post<ApiSuccess<Task>>('/tasks', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['tasks'] }),
  })
}

export function useCompleteTask() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => (await apiClient.post<ApiSuccess<Task>>(`/tasks/${id}/complete`, {})).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['tasks'] }),
  })
}
