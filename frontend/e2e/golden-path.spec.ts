import { expect, test, type Page } from '@playwright/test'

/**
 * The golden path (§25): login → create a lead → qualify it → convert it →
 * drag the resulting deal to Won → confirm the company shows as a Customer
 * on the dashboard. Run against a real backend + DemoSeeder-seeded DB —
 * `npm run e2e` (see package.json) after `php spark serve` is up.
 */

async function dragCard(page: Page, cardText: string, targetColumnTitle: string) {
  const card = page.locator(`text=${cardText}`).first()
  const column = page.locator('div', { has: page.locator(`h3:has-text("${targetColumnTitle}")`) }).last()

  const cardBox = await card.boundingBox()
  const columnBox = await column.boundingBox()
  if (!cardBox || !columnBox) throw new Error('Could not locate drag source/target')

  await page.mouse.move(cardBox.x + cardBox.width / 2, cardBox.y + cardBox.height / 2)
  await page.mouse.down()
  await page.mouse.move(columnBox.x + columnBox.width / 2, columnBox.y + 60, { steps: 10 })
  await page.mouse.move(columnBox.x + columnBox.width / 2, columnBox.y + 80, { steps: 5 })
  await page.mouse.up()
}

test('login, convert a lead, win the deal, see it on the dashboard', async ({ page }) => {
  const uniqueName = `E2E Corp ${Date.now()}`

  // 1. Sign in
  await page.goto('/login')
  await page.fill('#email', 'arjun.rep@brightfield.test')
  await page.fill('#password', 'Passw0rd!')
  await page.click('button[type=submit]')
  await expect(page).toHaveURL(/\/dashboard/)

  // 2. Create a lead — NewLeadDialog's inputs, in DOM order: firstName, lastName, email, phone, companyName
  await page.goto('/leads')
  await page.click('text=+ New Lead')
  const dialog = page.locator('div.fixed').last()
  await dialog.locator('input').nth(0).fill('Taylor')
  await dialog.locator('input').nth(1).fill('Reed')
  await dialog.locator('input').nth(4).fill(uniqueName)
  await dialog.getByRole('button', { name: 'Create' }).click()
  await expect(page.locator(`text=${uniqueName}`)).toBeVisible()

  // 3. Qualify it — drag New -> Contacted -> Qualified
  await dragCard(page, 'Taylor Reed', 'Contacted')
  await expect(page.locator('text=Taylor Reed')).toBeVisible()
  await dragCard(page, 'Taylor Reed', 'Qualified')

  // 4. Convert
  await page.locator('text=Taylor Reed').locator('..').getByRole('button', { name: 'Convert' }).click()
  const convertDialog = page.locator('div.fixed').last()
  await convertDialog.getByRole('button', { name: 'Convert' }).click()
  await expect(page).toHaveURL(/\/deals\/\d+/)

  // 5. Win the deal
  await page.goto('/deals')
  await dragCard(page, uniqueName, 'Won')
  await page.getByRole('button', { name: /Mark as customer/i }).click()

  // 6. Confirm the company is now a Customer
  await page.goto('/companies')
  await page.fill('input[placeholder="Search companies…"]', uniqueName)
  await expect(page.locator('text=CUSTOMER')).toBeVisible()
})
