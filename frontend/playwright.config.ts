import { defineConfig, devices } from '@playwright/test'

/**
 * §25 — the one end-to-end smoke path: login → create a lead → qualify it →
 * convert it → drag the resulting deal to Won → confirm the company shows
 * as a Customer on the dashboard. Requires the backend (php spark serve)
 * and DemoSeeder-seeded database to already be running — this suite drives
 * the real API, it doesn't mock anything.
 */
export default defineConfig({
  testDir: './e2e',
  fullyParallel: false,
  retries: 0,
  reporter: 'list',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:5173',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
})
