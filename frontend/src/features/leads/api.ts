import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { Company, Contact, Deal, Lead } from '@/types/entities'
import type { LeadSource, LeadStatus } from '@shared/constants'

export interface LeadInput {
  firstName: string
  lastName: string
  email?: string
  phone?: string
  companyName?: string
  source: LeadSource
}

export function useLead(id: number) {
  return useQuery({
    queryKey: ['leads', id],
    queryFn: async () => (await apiClient.get<ApiSuccess<Lead>>(`/leads/${id}`)).data.data,
  })
}

export function useCreateLead() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: LeadInput) => (await apiClient.post<ApiSuccess<Lead>>('/leads', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['leads'] }),
  })
}

export function useUpdateLeadStatus() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, status }: { id: number; status: LeadStatus }) =>
      (await apiClient.put<ApiSuccess<Lead>>(`/leads/${id}`, { status })).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['leads'] }),
  })
}

export function useDisqualifyLead() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, reason }: { id: number; reason: string }) =>
      (await apiClient.post<ApiSuccess<Lead>>(`/leads/${id}/disqualify`, { reason })).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['leads'] }),
  })
}

export interface ConvertResult {
  company: Company
  contact: Contact
  deal: Deal
}

export function useConvertLead() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async ({ id, dealName, dealValue, existingCompanyId }: { id: number; dealName: string; dealValue?: number; existingCompanyId?: number }) =>
      (await apiClient.post<ApiSuccess<ConvertResult>>(`/leads/${id}/convert`, { dealName, dealValue, existingCompanyId })).data.data,
    onSuccess: () => {
      void queryClient.invalidateQueries({ queryKey: ['leads'] })
      void queryClient.invalidateQueries({ queryKey: ['companies'] })
      void queryClient.invalidateQueries({ queryKey: ['deals'] })
    },
  })
}
