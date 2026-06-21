ADR 0001 — Authoritative Chart of Accounts

Status: Proposed

Context
- Audit revealed COA differences between code constants in `LedgerService` and the live `chart_of_accounts` table.
- Mismatches cause postings to land in unexpected accounts and lead to subledger vs GL variance.

Decision
- Designate the `chart_of_accounts` table in the live database as the authoritative COA for the system.
- All code mappings (constants in `LedgerService`) must be reconciled to the live table or the table must be migrated to match the chosen canonical COA.

Consequences
- Immediate need to align code or DB before any remediation posting is performed.
- Backfill and reconciliation scripts must reference the authoritative COA.

References
- `app/Services/LedgerService.php` COA constants
- `config/database_refactored.sql` and `migrations/008_accounting_foundation.sql`

Date: 2026-06-17
Proposed by: Audit Lead
