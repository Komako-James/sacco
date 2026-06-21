NEW TRANSACTION ACCOUNTING VERIFICATION REPORT
Date: 2026-06-17

**Executive Summary**
- Scope: Verify POSTING (read-only) of new transactions added after the audit (new members, share purchases, loan applications/disbursements).
- Work performed: Queried live `sacco_system` DB for newest members, share transactions, loans and ledger/journal entries; traced posting methods in code.
- High-level conclusion: Mixed results — share purchases for the new members are posting to the GL and are balanced; recent loans are in "applied" state (not disbursed) so no GL disbursement entries expected. However, per-member reconciliation shows at least one member with subledger amounts not reconciled to GL, indicating remaining issues. Root cause: Multiple issues (partial posting + mapping inconsistencies). Recommendation: READY FOR ACCOUNTING FIX PHASE.

**Section 1 — Test Transactions Identified**
- Newest members (top 2):
  - Member ID: 7 — Buyego Fred — 2026-06-17 10:12:59
  - Member ID: 6 — Robin Mukasa — 2026-06-17 10:12:02

- Recent share transaction IDs (from `member_share_transactions`):
  - For member 7: transaction_id 16 (transfer_in, reference STR-4-7-1781680792), transaction_id 13 (purchase, reference SHR-7-1781680671)
  - For member 6: transaction_id 14 (purchase, reference SHR-6-1781680695)

- Recent loans (new): loan_id 8 (member 6, status: applied), loan_id 7 (member 7, status: applied) — created 2026-06-17. No disbursements recorded yet.

**Section 2 — Share Purchase Trace (per new member)**
- Member 7 (Buyego Fred):
  - Transactions: SHR-7-1781680671 (purchase, amount 40000.00), STR-4-7-1781680792 (transfer_in, amount 40000.00)
  - Ledger entries (sample): entry_id 5 (ledger_code 2020, debit 40000.00), entry_id 6 (ledger_code 2010, credit 40000.00) for SHR-7-1781680671; entry_id 10 and 9 for STR transfer.
  - Transaction balance: balanced (debits == credits) for both reference numbers.
  - Status: PASS (transaction exists; ledger entries exist and are balanced).

- Member 6 (Robin Mukasa):
  - Transaction: SHR-6-1781680695 (purchase, amount 30000.00)
  - Ledger entries: entry_id 7 (2020 debit 30000.00), entry_id 8 (2010 credit 30000.00)
  - Transaction balance: PASS (balanced).

**Section 3 — Loan Disbursement Trace**
- New loans 7 and 8 are in `applied` state with no `disbursement_date`; therefore no GL disbursement postings expected. For historically disbursed loans (IDs 2,3,4,5) ledger lines exist and are balanced.
- For loans with status `disbursed` or `completed` ledger entries are present and grouped by transaction_reference; ref-level balances are balanced.
- Status: PASS for disbursed loans already processed historically; NEW loans: N/A (not disbursed yet).

**Section 4 — Member → GL Reconciliation (new members)**
- Member 7:
  - Subledger: shares total_invested = 80000.00; savings_balance = 10000.00; loans_outstanding = 0.00
  - GL: ledger_code 2020 net_balance = 40000.00; ledger_code 2010 net_balance = -80000.00
  - Variance exists; reconciliation indicates partial posting patterns. Status: FAIL/Requires investigation.

- Member 6:
  - Subledger: shares total_invested = 30000.00; savings_balance = 20000.00
  - GL: ledger_code 2020 net_balance = 30000.00; ledger_code 2010 net_balance = -30000.00
  - Net GL (sum)=0 while subledger sum != 0 → indicates subledger values not fully reflected at GL control level. Status: FAIL.

**Section 5 — Posting Engine Execution (code trace)**
- Methods referencing ledger posting:
  - `postSharePurchaseFromSavings`: found in [app/Services/LedgerService.php](app/Services/LedgerService.php) and [app/Services/ShareService.php](app/Services/ShareService.php)
  - `postSavingsDeposit`: found in [app/Services/LedgerService.php](app/Services/LedgerService.php) and [app/Services/SavingsService.php](app/Services/SavingsService.php)
  - `postLoanDisbursement`: found in [app/Services/LedgerService.php](app/Services/LedgerService.php) and [app/Services/LoanService.php](app/Services/LoanService.php)
  - `postLoanRepayment`: found in [app/Services/LedgerService.php](app/Services/LedgerService.php) and [app/Services/LoanService.php](app/Services/LoanService.php)
  - Interest posting methods found in `InterestCalculationService` and call into `LedgerService`.

**Section 6 — Ledger Entry Inspection (new members)**
- All ledger entries for members 6 and 7 were retrieved and are included in the audit data (file: `scripts/trace_new_transactions.php` prints the raw JSON when run). Key ledger entries for share purchases are balanced at transaction-reference level.

**Section 7 — COA Mapping Validation**
- `chart_of_accounts` vs `LedgerService` constants were compared. The script matched numeric account codes to constants; presence checks mostly matched (account code exists in both DB and constants). However semantic mismatches remain (example: `COA_MEMBER_SHARES` constant maps to account code `2010` while `chart_of_accounts` shows `2010` labelled `Member Savings`) — this indicates naming/semantic mismatch between code constants and DB account names and should be resolved before remediation postings.

**Section 8 — Root Cause Confirmation**
- Per-member heuristic results:
  - Member 7: `E` (Multiple issues or partial posting)
  - Member 6: `C` (Subledger updated but GL net is zero for combined controls — indicates partial or offset posting patterns)
- Overall conclusion: E (Multiple issues) — evidence shows some new transactions post correctly to GL and are balanced, while others exhibit reconciliation variances and semantic COA mismatches.

**Readiness Score (0-100)**
- Data integrity readiness: 50/100
  - Strengths: Transaction-level postings for share purchases are present and balanced; ledger journal lines exist for many historical transactions.
  - Weaknesses: Subledger vs GL variances for new members; COA semantic mismatches; interest/loan lifecycle postings need verification.

**Decision**
- Recommendation: READY FOR ACCOUNTING FIX PHASE — proceed with a controlled remediation plan:
  1) Decide authoritative COA (DB or migration seed) and align `LedgerService` constants. (see ADR 0001)
  2) Generate safe, auditable adjustment JE scripts (dry-run first) to backfill missing GL control amounts for Savings/Shares/Loans.
  3) Add automated integration tests that assert subledger ↔ GL control parity.

**Artifacts produced**
- `scripts/trace_new_transactions.php` — read-only PHP trace script (in repo)
- `scripts/reconciliation_dryrun.sql` — dry-run SQL to compute control variances
- `PROJECT_BIBLE.md`, `ACCOUNTING_FINDINGS_REGISTER.md`, `DECISION_LOG.md`, ADRs and `RISK_REGISTER.md` (in repo root)

If you want, I can now:
- (A) Generate the adjusting JE SQL (dry-run only) for the specific variances found for members 6 and 7, or
- (B) Produce a formatted PDF version of this verification report.

---
Generated by: audit script (read-only), 2026-06-17
