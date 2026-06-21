-- forensic_reconciliation_json.sql
-- Single-query JSON output for forensic reconciliation. Run via MySQL CLI to get one JSON document.

SET SESSION group_concat_max_len = 10000000;

SELECT JSON_OBJECT(
  'coa', (SELECT JSON_ARRAYAGG(JSON_OBJECT('account_code', account_code, 'account_name', account_name, 'account_type', account_type, 'account_category', account_category)) FROM chart_of_accounts),
  'coa_matches', (SELECT JSON_ARRAYAGG(JSON_OBJECT('account_code', account_code, 'account_name', account_name, 'account_type', account_type, 'account_category', account_category)) FROM chart_of_accounts WHERE LOWER(account_name) REGEXP 'save|deposit|share|cash|bank|loan|interest'),

  'gl_activity', (SELECT JSON_ARRAYAGG(JSON_OBJECT('ledger_code', ledger_code, 'ledger_name', ledger_name, 'entries', entries, 'total_debit', total_debit, 'total_credit', total_credit, 'balance', balance)) FROM (SELECT ledger_code, ledger_name, COUNT(*) AS entries, SUM(debit) AS total_debit, SUM(credit) AS total_credit, SUM(debit)-SUM(credit) AS balance FROM ledger_entries GROUP BY ledger_code, ledger_name ORDER BY ledger_code) x),

  'multi_names', (SELECT JSON_ARRAYAGG(JSON_OBJECT('ledger_code', ledger_code, 'distinct_names', distinct_names, 'names_list', names_list)) FROM (SELECT ledger_code, COUNT(DISTINCT ledger_name) AS distinct_names, GROUP_CONCAT(DISTINCT ledger_name SEPARATOR ' ||| ') AS names_list FROM ledger_entries GROUP BY ledger_code HAVING COUNT(DISTINCT ledger_name) > 1) y),

  'hidden_postings', (SELECT JSON_ARRAYAGG(JSON_OBJECT('transaction_reference', transaction_reference,'entry_id',entry_id,'entry_date',entry_date,'ledger_code',ledger_code,'ledger_name',ledger_name,'debit',debit,'credit',credit,'description',description,'transaction_type',transaction_type,'receipt_number',receipt_number,'member_id',member_id)) FROM (SELECT transaction_reference, entry_id, entry_date, ledger_code, ledger_name, debit, credit, description, transaction_type, receipt_number, member_id FROM ledger_entries WHERE LOWER(COALESCE(description,'')) REGEXP 'savings|deposit|withdrawal|transfer|share|purchase|contribution' OR LOWER(COALESCE(transaction_type,'')) REGEXP 'savings|deposit|withdrawal|transfer|share|purchase|contribution' ORDER BY entry_date DESC LIMIT 2000) h),

  'top20_savings_subledger', (SELECT JSON_ARRAYAGG(JSON_OBJECT('member_id', member_id,'full_name',full_name,'savings_subledger',savings_subledger)) FROM (SELECT sa.member_id, COALESCE(m.full_name,'') AS full_name, SUM(se.amount) AS savings_subledger FROM savings_transactions se LEFT JOIN savings_accounts sa ON se.account_id = sa.account_id LEFT JOIN members m ON sa.member_id = m.member_id WHERE se.status IN ('completed','posted') GROUP BY sa.member_id ORDER BY savings_subledger DESC LIMIT 20) t),

  'gl_per_member_top', (SELECT JSON_ARRAYAGG(JSON_OBJECT('member_id', member_id,'gl_balance',gl_balance)) FROM (SELECT le.member_id, SUM(le.debit)-SUM(le.credit) AS gl_balance FROM ledger_entries le WHERE le.ledger_code IN ('2010','2020') GROUP BY le.member_id) g),

  'combined_reconciliation_top20', (SELECT JSON_ARRAYAGG(JSON_OBJECT('member_id', member_id, 'member_name', member_name, 'savings_subledger', savings_subledger, 'gl_balance', gl_balance, 'variance', variance)) FROM (SELECT s.member_id, COALESCE(m.full_name,'') member_name, s.savings_subledger, COALESCE(g.gl_balance,0) gl_balance, s.savings_subledger - COALESCE(g.gl_balance,0) variance FROM (SELECT sa.member_id, SUM(se.amount) AS savings_subledger FROM savings_transactions se LEFT JOIN savings_accounts sa ON se.account_id = sa.account_id WHERE se.status IN ('completed','posted') GROUP BY sa.member_id ORDER BY savings_subledger DESC LIMIT 20) s LEFT JOIN (SELECT le.member_id, SUM(le.debit)-SUM(le.credit) AS gl_balance FROM ledger_entries le WHERE le.ledger_code IN ('2010','2020') GROUP BY le.member_id) g ON s.member_id = g.member_id LEFT JOIN members m ON s.member_id = m.member_id) c),

  'member_share_transactions_for_STR', (SELECT JSON_ARRAYAGG(JSON_OBJECT('transfer_id', transfer_id,'source_member_id',source_member_id,'destination_member_id',destination_member_id,'reference_number',reference_number,'status',status,'transfer_date',transfer_date,'amount',amount)) FROM member_share_transfers WHERE reference_number IN ('STR-3-4-1780077242','STR-3-4-1780077070')),

  'audit_logs_for_STR', (SELECT JSON_ARRAYAGG(JSON_OBJECT('log_id', log_id,'user_id',user_id,'action',action,'entity_type',entity_type,'entity_id',entity_id,'old_values',old_values,'new_values',new_values,'timestamp',timestamp)) FROM audit_logs WHERE old_values LIKE '%STR-3-4-1780077242%' OR new_values LIKE '%STR-3-4-1780077242%' OR action LIKE '%STR-3-4-1780077242%' OR old_values LIKE '%STR-3-4-1780077070%' OR new_values LIKE '%STR-3-4-1780077070%' OR action LIKE '%STR-3-4-1780077070%'),

  'ledger_entries_for_STR', (SELECT JSON_ARRAYAGG(JSON_OBJECT('entry_id', entry_id,'entry_date',entry_date,'ledger_code',ledger_code,'ledger_name',ledger_name,'debit',debit,'credit',credit,'description',description,'transaction_reference',transaction_reference,'receipt_number',receipt_number,'status',status,'member_id',member_id)) FROM ledger_entries WHERE transaction_reference IN ('STR-3-4-1780077242','STR-3-4-1780077070')),

  'gl_summary_2010_2020', (SELECT JSON_ARRAYAGG(JSON_OBJECT('ledger_code',ledger_code,'ledger_name',ledger_name,'total_debit',total_debit,'total_credit',total_credit,'gl_balance',gl_balance)) FROM (SELECT ledger_code, ledger_name, SUM(debit) AS total_debit, SUM(credit) AS total_credit, SUM(debit)-SUM(credit) AS gl_balance FROM ledger_entries WHERE ledger_code IN ('2010','2020') GROUP BY ledger_code, ledger_name) s),

  'migration_indicators', (SELECT JSON_ARRAYAGG(JSON_OBJECT('entry_id',entry_id,'entry_date',entry_date,'description',description)) FROM (SELECT entry_id, entry_date, description FROM ledger_entries WHERE LOWER(COALESCE(description,'')) REGEXP 'migrat|import|legacy|opening balance|brought forward' ORDER BY entry_date DESC LIMIT 500) mi)
) AS result;
