import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { renderHook, waitFor } from '@testing-library/react'
import { http, HttpResponse } from 'msw'
import type { ReactNode } from 'react'
import { describe, expect, it } from 'vitest'
import { server } from '@/test/mswServer'
import { useListQuery } from './useListQuery'

function wrapper({ children }: { children: ReactNode }) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>
}

describe('useListQuery', () => {
  it('fetches the §13.2 paginated envelope and passes params through as query string', async () => {
    let capturedUrl: URL | undefined

    server.use(
      http.get('/api/v1/companies', ({ request }) => {
        capturedUrl = new URL(request.url)
        return HttpResponse.json({
          success: true,
          data: [{ id: 1, name: 'Acme' }],
          meta: { page: 1, limit: 20, total: 1, totalPages: 1 },
        })
      }),
    )

    const { result } = renderHook(() => useListQuery('companies', { search: 'acme', sort: 'name', direction: 'asc' }), {
      wrapper,
    })

    await waitFor(() => expect(result.current.isSuccess).toBe(true))

    expect(result.current.data?.data).toEqual([{ id: 1, name: 'Acme' }])
    expect(result.current.data?.meta.total).toBe(1)
    expect(capturedUrl?.searchParams.get('search')).toBe('acme')
    expect(capturedUrl?.searchParams.get('sort')).toBe('name')
  })

  it('surfaces the §13.3 error envelope on failure', async () => {
    server.use(
      http.get('/api/v1/companies', () =>
        HttpResponse.json({ success: false, error: { code: 'INTERNAL_ERROR', message: 'boom' } }, { status: 500 }),
      ),
    )

    const { result } = renderHook(() => useListQuery('companies'), { wrapper })

    await waitFor(() => expect(result.current.isError).toBe(true))
  })
})
