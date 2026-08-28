import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { Deal } from '@/types/entities'
import type { DealStage } from '@shared/constants'

export interface DealInput {
  companyId: number
  contactId?: number
  name: string
  valueAmount?: number
  expectedCloseDate?: string
}

export function useDeal(id: number) {
  return useQuery({
    queryKey: ['deals', id],
    queryFn: async () => (await apiClient.get<ApiSuccess<Deal>>(`/deals/${id}`)).data.data,
  })
}

export function useCreateDeal() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: DealInput) => (await apiClient.post<ApiSuccess<Deal>>('/deals', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['deals'] }),
  })
}

export function useChangeDealStage() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, stage, lostReason }: { id: number; stage: DealStage; lostReason?: string }) =>
      (await apiClient.post<ApiSuccess<Deal>>(`/deals/${id}/change-stage`, { stage, lostReason })).data.data,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['deals'] })
      void queryClient.invalidateQueries({ queryKey: ['companies'] })
      void queryClient.invalidateQueries({ queryKey: ['dashboard'] })
    },
  })
}
