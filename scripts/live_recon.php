<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = getDB();
header('Content-Type: application/json');
$results = [];
$errors = [];

function columnExists($db, $table, $column) {
    $stmt = $db->prepare("SELECT COUNT(*) AS c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$table, $column]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return intval($r['c']) > 0;
}

try {
    // TASK2: ledger account inventory (use COALESCE to avoid null sums)
    $sql = "SELECT ledger_code, ledger_name, COUNT(*) AS entries, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM ledger_entries GROUP BY ledger_code, ledger_name ORDER BY ledger_code";
    $results['task2'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // TASK3: status counts
    $sql = "SELECT status, COUNT(*) AS cnt FROM ledger_entries GROUP BY status";
    $results['task3'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // TASK6 totals for ledger_entries
    $sql = "SELECT COUNT(*) AS entries, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM ledger_entries";
    $results['ledger_totals'] = $db->query($sql)->fetch(PDO::FETCH_ASSOC);

    // JOURNAL totals: only if debit/credit columns exist on journal_entries
    if (columnExists($db, 'journal_entries', 'debit') && columnExists($db, 'journal_entries', 'credit')) {
        $sql = "SELECT COUNT(*) AS entries, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM journal_entries";
        $results['journal_totals'] = $db->query($sql)->fetch(PDO::FETCH_ASSOC);
    } else {
        $results['journal_totals'] = null;
        $errors[] = "journal_entries table missing debit/credit columns; skipped journal totals.";
    }

    // DESCRIBE member_share_transactions
    $stmt = $db->query("SHOW COLUMNS FROM member_share_transactions");
    $memberShareCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results['member_share_columns'] = $memberShareCols;

    // Build column-aware names for member_share_transactions
    $mfields = array_map(function($c){ return $c['Field']; }, $memberShareCols);
    $mst_id = in_array('transaction_id', $mfields) ? 'transaction_id' : (in_array('id', $mfields) ? 'id' : $mfields[0]);
    $mst_ref = in_array('transaction_reference', $mfields) ? 'transaction_reference' : (in_array('reference_number', $mfields) ? 'reference_number' : (in_array('reference', $mfields) ? 'reference' : null));
    $mst_date = in_array('transaction_date', $mfields) ? 'transaction_date' : (in_array('created_at', $mfields) ? 'created_at' : null);
    $mst_type = in_array('transaction_type', $mfields) ? 'transaction_type' : null;
    $mst_amount = in_array('amount', $mfields) ? 'amount' : null;

    if ($mst_ref === null) {
        $errors[] = 'Could not detect reference column for member_share_transactions; using fallback match on amount+date may be unreliable.';
        // Fallback to selecting without ledger join
        $sql = "SELECT *, NULL as ledger_id, NULL as ledger_code, 0 AS debit, 0 AS credit, NULL as status FROM member_share_transactions ORDER BY " . ($mst_date ?: $mst_id) . " DESC";
        $results['share_matches'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // SHARE TRANSACTIONS matching ledger_entries using detected columns
        $sql = sprintf(
            "SELECT mst.%s AS transaction_id, mst.%s AS transaction_reference, mst.%s AS transaction_date, mst.%s AS transaction_type, mst.%s AS amount, le.transaction_reference AS ledger_reference, le.ledger_code, COALESCE(le.debit,0) AS debit, COALESCE(le.credit,0) AS credit, le.status FROM member_share_transactions mst LEFT JOIN ledger_entries le ON le.transaction_reference = mst.%s OR le.receipt_number = mst.%s ORDER BY mst.%s DESC",
            $mst_id, $mst_ref, $mst_date ?: $mst_id, $mst_type ?: 'NULL', $mst_amount ?: 'NULL', $mst_ref, $mst_ref, $mst_date ?: $mst_id
        );
        $results['share_matches'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // DESCRIBE savings_transactions
    $stmt = $db->query("SHOW COLUMNS FROM savings_transactions");
    $savingsCols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $results['savings_columns'] = $savingsCols;

    // Build column-aware names for savings_transactions
    $sfields = array_map(function($c){ return $c['Field']; }, $savingsCols);
    $st_id = in_array('id', $sfields) ? 'id' : (in_array('transaction_id', $sfields) ? 'transaction_id' : $sfields[0]);
    $st_ref = in_array('transaction_reference', $sfields) ? 'transaction_reference' : (in_array('reference_number', $sfields) ? 'reference_number' : (in_array('reference_no', $sfields) ? 'reference_no' : (in_array('receipt_no', $sfields) ? 'receipt_no' : (in_array('reference', $sfields) ? 'reference' : null))));
    $st_date = in_array('transaction_date', $sfields) ? 'transaction_date' : (in_array('created_at', $sfields) ? 'created_at' : null);
    $st_type = in_array('transaction_type', $sfields) ? 'transaction_type' : null;
    $st_amount = in_array('amount', $sfields) ? 'amount' : null;


    if ($st_ref === null) {
        $errors[] = 'Could not detect reference column for savings_transactions; listing raw transactions without ledger match.';
        $sql = "SELECT * FROM savings_transactions ORDER BY " . ($st_date ?: $st_id) . " DESC LIMIT 1000";
        $results['savings_matches_90_days'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        $results['savings_90_day_totals_by_type'] = null;
    } else {
        $sql = sprintf(
            "SELECT st.%s AS id, st.%s AS transaction_reference, st.%s AS transaction_type, st.%s AS amount, st.%s AS transaction_date, le.transaction_reference AS ledger_reference, le.ledger_code, COALESCE(le.debit,0) AS debit, COALESCE(le.credit,0) AS credit, le.status FROM savings_transactions st LEFT JOIN ledger_entries le ON le.transaction_reference = st.%s OR le.receipt_number = st.%s WHERE st.%s >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) ORDER BY st.%s DESC",
            $st_id, $st_ref, $st_type ?: 'NULL', $st_amount ?: 'NULL', $st_date ?: $st_id, $st_ref, $st_ref, $st_date ?: $st_id, $st_date ?: $st_id
        );
        $results['savings_matches_90_days'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Totals of savings transactions (deposits/withdrawals/transfers) in last 90 days
    // Totals of savings transactions (deposits/withdrawals/transfers) in last 90 days
    if ($st_ref !== null) {
        $sql = "SELECT st.transaction_type, COUNT(*) AS cnt, COALESCE(SUM(st.amount),0) AS total_amount, COALESCE(SUM(CASE WHEN (le.transaction_reference IS NOT NULL AND TRIM(le.transaction_reference)!='') THEN st.amount ELSE 0 END),0) AS total_amount_posted_to_gl FROM savings_transactions st LEFT JOIN ledger_entries le ON le.transaction_reference = st." . $st_ref . " OR le.receipt_number = st." . $st_ref . " WHERE st." . ($st_date ?: $st_id) . " >= DATE_SUB(CURDATE(), INTERVAL 90 DAY) GROUP BY st.transaction_type";
        $results['savings_90_day_totals_by_type'] = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // Control account mapping: extract LedgerService constants via file parse
    $ls = file_get_contents(__DIR__ . '/../app/Services/LedgerService.php');
    $mapping = [];
    if (preg_match_all('/const\s+(COA_[A-Z0-9_]+)\s*=\s*\'([^\']+)\'/m', $ls, $m)) {
        for ($i=0;$i<count($m[1]);$i++) {
            $mapping[$m[1][$i]] = $m[2][$i];
        }
    }
    $results['ledger_service_coa_constants'] = $mapping;

    // ----- TASK 1: COA mappings -----
    $stmt = $db->prepare("SELECT * FROM chart_of_accounts WHERE account_code IN ('2010','2020','3010','4010')");
    $stmt->execute();
    $results['task1_coa'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("SELECT DISTINCT ledger_code, ledger_name FROM ledger_entries WHERE ledger_code IN ('2010','2020','3010','4010')");
    $stmt->execute();
    $results['task1_ledger_names'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Determine issues: map ledger names per code
    $coa_map = [];
    foreach ($results['task1_coa'] as $row) {
        $coa_map[$row['account_code']] = $row['account_name'];
    }
    $ledger_names_by_code = [];
    foreach ($results['task1_ledger_names'] as $r) {
        $ledger_names_by_code[$r['ledger_code']][] = $r['ledger_name'];
    }
    $coa_issues = [];
    foreach (['2010','2020','3010','4010'] as $code) {
        $coa_name = isset($coa_map[$code]) ? $coa_map[$code] : null;
        $ledger_names = isset($ledger_names_by_code[$code]) ? $ledger_names_by_code[$code] : [];
        $issue = false;
        if ($coa_name === null) $issue = true;
        if (count($ledger_names) > 1) $issue = true;
        if ($coa_name !== null && count($ledger_names)==1 && $ledger_names[0] !== $coa_name) $issue = true;
        $coa_issues[] = ['account_code'=>$code,'coa_name'=>$coa_name,'ledger_names_found'=>$ledger_names,'issue'=>$issue];
    }
    $results['task1_issues'] = $coa_issues;

    // ----- TASK 2: unmatched savings transactions (all time) -----
    // Match on receipt_no OR reference_no against ledger_entries.transaction_reference or ledger_entries.receipt_number
    $sql_unmatched_savings = "SELECT st.trans_id AS transaction_id, st.account_id AS member_id, st.transaction_date, st.transaction_type, st.amount, st.receipt_no, st.reference_no
        FROM savings_transactions st
        WHERE NOT EXISTS (
            SELECT 1 FROM ledger_entries le WHERE (
                (le.transaction_reference IS NOT NULL AND le.transaction_reference != '' AND (le.transaction_reference = st.receipt_no OR le.transaction_reference = st.reference_no))
                OR (le.receipt_number IS NOT NULL AND le.receipt_number != '' AND (le.receipt_number = st.receipt_no OR le.receipt_number = st.reference_no))
            )
        )
        ORDER BY st.transaction_date DESC";
    $results['unmatched_savings'] = $db->query($sql_unmatched_savings)->fetchAll(PDO::FETCH_ASSOC);

    // Totals by type for unmatched savings
    $sql_unmatched_savings_totals = "SELECT st.transaction_type, COUNT(*) AS cnt, COALESCE(SUM(st.amount),0) AS total_amount FROM savings_transactions st WHERE NOT EXISTS (SELECT 1 FROM ledger_entries le WHERE ((le.transaction_reference IS NOT NULL AND le.transaction_reference != '' AND (le.transaction_reference = st.receipt_no OR le.transaction_reference = st.reference_no)) OR (le.receipt_number IS NOT NULL AND le.receipt_number != '' AND (le.receipt_number = st.receipt_no OR le.receipt_number = st.reference_no)))) GROUP BY st.transaction_type";
    $results['unmatched_savings_totals_by_type'] = $db->query($sql_unmatched_savings_totals)->fetchAll(PDO::FETCH_ASSOC);

    // ----- TASK 3: unmatched share transactions -----
    $sql_unmatched_shares = "SELECT mst.transaction_id, mst.member_id, mst.transaction_date, mst.transaction_type, mst.amount, mst.reference_number FROM member_share_transactions mst WHERE NOT EXISTS (SELECT 1 FROM ledger_entries le WHERE (le.transaction_reference = mst.reference_number OR le.receipt_number = mst.reference_number)) ORDER BY mst.transaction_date DESC";
    $results['unmatched_shares'] = $db->query($sql_unmatched_shares)->fetchAll(PDO::FETCH_ASSOC);
    $sql_unmatched_shares_totals = "SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total_amount FROM member_share_transactions mst WHERE NOT EXISTS (SELECT 1 FROM ledger_entries le WHERE (le.transaction_reference = mst.reference_number OR le.receipt_number = mst.reference_number))";
    $results['unmatched_shares_totals'] = $db->query($sql_unmatched_shares_totals)->fetch(PDO::FETCH_ASSOC);

    // ----- TASK 4: simulate backfill journals (savings unmatched) -----
    // Simulate debit/credit account assignments
    $sql_sim_journals = "SELECT st.trans_id AS transaction_id, COALESCE(NULLIF(st.receipt_no,''), NULLIF(st.reference_no,'')) AS reference, st.transaction_date AS date, st.transaction_type, st.amount,
        CASE
            WHEN st.transaction_type = 'deposit' THEN (CASE WHEN st.payment_method = 'bank_transfer' THEN '1020' ELSE '1010' END)
            WHEN st.transaction_type = 'withdrawal' THEN '2020'
            WHEN st.transaction_type = 'transfer_out' THEN '2020'
            ELSE '1010'
        END AS debit_account_code,
        CASE
            WHEN st.transaction_type = 'deposit' THEN '2020'
            WHEN st.transaction_type = 'withdrawal' THEN (CASE WHEN st.payment_method = 'bank_transfer' THEN '1020' ELSE '1010' END)
            WHEN st.transaction_type = 'transfer_out' THEN '2010'
            ELSE '2020'
        END AS credit_account_code
    FROM savings_transactions st
    WHERE NOT EXISTS (SELECT 1 FROM ledger_entries le WHERE ((le.transaction_reference IS NOT NULL AND le.transaction_reference != '' AND (le.transaction_reference = st.receipt_no OR le.transaction_reference = st.reference_no)) OR (le.receipt_number IS NOT NULL AND le.receipt_number != '' AND (le.receipt_number = st.receipt_no OR le.receipt_number = st.reference_no))))";
    $results['simulated_journals'] = $db->query($sql_sim_journals)->fetchAll(PDO::FETCH_ASSOC);

    // ----- TASK 5: control account reconciliation -----
    $results['control_subledger_savings'] = $db->query("SELECT COALESCE(SUM(balance),0) AS total_balance FROM savings_accounts")->fetch(PDO::FETCH_ASSOC);
    $results['control_subledger_shares'] = $db->query("SELECT COALESCE(SUM(total_invested),0) AS total_invested FROM member_share_holdings")->fetch(PDO::FETCH_ASSOC);
    $results['control_gl_accounts'] = $db->query("SELECT ledger_code, COALESCE(SUM(debit-credit),0) AS balance, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM ledger_entries WHERE ledger_code IN ('2010','2020') GROUP BY ledger_code")->fetchAll(PDO::FETCH_ASSOC);

    // ----- TASK 6: projected impact after simulated backfill -----
    // Compute per-account delta from simulated journals
    $sim = $db->query($sql_sim_journals)->fetchAll(PDO::FETCH_ASSOC);
    $deltas = [];
    foreach ($sim as $row) {
        $amt = floatval($row['amount']);
        $debit = $row['debit_account_code'];
        $credit = $row['credit_account_code'];
        if (!isset($deltas[$debit])) $deltas[$debit]=0;
        if (!isset($deltas[$credit])) $deltas[$credit]=0;
        $deltas[$debit] += $amt; // debit increases
        $deltas[$credit] -= $amt; // credit decreases (or increases credit)
    }
    $results['simulated_deltas'] = $deltas;

    // Projected GL balances for 2010 and 2020
    $gl_bal = [];
    foreach ($results['control_gl_accounts'] as $g) $gl_bal[$g['ledger_code']] = floatval($g['balance']);
    $proj = [];
    foreach (['2010','2020'] as $code) {
        $adj = isset($deltas[$code]) ? $deltas[$code] : 0;
        $current = isset($gl_bal[$code]) ? $gl_bal[$code] : 0;
        $proj[$code] = ['current'=>$current, 'adjustment'=>$adj, 'projected'=>$current + $adj];
    }
    $results['projected_gl_impact'] = $proj;

    // ----- TASK 7: duplicate detection -----
    $results['dup_ledger_by_receipt'] = $db->query("SELECT COALESCE(transaction_reference, receipt_number) AS reference, COUNT(*) AS cnt FROM ledger_entries GROUP BY COALESCE(transaction_reference, receipt_number) HAVING reference IS NOT NULL AND reference<>'' AND COUNT(*)>1 ORDER BY cnt DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $results['dup_ledger_by_ref'] = $db->query("SELECT transaction_reference AS reference, COUNT(*) AS cnt FROM ledger_entries WHERE transaction_reference IS NOT NULL AND transaction_reference<>'' GROUP BY transaction_reference HAVING COUNT(*)>1 ORDER BY cnt DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $results['dup_share_refs'] = $db->query("SELECT reference_number, COUNT(*) AS cnt FROM member_share_transactions GROUP BY reference_number HAVING COUNT(*)>1 ORDER BY cnt DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);

    // ----- TASK 8: readiness heuristic -----
    $readiness = ['safe'=>true,'reasons'=>[]];
    if (count($results['dup_ledger_by_receipt'])>0 || count($results['dup_ledger_by_ref'])>0) {
        $readiness['safe']=false;
        $readiness['reasons'][]='Duplicate ledger references detected; require cleanup before backfill.';
    }
    if (count($results['unmatched_savings'])>0) {
        $readiness['reasons'][]=count($results['unmatched_savings']).' unmatched savings transactions found.';
    }
    $results['backfill_readiness'] = $readiness;

    // ----- TASK 9: execution plan (summary strings) -----
    $results['execution_plan'] = [
        'dry_run_spec' => 'Use simulated_journals to validate debits==credits and run on staging. Do NOT insert until validated.',
        'production_spec' => 'Backfill script will iterate unmatched savings, call LedgerService::postJournalEntries() with original dates and receipt_no as transaction_reference, record mapping table for audit.',
        'rollback' => 'Backfill will insert ledger_entries with a backfill marker; rollback = delete where description LIKE "Backfill:%" and posted_by = <script_user>',
        'validation_checklist' => [
            'All simulated journals balance (sum debits == sum credits)',
            'Projected GL balances match expected sums',
            'No duplicate references produced'
        ],
        'post_backfill' => [
            'Re-run reconciliation totals',
            'Run member account statements spot-checks',
            'Confirm Trial Balance reflects updated control accounts'
        ]
    ];

    // ----- DUPLICATE ANALYSIS & EXPORTS (TASKS 1-9) -----
    // Ensure output directory exists
    $outDir = __DIR__ . DIRECTORY_SEPARATOR . 'output';
    if (!is_dir($outDir)) @mkdir($outDir, 0755, true);

    // TASK 1: enumerate duplicate references (transaction_reference)
    $sql_dup_tr = "SELECT transaction_reference AS reference, COUNT(*) AS duplicate_count, MIN(entry_date) AS first_entry, MAX(entry_date) AS last_entry, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM ledger_entries WHERE transaction_reference IS NOT NULL AND transaction_reference<>'' GROUP BY transaction_reference HAVING COUNT(*)>1 ORDER BY duplicate_count DESC";
    $dup_tr = $db->query($sql_dup_tr)->fetchAll(PDO::FETCH_ASSOC);
    $results['duplicate_inventory_transaction_reference'] = $dup_tr;

    // TASK 1b: enumerate duplicate references (receipt_number)
    $sql_dup_rc = "SELECT receipt_number AS reference, COUNT(*) AS duplicate_count, MIN(entry_date) AS first_entry, MAX(entry_date) AS last_entry, COALESCE(SUM(debit),0) AS total_debit, COALESCE(SUM(credit),0) AS total_credit FROM ledger_entries WHERE receipt_number IS NOT NULL AND receipt_number<>'' GROUP BY receipt_number HAVING COUNT(*)>1 ORDER BY duplicate_count DESC";
    $dup_rc = $db->query($sql_dup_rc)->fetchAll(PDO::FETCH_ASSOC);
    $results['duplicate_inventory_receipt_number'] = $dup_rc;

    // TASK 2: forensic analysis for selected top duplicates
    $top_refs = ['RP20260617185109210','RP20260617185239943','SHR-3-1780076050','STR-3-4-1780078429'];
    $forensic = [];
    foreach ($top_refs as $ref) {
        $stmt = $db->prepare("SELECT COALESCE(id, entry_id, ledger_id, NULL) AS ledger_entry_id, entry_date, ledger_code, ledger_name, debit, credit, description, status, posted_by FROM ledger_entries WHERE transaction_reference = :r OR receipt_number = :r ORDER BY entry_date");
        $stmt->execute([':r'=>$ref]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sum_debit = 0; $sum_credit = 0; $count = count($rows);
        foreach ($rows as $r) { $sum_debit += floatval($r['debit']); $sum_credit += floatval($r['credit']); }
        // classify
        $classification = 'Unknown'; $confidence = 60;
        if ($count>=2 && abs($sum_debit - $sum_credit) < 0.001) { $classification='Legitimate double-entry pair'; $confidence=95; }
        if ($count>2 && abs($sum_debit - $sum_credit) < 0.001) { $classification='Possible duplicate postings'; $confidence=85; }
        if (abs($sum_debit - $sum_credit) > 0.01) { $classification='Partial / unbalanced posting'; $confidence=80; }
        if ($count==0) { $classification='No ledger entries found'; $confidence=90; }
        $forensic[$ref] = ['rows'=>$rows,'count'=>$count,'sum_debit'=>$sum_debit,'sum_credit'=>$sum_credit,'classification'=>$classification,'confidence'=>$confidence];
    }
    $results['forensic_top_duplicates'] = $forensic;

    // TASK 3: expected vs actual lines for duplicate references (sample from dup_tr)
    $dup_groups = [];
    $sample_refs = array_slice(array_column($dup_tr,'reference'),0,200);
    foreach ($sample_refs as $ref) {
        $stmt = $db->prepare("SELECT transaction_type, COUNT(*) AS cnt FROM ledger_entries le LEFT JOIN journal_entries je ON le.journal_id = je.id WHERE le.transaction_reference = :r GROUP BY transaction_type");
        $stmt->execute([':r'=>$ref]);
        $grp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $actual_lines_stmt = $db->prepare("SELECT COUNT(*) AS actual_lines FROM ledger_entries WHERE transaction_reference = :r");
        $actual_lines_stmt->execute([':r'=>$ref]);
        $actual = $actual_lines_stmt->fetch(PDO::FETCH_ASSOC)['actual_lines'];
        // heuristic expected
        $expected = 2; $txType='unknown';
        if (strpos($ref,'RP')===0) { $expected = 2; $txType='loan_repayment'; }
        if (strpos($ref,'DEP')===0 || strpos($ref,'WTH')===0) { $expected = 2; $txType='savings'; }
        if (strpos($ref,'SHR')===0) { $expected = 2; $txType='share_purchase'; }
        if (strpos($ref,'STR')===0) { $expected = 4; $txType='transfer'; }
        $status = ($actual > $expected) ? 'Actual > Expected' : 'OK';
        $dup_groups[] = ['reference'=>$ref,'transaction_type'=>$txType,'expected_lines'=>$expected,'actual_lines'=>intval($actual),'status'=>$status];
    }
    $results['duplicate_groups_sample'] = $dup_groups;

    // TASK 4: trace origin (quick code grep for common prefixes)
    $prefixes = ['RP','SHR','STR','DEP','WTH'];
    $trace = [];
    foreach ($prefixes as $p) {
        $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM ledger_entries WHERE transaction_reference LIKE :p OR receipt_number LIKE :p");
        $stmt->execute([':p'=>$p.'%']);
        $cnt = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        $trace[$p] = ['ledger_count'=>$cnt];
    }
    $results['reference_prefix_trace'] = $trace;

    // TASK 5: detect backfill collision risk for unmatched savings
    $csv_unmatched = [];
    foreach ($results['unmatched_savings'] as $u) {
        $ref = $u['receipt_no'] ?: $u['reference_no'];
        $existsStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM ledger_entries WHERE transaction_reference = :r OR receipt_number = :r");
        $existsStmt->execute([':r'=>$ref]);
        $exists = intval($existsStmt->fetch(PDO::FETCH_ASSOC)['cnt']);
        $would_duplicate = ($exists>0) ? 'Y' : 'N';
        $csv_unmatched[] = array(
            $u['transaction_id'],$u['member_id'],$u['transaction_date'],$u['transaction_type'],$u['amount'],$u['receipt_no'],$u['reference_no'],($exists? 'MATCHED':'') ,($would_duplicate=='Y'?'N':'Y')
        );
    }
    // write unmatched_savings.csv
    $fh = fopen($outDir.DIRECTORY_SEPARATOR.'unmatched_savings.csv','w');
    fputcsv($fh, ['transaction_id','member_id','transaction_date','transaction_type','amount','receipt_no','reference_no','matched_ledger_entry','backfill_safe']);
    foreach ($csv_unmatched as $r) fputcsv($fh,$r);
    fclose($fh);

    // TASK 5b: unmatched shares
    $csv_unmatched_shares = [];
    foreach ($results['unmatched_shares'] as $u) {
        $ref = $u['reference_number'];
        $existsStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM ledger_entries WHERE transaction_reference = :r OR receipt_number = :r");
        $existsStmt->execute([':r'=>$ref]);
        $exists = intval($existsStmt->fetch(PDO::FETCH_ASSOC)['cnt']);
        $would_duplicate = ($exists>0) ? 'Y' : 'N';
        $csv_unmatched_shares[] = [$u['transaction_id'],$u['member_id'],$u['transaction_date'],$u['transaction_type'],$u['amount'],$u['reference_number'],($exists? 'MATCHED':''),($would_duplicate=='Y'?'N':'Y')];
    }
    $fh = fopen($outDir.DIRECTORY_SEPARATOR.'unmatched_shares.csv','w');
    fputcsv($fh, ['transaction_id','member_id','transaction_date','transaction_type','amount','reference_number','matched_ledger_entry','backfill_safe']);
    foreach ($csv_unmatched_shares as $r) fputcsv($fh,$r);
    fclose($fh);

    // TASK 9: simulated_journals.csv
    $fh = fopen($outDir.DIRECTORY_SEPARATOR.'simulated_journals.csv','w');
    fputcsv($fh, ['source_transaction_id','source_type','transaction_date','reference','debit_account','credit_account','amount','status']);
    $blocked = 0; $safe = 0; $review = 0;
    foreach ($results['simulated_journals'] as $sj) {
        $ref = $sj['reference'];
        // check if ledger already has this ref
        $existsStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM ledger_entries WHERE transaction_reference = :r OR receipt_number = :r");
        $existsStmt->execute([':r'=>$ref]);
        $exists = intval($existsStmt->fetch(PDO::FETCH_ASSOC)['cnt']);
        if ($exists>0) { $status='BLOCKED_DUPLICATE'; $blocked++; }
        else { $status='SAFE'; $safe++; }
        fputcsv($fh, [$sj['transaction_id'],'savings',$sj['date'],$ref,$sj['debit_account_code'],$sj['credit_account_code'],$sj['amount'],$status]);
    }
    fclose($fh);

    // TASK 1 export duplicate inventory
    $fh = fopen($outDir.DIRECTORY_SEPARATOR.'duplicate_references.csv','w');
    fputcsv($fh, ['reference','duplicate_count','first_entry','last_entry','debit_total','credit_total','classification','recommended_action']);
    foreach ($dup_tr as $d) {
        // simple classification
        $classification = ($d['total_debit'] == $d['total_credit']) ? 'Balanced duplicates' : 'Unbalanced duplicates';
        $action = ($classification=='Balanced duplicates') ? 'No action (verify)' : 'Investigate / correct';
        fputcsv($fh, [$d['reference'],$d['duplicate_count'],$d['first_entry'],$d['last_entry'],$d['total_debit'],$d['total_credit'],$classification,$action]);
    }
    // also append receipt_number duplicates
    foreach ($dup_rc as $d) {
        $classification = ($d['total_debit'] == $d['total_credit']) ? 'Balanced duplicates' : 'Unbalanced duplicates';
        $action = ($classification=='Balanced duplicates') ? 'No action (verify)' : 'Investigate / correct';
        fputcsv($fh, [$d['reference'],$d['duplicate_count'],$d['first_entry'],$d['last_entry'],$d['total_debit'],$d['total_credit'],$classification,$action]);
    }
    fclose($fh);

    // Summary counts
    $results['export_summary'] = [
        'total_unmatched_savings'=>count($results['unmatched_savings']),
        'total_unmatched_shares'=>count($results['unmatched_shares']),
        'total_simulated_journals'=>count($results['simulated_journals']),
        'total_blocked_journals'=>$blocked,
        'total_safe_journals'=>$safe
    ];
} catch (Exception $e) {
    $errors[] = 'Exception: ' . $e->getMessage();
}

$output = ['results' => $results, 'errors' => $errors];
echo json_encode($output, JSON_PRETTY_PRINT);
