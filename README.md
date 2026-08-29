# React + PHP SaaS CRM

A small-business CRM — companies, contacts, a lead pipeline with real
conversion logic, a deal pipeline board, tasks, an activity timeline,
dashboards and reports — built with **React 19 + TypeScript**, **PHP 8.2 +
CodeIgniter 4**, and **MySQL 8**, anchored by one reusable server-side list
API that every list-style module (Companies, Contacts, Leads, Deals, Tasks)
shares.

Modeled on a fictional small business, **Brightfield Business Solutions**.
All data — the company, its team, its leads and deals — is synthetic,
generated for this portfolio build.

> © 2026 Arsi India Info. Code is MIT-licensed (see [`LICENSE`](./LICENSE));
> the Arsi India Info name and logo are not — see [`TRADEMARK.md`](./TRADEMARK.md).

## Why this project

Most CRM demos are CRUD-and-forget. This one is built around two pieces of
real engineering depth:

1. **A transactional, multi-table lead-to-deal conversion** — `sp_lead_convert`
   turns a qualified Lead into a Company + Contact + Deal atomically, guarded
   by a `SELECT ... FOR UPDATE` row lock so a double-click can't create
   duplicates (proven by a concurrency test that fires two real, concurrent
   HTTP requests at the same lead).
2. **One server-side list contract, five resources.** `sp_records_search`
   plus `ListQueryParser` implement `page/limit/search/sort/direction` once
   and every list screen — Companies, Contacts, Leads, Deals, Tasks — reuses
   it, both on the API and in the React `<DataTable/>`/`useListQuery` layer.

Ownership-based row visibility (§6 in the plan) is enforced in the query
layer via `OwnershipScopeFilter`, not hidden client-side: a sales rep only
ever sees their own pipeline, a manager sees their direct reports' pipeline
too, and an admin sees everything — a stale link to someone else's record
returns a plain `404`, never a client-hidden row.

The full design rationale — schema, stored procedures, API contract, screen
spec — is captured in this project's implementation plan (kept outside this
repository); the highlights are summarized in [`docs/`](./docs).

## Tech stack

| Layer | Choices |
| --- | --- |
| Frontend | React 19, TypeScript, Vite, TanStack Query, Tailwind CSS v4, React Hook Form + Zod, `@dnd-kit`, Recharts |
| Backend | PHP 8.2, CodeIgniter 4.7, MySQL 8 (business logic in stored procedures), firebase/php-jwt |
| Testing | PHPUnit 10 (feature + unit), Vitest + React Testing Library + MSW |
| Infra | Docker Compose (MySQL, Mailhog) for local dev |

## Repository layout

```
react-php-saas-crm/
├── backend/            CodeIgniter 4 API — app/Controllers, Services, Models, Database/{Migrations,Procedures,Seeds}
├── frontend/            React + TypeScript + Vite SPA — src/{app,features,components,lib}
├── shared/               Enum lists mirrored between the PHP and TS layers
├── infrastructure/       docker-compose.yml (MySQL + Mailhog)
├── docs/                  ER diagram, demo script, OpenAPI notes
├── LICENSE                MIT
└── TRADEMARK.md            Arsi India Info name/logo notice
```

## Getting started

### 1. Start MySQL + Mailhog

```bash
cd infrastructure
docker compose up -d
```

This maps MySQL to **host port 3307** (not the default 3306) to avoid
clashing with a local MySQL/XAMPP install — `backend/.env.example` already
points at 3307. If that port is also taken on your machine, remap it in
`docker-compose.yml` and update `backend/.env` to match.

### 2. Backend

```bash
cd backend
composer install
cp .env.example .env      # adjust JWT_SECRET etc. for anything beyond local dev
php spark migrate --all   # creates all 9 tables + loads all 12 stored procedures
php spark db:seed DemoSeeder   # the full Brightfield dataset — see below
php spark serve            # http://localhost:8080
```

Everyone seeded by `DemoSeeder` shares the password `Passw0rd!`. Sign in as
`admin@brightfield.test` for the full admin view, or `arjun.rep@brightfield.test`
/ `priya.manager@brightfield.test` to see rep- and manager-scoped visibility
in action.

### 3. Frontend

```bash
cd frontend
npm install
npm run dev                 # http://localhost:5173, proxies /api to :8080
```

### Running the tests

```bash
cd backend && composer test        # PHPUnit — migrates/seeds a separate `_test` database automatically
cd frontend && npm run test        # Vitest + MSW, no real network calls
```

## The demo data

`DemoSeeder` builds a full "Brightfield Business Solutions" sales org — an
admin, two managers, four reps — and:

- **13 companies** across PROSPECT / CUSTOMER / CHURNED, each with a contact
- **~20 deals** spread across all five stages, with 8 WON deals dated across
  the last several months (so the dashboard's revenue trend chart isn't empty)
- **14 leads** covering every status, including two fully converted ones
  (company + contact + deal all created, linked back to the originating lead)
- **10 tasks** (open and completed, various priorities/due dates)
- **12 activities** (calls, emails, meetings, notes) across the timeline
- **8 audit log entries** so the admin audit trail has content on first login

See [`docs/demo-script.md`](./docs/demo-script.md) for a guided walkthrough,
and [`docs/er-diagram.md`](./docs/er-diagram.md) for the schema.

## API documentation

`GET /api/docs` serves the generated OpenAPI spec in non-production
environments — see [`docs/openapi.md`](./docs/openapi.md) for how it's
generated and where the contract lives.

## Scope, deliberately

One currency, no custom-field builder, no email/telephony integration, no
quotes/invoicing, no marketing automation. This CRM is deployable on any
standard PHP 8.2 + MySQL 8 host — no cloud-vendor lock-in required.
