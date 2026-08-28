import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { render, screen, waitFor } from '@testing-library/react'
import userEvent from '@testing-library/user-event'
import { MemoryRouter } from 'react-router-dom'
import { http, HttpResponse } from 'msw'
import { describe, expect, it, vi } from 'vitest'
import { server } from '@/test/mswServer'
import { ToastProvider } from '@/components/Toast'
import { ConvertLeadDialog } from './ConvertLeadDialog'
import type { Lead } from '@/types/entities'

const lead: Lead = {
  id: 1,
  firstName: 'Sam',
  lastName: 'Adworth',
  email: null,
  phone: null,
  companyName: 'Sam Industries',
  source: 'WEBSITE',
  status: 'QUALIFIED',
  disqualifyReason: null,
  ownerId: 1,
  convertedAt: null,
  convertedCompanyId: null,
  convertedContactId: null,
  convertedDealId: null,
  createdAt: '2026-01-01T00:00:00Z',
  updatedAt: '2026-01-01T00:00:00Z',
}

function renderDialog(onClose = vi.fn()) {
  const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } })
  render(
    <QueryClientProvider client={queryClient}>
      <MemoryRouter>
        <ToastProvider>
          <ConvertLeadDialog lead={lead} onClose={onClose} />
        </ToastProvider>
      </MemoryRouter>
    </QueryClientProvider>,
  )
  return { onClose }
}

describe('ConvertLeadDialog', () => {
  it('pre-fills the deal name from the lead company', () => {
    renderDialog()
    expect(screen.getByLabelText(/deal name/i)).toHaveValue('Sam Industries — New Business')
  })

  it('blocks submission when the deal name is cleared', async () => {
    const user = userEvent.setup()
    let convertWasCalled = false
    server.use(
      http.post('/api/v1/leads/:id/convert', () => {
        convertWasCalled = true
        return HttpResponse.json({ success: true, data: {} })
      }),
    )

    renderDialog()

    const nameInput = screen.getByLabelText(/deal name/i)
    await user.clear(nameInput)
    await user.click(screen.getByRole('button', { name: /convert/i }))

    await waitFor(() => expect(screen.getByText('Required')).toBeInTheDocument())
    expect(convertWasCalled).toBe(false)
  })

  it('submits the convert request with the pre-filled deal name', async () => {
    const user = userEvent.setup()
    let requestBody: unknown

    server.use(
      http.post('/api/v1/leads/:id/convert', async ({ request }) => {
        requestBody = await request.json()
        return HttpResponse.json({
          success: true,
          data: { company: { id: 1 }, contact: { id: 1 }, deal: { id: 1 } },
        })
      }),
    )

    const { onClose } = renderDialog()
    await user.click(screen.getByRole('button', { name: /convert/i }))

    await waitFor(() => expect(onClose).toHaveBeenCalled())
    expect(requestBody).toMatchObject({ dealName: 'Sam Industries — New Business' })
  })
})
