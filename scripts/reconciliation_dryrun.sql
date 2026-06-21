-- Reconciliation dry-run script
-- Computes required adjusting journal entries to align GL control accounts with subledgers

-- 1) Savings variance
SELECT 'SAVINGS' AS ledger, SUM(balance) as subledger_total FROM savings_accounts;
SELECT account_code, SUM(CASE WHEN debit_credit='D' THEN amount ELSE -amount END) as gl_balance FROM ledger_entries WHERE account_code IN ('2010','2020') GROUP BY account_code;

-- 2) Shares variance
SELECT 'SHARES' AS ledger, SUM(quantity * price) as subledger_total FROM member_share_holdings;
SELECT account_code, SUM(CASE WHEN debit_credit='D' THEN amount ELSE -amount END) as gl_balance FROM ledger_entries WHERE account_code IN ('3010') GROUP BY account_code;

-- 3) Loans variance
SELECT 'LOANS' AS ledger, SUM(outstanding_balance) as subledger_total FROM loans WHERE status!='written_off';
SELECT account_code, SUM(CASE WHEN debit_credit='D' THEN amount ELSE -amount END) as gl_balance FROM ledger_entries WHERE account_code IN ('1030') GROUP BY account_code;

-- Note: This script is read-only and must be validated before performing any writes.
