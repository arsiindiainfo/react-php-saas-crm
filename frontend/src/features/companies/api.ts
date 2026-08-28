import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { Company } from '@/types/entities'

export interface CompanyInput {
  name: string
  industry?: string
  website?: string
  phone?: string
}

export function useCompany(id: number) {
  return useQuery({
    queryKey: ['companies', id],
    queryFn: async () => (await apiClient.get<ApiSuccess<Company>>(`/companies/${id}`)).data.data,
  })
}

export function useCreateCompany() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: CompanyInput) => (await apiClient.post<ApiSuccess<Company>>('/companies', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['companies'] }),
  })
}

export function useUpdateCompany(id: number) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: Partial<CompanyInput> & { status?: string }) =>
      (await apiClient.put<ApiSuccess<Company>>(`/companies/${id}`, input)).data.data,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['companies'] })
    },
  })
}

export function useDeleteCompany() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/companies/${id}`),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['companies'] }),
  })
}
