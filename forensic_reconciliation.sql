-- forensic_reconciliation.sql
-- Read-only queries to perform the forensic reconciliation steps requested.
-- Run these queries on your staging/read-only connection. Export results as JSON or CSV and paste outputs into the analyzer or here.

-- 1) Canonical Chart of Accounts (full table)
-- Export the entire table
SELECT account_code, account_name, account_type, account_category
FROM chart_of_accounts
ORDER BY account_code;

-- 1b) Helpful filter to find likely COA rows by keywords (savings, deposits, share, cash, bank, loan, interest)
SELECT account_code, account_name, account_type, account_category
FROM chart_of_accounts
WHERE LOWER(account_name) REGEXP 'save|deposit|share|cash|bank|loan|interest'
ORDER BY account_code;

-- 2) Actual GL activity by ledger code
SELECT ledger_code,
       ledger_name,
       COUNT(*) AS entries,
       SUM(debit) AS total_debit,
       SUM(credit) AS total_credit,
       SUM(debit)-SUM(credit) AS balance
FROM ledger_entries
GROUP BY ledger_code, ledger_name
ORDER BY ledger_code;

-- 2b) Detect codes with multiple ledger_name values
SELECT ledger_code,
       COUNT(DISTINCT ledger_name) AS distinct_names,
       GROUP_CONCAT(DISTINCT ledger_name SEPARATOR ' ||| ') AS names_list
FROM ledger_entries
GROUP BY ledger_code
HAVING COUNT(DISTINCT ledger_name) > 1
ORDER BY ledger_code;

-- 3) Locate hidden or misclassified savings/share postings (search descriptions and transaction_type)
SELECT transaction_reference, entry_id, entry_date, ledger_code, ledger_name, debit, credit, description, transaction_type, receipt_number, member_id
FROM ledger_entries
WHERE LOWER(COALESCE(description,'')) REGEXP 'savings|deposit|withdrawal|transfer|share|purchase|contribution'
   OR LOWER(COALESCE(transaction_type,'')) REGEXP 'savings|deposit|withdrawal|transfer|share|purchase|contribution'
ORDER BY entry_date DESC
LIMIT 2000;

-- 4) Member-level reconciliation: top 20 members by savings subledger
-- A: savings subledger totals (top 20)
SELECT sa.member_id,
       COALESCE(m.full_name,'') AS full_name,
       SUM(se.amount) AS savings_subledger
FROM savings_transactions se
LEFT JOIN savings_accounts sa ON se.account_id = sa.account_id
LEFT JOIN members m ON sa.member_id = m.member_id
WHERE se.status IN ('completed','posted')
GROUP BY sa.member_id
ORDER BY savings_subledger DESC
LIMIT 20;

-- B: GL balances per member for savings-related ledger codes (adjust codes if necessary)
SELECT le.member_id,
       SUM(le.debit)-SUM(le.credit) AS gl_balance
FROM ledger_entries le
WHERE le.ledger_code IN ('2010','2020')
GROUP BY le.member_id
ORDER BY gl_balance DESC
LIMIT 500;

-- C: Combined top-20 reconciliation (join A and B)
-- Run the two queries above and then run this (or run as a single nested query if you prefer):
SELECT s.member_id,
       COALESCE(m.full_name,'') AS member_name,
       s.savings_subledger,
       COALESCE(g.gl_balance,0) AS gl_balance,
       s.savings_subledger - COALESCE(g.gl_balance,0) AS variance
FROM (
  SELECT sa.member_id, SUM(se.amount) AS savings_subledger
  FROM savings_transactions se
  LEFT JOIN savings_accounts sa ON se.account_id = sa.account_id
  WHERE se.status IN ('completed','posted')
  GROUP BY sa.member_id
  ORDER BY savings_subledger DESC
  LIMIT 20
) s
LEFT JOIN (
  SELECT le.member_id, SUM(le.debit)-SUM(le.credit) AS gl_balance
  FROM ledger_entries le
  WHERE le.ledger_code IN ('2010','2020')
  GROUP BY le.member_id
) g ON s.member_id = g.member_id
LEFT JOIN members m ON s.member_id = m.member_id
ORDER BY s.savings_subledger DESC;

-- 5) Investigate orphan share transactions (STR refs)
-- A: member_share_transactions
SELECT * FROM member_share_transactions WHERE reference_number IN ('STR-3-4-1780077242','STR-3-4-1780077070');

-- B: audit_logs (search fields that might contain the reference)
SELECT * FROM audit_logs
WHERE (old_values LIKE '%STR-3-4-1780077242%' OR new_values LIKE '%STR-3-4-1780077242%' OR action LIKE '%STR-3-4-1780077242%')
   OR (old_values LIKE '%STR-3-4-1780077070%' OR new_values LIKE '%STR-3-4-1780077070%' OR action LIKE '%STR-3-4-1780077070%')
ORDER BY timestamp DESC
LIMIT 200;

-- C: generic search in common log tables (activity_logs, transaction_logs, backups) if they exist
-- If your schema has other logs replace table names accordingly or run the following pattern per table:
-- SELECT * FROM activity_logs WHERE message LIKE '%STR-3-4-1780077242%' OR message LIKE '%STR-3-4-1780077070%';

-- D: ledger_entries search by transaction_reference
SELECT * FROM ledger_entries WHERE transaction_reference IN ('STR-3-4-1780077242','STR-3-4-1780077070');

-- 6) Recompute control-account reconciliation for COA codes (use canonical codes found in task 1)
-- Adjust the ledger_code list if your canonical COA uses different codes for Savings and Shares
SELECT ledger_code, ledger_name, SUM(debit) AS total_debit, SUM(credit) AS total_credit, SUM(debit)-SUM(credit) AS gl_balance
FROM ledger_entries
WHERE ledger_code IN ('2010','2020')
GROUP BY ledger_code, ledger_name;

-- 6b) Compute variance using provided subledger totals (replace the numbers below with values you obtained)
-- Example: replace 34115000.00 and 610000.00 with your extracted subledger totals if different
SELECT '2020' AS ledger_code, 'Savings Control' AS name, (SELECT SUM(debit)-SUM(credit) FROM ledger_entries WHERE ledger_code='2020') AS gl_balance, 34115000.00 AS subledger_total, ((SELECT SUM(debit)-SUM(credit) FROM ledger_entries WHERE ledger_code='2020') - 34115000.00) AS variance;

SELECT '2010' AS ledger_code, 'Shares Control' AS name, (SELECT SUM(debit)-SUM(credit) FROM ledger_entries WHERE ledger_code='2010') AS gl_balance, 610000.00 AS subledger_total, ((SELECT SUM(debit)-SUM(credit) FROM ledger_entries WHERE ledger_code='2010') - 610000.00) AS variance;

-- 7) Helper: search for likely migrated/historical indicators (old_import, migrated, legacy)
SELECT * FROM ledger_entries WHERE LOWER(COALESCE(description,'')) REGEXP 'migrat|import|legacy|opening balance|brought forward' ORDER BY entry_date DESC LIMIT 500;

-- End of forensic_reconciliation.sql
