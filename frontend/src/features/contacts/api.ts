import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'
import type { Contact } from '@/types/entities'

export interface ContactInput {
  companyId: number
  firstName: string
  lastName: string
  email?: string
  phone?: string
  jobTitle?: string
}

export function useContact(id: number) {
  return useQuery({
    queryKey: ['contacts', id],
    queryFn: async () => (await apiClient.get<ApiSuccess<Contact>>(`/contacts/${id}`)).data.data,
  })
}

export function useCreateContact() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: ContactInput) => (await apiClient.post<ApiSuccess<Contact>>('/contacts', input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['contacts'] }),
  })
}

export function useUpdateContact(id: number) {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (input: Partial<Omit<ContactInput, 'companyId'>>) =>
      (await apiClient.put<ApiSuccess<Contact>>(`/contacts/${id}`, input)).data.data,
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['contacts'] }),
  })
}

export function useDeleteContact() {
  const queryClient = useQueryClient()
  return useMutation({
    mutationFn: async (id: number) => apiClient.delete(`/contacts/${id}`),
    onSuccess: () => void queryClient.invalidateQueries({ queryKey: ['contacts'] }),
  })
}
