# Entity-Relationship Diagram

The 9 tables behind the CRM (§7 of the implementation plan). Renders natively
on GitHub — no external tool needed to view it.

```mermaid
erDiagram
    users ||--o{ users : "manages (manager_id)"
    users ||--o{ companies : owns
    users ||--o{ contacts : owns
    users ||--o{ leads : owns
    users ||--o{ deals : owns
    users ||--o{ tasks : "assigned to"
    users ||--o{ activities : logs
    users ||--o{ audit_logs : performs
    users ||--o{ refresh_tokens : holds

    companies ||--o{ contacts : has
    companies ||--o{ deals : has
    companies ||--o{ leads : "converted into"

    contacts ||--o{ deals : "primary contact for"
    contacts ||--o{ leads : "converted into"

    leads ||--o| deals : "converts to"

    users {
        bigint id PK
        varchar name
        varchar email UK
        varchar password_hash
        enum role "ADMIN, SALES_MANAGER, SALES_REP"
        bigint manager_id FK
        enum status "ACTIVE, DISABLED"
    }

    companies {
        bigint id PK
        varchar name UK
        varchar industry
        varchar website
        enum status "PROSPECT, CUSTOMER, CHURNED"
        bigint owner_id FK
    }

    contacts {
        bigint id PK
        bigint company_id FK
        varchar first_name
        varchar last_name
        varchar email
        bigint owner_id FK
    }

    leads {
        bigint id PK
        varchar first_name
        varchar last_name
        varchar company_name "free text, pre-conversion"
        enum source "WEBSITE, REFERRAL, COLD_CALL, EVENT, OTHER"
        enum status "NEW, CONTACTED, QUALIFIED, CONVERTED, DISQUALIFIED"
        bigint owner_id FK
        bigint converted_company_id FK
        bigint converted_contact_id FK
        bigint converted_deal_id FK
    }

    deals {
        bigint id PK
        bigint company_id FK
        bigint contact_id FK
        bigint lead_id FK
        varchar name
        decimal value_amount
        enum stage "PROSPECTING, PROPOSAL, NEGOTIATION, WON, LOST"
        varchar lost_reason
        bigint owner_id FK
    }

    tasks {
        bigint id PK
        varchar subject
        date due_date
        enum priority "LOW, MEDIUM, HIGH"
        enum related_to_type "COMPANY, CONTACT, LEAD, DEAL — app-enforced, no real FK"
        bigint related_to_id
        bigint assigned_to FK
        bigint created_by FK
        datetime completed_at
    }

    activities {
        bigint id PK
        enum type "NOTE, CALL, EMAIL, MEETING"
        varchar body
        datetime occurred_at
        enum related_to_type "COMPANY, CONTACT, LEAD, DEAL — app-enforced, no real FK"
        bigint related_to_id
        bigint created_by FK
    }

    audit_logs {
        bigint id PK
        bigint user_id FK
        varchar action
        varchar entity_type
        bigint entity_id
        json details
    }

    refresh_tokens {
        bigint id PK
        bigint user_id FK
        char token_hash UK
        datetime expires_at
        datetime revoked_at
    }
```

## Notes

- **`tasks`/`activities`' polymorphic link** (`related_to_type`/`related_to_id`)
  can't be a real foreign key across four possible target tables — MySQL has
  no polymorphic FK. It's enforced in the Service layer instead: the target
  must exist and be visible to the caller before the insert (§7.4).
- **`leads` ↔ `deals` is circular** (a lead converts into a deal; a deal can
  point back to the lead it came from) — handled with two migrations: create
  `leads` without the `converted_deal_id` FK, create `deals`, then add the FK
  back onto `leads` once `deals` exists.
- **Companies and Customers are one table.** A "customer" is just a company
  whose `status` has advanced to `CUSTOMER` — see the plan's §7.3 decision.
- **Notes and Activities are one table.** A "note" is an Activity with
  `type = NOTE`; the Notes tab is just the Activity timeline filtered to it.
