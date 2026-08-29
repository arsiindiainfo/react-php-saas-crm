# Demo Script

A guided walkthrough of the CRM's golden path — the same flow the
Playwright smoke suite exercises. Run this after seeding:

```bash
cd backend && php spark migrate --all && php spark db:seed DemoSeeder && php spark serve
cd frontend && npm run dev
```

Everyone's password is `Passw0rd!`.

## 1. Sign in as a rep

Go to `http://localhost:5173/login` and sign in as
**`arjun.rep@brightfield.test`**. You land on the Dashboard — KPI cards
(Customers, New Leads, Conversion Rate, Open Deals, Revenue MTD), a 12-month
revenue trend chart, and (since Arjun is a rep, not a manager) no
leaderboard panel.

## 2. Companies & Contacts

Open **Companies**. Notice the list is scoped — Arjun only sees companies he
owns, not the full Brightfield book. Click into **NovaTrail Logistics**: the
tabs — Overview / Contacts / Deals / Activity / Notes — all pull from the
same `<DataTable/>`/`useListQuery` machinery and the shared
`ActivityTimeline` component.

## 3. The flagship flow — Lead → Deal conversion

1. Open **Leads**. The board has four columns: New, Contacted, Qualified,
   Disqualified.
2. Drag a card from **New** into **Contacted**, then into **Qualified**.
   Once a lead is Qualified, a green **Convert** button appears on the card.
3. Click **Convert**. The dialog pre-fills the deal name from the lead's
   company name; optionally link it to an existing company instead of
   creating a new one. Submit.
4. You're taken to the newly created **Deal**. Behind the scenes,
   `sp_lead_convert` just created a Company, a Contact, and a Deal in one
   transaction, and the lead flipped to `CONVERTED` — try clicking Convert
   twice quickly on two different tabs and only one will succeed (that's
   the row lock the concurrency test proves).

## 4. The Deals pipeline board

Open **Deals**. Drag a card into **Won** — a small confirm dialog asks you
to confirm ("Mark {company} as a customer?"). Confirm it, then go back to
**Companies** and check that company's status is now `CUSTOMER`.

Try dragging a different open deal into **Lost** — this time the dialog
*requires* a reason before the Confirm button enables.

## 5. Tasks

Open **Tasks** — defaults to "my open tasks, due soonest first". Create one,
then mark it done. If your list is ever empty, you'll see "Nothing due —
nice work" instead of a generic empty state.

## 6. Reports

Open **Reports** — a pipeline-by-stage funnel, the same monthly revenue
trend as the dashboard, and (sign in as `priya.manager@brightfield.test` or
`admin@brightfield.test` to see this section) a salesperson leaderboard.

## 7. Ownership guardrail, made visible

Sign out and sign in as **`meera.rep@brightfield.test`** (seeded with *no*
manager, deliberately). Try opening a company or deal you saw as Arjun by
editing the URL directly (e.g. `/companies/2`) — you get a plain "Not found"
page, not an "access denied" screen. That's the §6 ownership scope enforced
at the query layer: a record outside your visibility looks identical to a
record that doesn't exist.

Now sign in as **`priya.manager@brightfield.test`** (Arjun's manager) and
open the same company — she *can* see it, because Arjun reports to her.

## 8. Admin screens

Sign in as **`admin@brightfield.test`**:

- **Team** (`/admin/users`) — invite a new user; role `SALES_REP` requires
  picking a manager.
- **Audit Log** (`/admin/audit-log`) — every sensitive action (company
  created, lead converted, deal stage changed, user invited) is logged;
  click a row to expand its JSON `details` payload.
