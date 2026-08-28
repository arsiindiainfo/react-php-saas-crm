import { setupServer } from 'msw/node'

/** Handlers are added per-test via `server.use(...)` — MSW mocks the API so no real network calls happen in component tests (§25). */
export const server = setupServer()
