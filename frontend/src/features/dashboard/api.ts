import { useQuery } from '@tanstack/react-query'
import { apiClient } from '@/lib/apiClient'
import type { ApiSuccess } from '@/types/api'

export interface DashboardSummary {
  totals: {
    totalCompanies: string | number
    newLeadsThisMonth: string | number
    conversionRate: string | number
    openDealsCount: string | number
    openDealsValue: string | number
    revenueThisMonth: string | number
  }
  monthlyTrend: { month: string; revenue: string | number }[]
}

export function useDashboardSummary() {
  return useQuery({
    queryKey: ['dashboard', 'summary'],
    queryFn: async () => (await apiClient.get<ApiSuccess<DashboardSummary>>('/dashboard/summary')).data.data,
  })
}
