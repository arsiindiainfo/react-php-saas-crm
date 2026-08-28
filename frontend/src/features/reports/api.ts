import { useQuery } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'

export interface StageBucket {
  stage: string
  count: string | number
  value: string | number
}

export interface MonthlySalesPoint {
  month: string
  revenue: string | number
}

export interface PerformanceRow {
  userId: number
  name: string
  dealsWon: string | number
  revenue: string | number
}

export function usePipelineByStage() {
  return useQuery({
    queryKey: ['reports', 'pipeline-by-stage'],
    queryFn: async () => (await apiClient.get<ApiSuccess<StageBucket[]>>('/reports/pipeline-by-stage')).data.data,
  })
}

export function useMonthlySales() {
  return useQuery({
    queryKey: ['reports', 'monthly-sales'],
    queryFn: async () => (await apiClient.get<ApiSuccess<MonthlySalesPoint[]>>('/reports/monthly-sales')).data.data,
  })
}

export function useSalespersonPerformance(enabled: boolean) {
  return useQuery({
    queryKey: ['reports', 'salesperson-performance'],
    queryFn: async () => (await apiClient.get<ApiSuccess<PerformanceRow[]>>('/reports/salesperson-performance')).data.data,
    enabled,
  })
}
