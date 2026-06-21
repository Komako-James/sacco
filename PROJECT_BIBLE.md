PROJECT BIBLE — SACCO Accounting Audit & Certification

Purpose
- Single-source overview of the SACCO accounting certification engagement (PHASE 2C).
- Reference for scope, authority, data sources, and deliverables.

Scope
- Live reconciliation and certification of Savings, Shares, Loans, Interest and Financial Statements based on the `sacco_system` database and the current codebase in `app/Services`.
- Verification-only engagement: no code or data changes performed in this engagement.

Primary Contacts
- Audit Lead: [You]
- Engineering Lead: (from repository maintainers)

Authoritative Sources
- Live DB: `sacco_system` (via `config/db_connection.php`)
- Code: `app/Services/LedgerService.php`, `LoanService.php`, `InterestCalculationService.php`, `SavingsService.php`, `ShareService.php`.
- Seeds/migrations: `config/database_refactored.sql`, `migrations/008_accounting_foundation.sql`.

Key Findings (summary)
- General Ledger mechanically balances (Debits = Credits) but is materially incomplete vs operational subledgers.
- Large variances discovered: Savings, Shares, Loans control accounts not reconciled with subledgers.
- COA mapping mismatch between code constants and `chart_of_accounts` table.

Deliverables
- SACCO ACCOUNTING CERTIFICATION REPORT (executive summary, evidence matrix, reconciliations, recommendations)
- Accounting Findings Register
- Risk Register
- Decision Log & ADRs

Repository Map (important files)
- `app/Services/LedgerService.php` — central posting logic and reporting
- `app/Services/LoanService.php` — loan lifecycle and calls to ledger
- `app/Services/InterestCalculationService.php` — interest calc and posting
- `app/Services/SavingsService.php`, `app/Services/ShareService.php` — operational services calling ledger
- `config/db_connection.php` — DB credentials used for audit

How to use this document
- Refer to ADRs for design decisions.
- Use Accounting Findings Register for remediation and tracking.
- Update Decision Log when making changes during remediation.

Status
- Generated: 2026-06-17
- This document is read-only for the certification phase; any remediation work should be tracked via ADRs and the Decision Log.
