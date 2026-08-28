import { useQuery, keepPreviousData } from '@tanstack/react-query'
import { apiClient } from './apiClient'
import type { ApiPaginated } from '@/types/api'

/**
 * Client-side half of the §10/§24 list contract — one hook drives every
 * DataTable and Kanban board (Companies, Contacts, Leads, Deals, Tasks).
 */
export interface ListParams {
  page?: number
  limit?: number
  search?: string
  sort?: string
  direction?: 'asc' | 'desc'
  [key: string]: string | number | undefined
}

export function useListQuery<T>(resource: string, params: ListParams = {}) {
  return useQuery({
    queryKey: [resource, 'list', params],
    queryFn: async () => {
      const response = await apiClient.get<ApiPaginated<T>>(`/${resource}`, { params })
      return response.data
    },
    placeholderData: keepPreviousData,
  })
}
