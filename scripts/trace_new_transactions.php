<?php
require_once __DIR__ . '/../config/db_connection.php';
header('Content-Type: application/json');
$db = getDB();
$result = [
    'timestamp' => date('c'),
    'errors' => []
];

// helper
function tableExists($db, $table) {
    $stmt = $db->prepare("SHOW TABLES LIKE ?");
    $stmt->execute([$table]);
    return $stmt->fetch() !== false;
}

try {
    // SECTION 1 - newest members
    if (tableExists($db, 'members')) {
        $stmt = $db->prepare("SELECT id, COALESCE(CONCAT(first_name,' ',last_name), username, name) AS member_name, created_at FROM members ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $members = $stmt->fetchAll();
        $result['section_1']['newest_members'] = $members;
        $new_member_ids = array_map(function($m){return $m['id'];}, array_slice($members,0,2));
    } else {
        $result['section_1']['newest_members'] = [];
        $result['errors'][] = 'Table members not found';
        $new_member_ids = [];
    }

    // SECTION 1 - newest share purchases
    if (tableExists($db, 'shares_transactions')) {
        $stmt = $db->prepare("SELECT * FROM shares_transactions ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $result['section_1']['recent_share_transactions'] = $stmt->fetchAll();
    } else {
        $result['section_1']['recent_share_transactions'] = [];
    }

    // SECTION 1 - newest loans
    if (tableExists($db, 'loans')) {
        $stmt = $db->prepare("SELECT * FROM loans ORDER BY created_at DESC LIMIT 20");
        $stmt->execute();
        $result['section_1']['recent_loans'] = $stmt->fetchAll();
    } else {
        $result['section_1']['recent_loans'] = [];
    }

    // For each new member, trace shares and ledger
    $result['section_2'] = [];
    foreach ($new_member_ids as $mid) {
        $mData = ['member_id'=>$mid];
        // share holdings
        if (tableExists($db, 'member_share_holdings')) {
            $stmt = $db->prepare("SELECT * FROM member_share_holdings WHERE member_id = ?");
            $stmt->execute([$mid]);
            $mData['member_share_holdings'] = $stmt->fetchAll();
        } else { $mData['member_share_holdings'] = []; }
        // shares transactions
        if (tableExists($db, 'shares_transactions')) {
            $stmt = $db->prepare("SELECT * FROM shares_transactions WHERE member_id = ? ORDER BY created_at DESC");
            $stmt->execute([$mid]);
            $mData['shares_transactions'] = $stmt->fetchAll();
        } else { $mData['shares_transactions'] = []; }
        // ledger entries
        if (tableExists($db, 'ledger_entries')) {
            $stmt = $db->prepare("SELECT * FROM ledger_entries WHERE member_id = ? ORDER BY created_at DESC");
            $stmt->execute([$mid]);
            $mData['ledger_entries'] = $stmt->fetchAll();
        } else { $mData['ledger_entries'] = []; }
        // journal entries if exists
        if (tableExists($db, 'journal_entries')) {
            $stmt = $db->prepare("SELECT * FROM journal_entries WHERE member_id = ? ORDER BY created_at DESC");
            $stmt->execute([$mid]);
            $mData['journal_entries'] = $stmt->fetchAll();
        } else { $mData['journal_entries'] = []; }
        $result['section_2'][] = $mData;
    }

    // SECTION 3 - loans for newest loans (take newest 5)
    $result['section_3'] = [];
    if (tableExists($db, 'loans')) {
        $stmt = $db->prepare("SELECT id, member_id, principal, status, created_at, disbursed_at FROM loans ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $recentLoans = $stmt->fetchAll();
        foreach ($recentLoans as $loan) {
            $lid = $loan['id'];
            $lData = ['loan'=>$loan];
            // disbursement record
            if (tableExists($db, 'loan_disbursements')) {
                $stmt2 = $db->prepare("SELECT * FROM loan_disbursements WHERE loan_id = ?");
                $stmt2->execute([$lid]);
                $lData['disbursements'] = $stmt2->fetchAll();
            } else {
                // try ledger entries for disbursement
                $lData['disbursements'] = [];
            }
            // journal / ledger entries
            if (tableExists($db, 'ledger_entries')) {
                $stmt3 = $db->prepare("SELECT * FROM ledger_entries WHERE reference LIKE ? OR loan_id = ? ORDER BY created_at DESC");
                $stmt3->execute(["%loan%$lid%", $lid]);
                $lData['ledger_entries'] = $stmt3->fetchAll();
            } else { $lData['ledger_entries'] = []; }
            $result['section_3'][] = $lData;
        }
    }

    // SECTION 4 - member to GL reconciliation for new members
    $result['section_4'] = [];
    foreach ($new_member_ids as $mid) {
        $rec = ['member_id'=>$mid];
        // shares owned
        if (tableExists($db, 'member_share_holdings')) {
            $stmt = $db->prepare("SELECT SUM(quantity * price) AS shares_value, SUM(quantity) as shares_qty FROM member_share_holdings WHERE member_id = ?");
            $stmt->execute([$mid]);
            $rec['shares'] = $stmt->fetch();
        } else { $rec['shares'] = null; }
        // savings balance
        if (tableExists($db, 'savings_accounts')) {
            $stmt = $db->prepare("SELECT SUM(balance) as savings_balance FROM savings_accounts WHERE member_id = ?");
            $stmt->execute([$mid]);
            $rec['savings'] = $stmt->fetch();
        } else { $rec['savings'] = null; }
        // loans outstanding
        if (tableExists($db, 'loans')) {
            $stmt = $db->prepare("SELECT SUM(outstanding_balance) as loans_outstanding FROM loans WHERE member_id = ? AND status!='written_off'");
            $stmt->execute([$mid]);
            $rec['loans'] = $stmt->fetch();
        } else { $rec['loans'] = null; }
        // GL amounts from ledger_entries for that member
        if (tableExists($db, 'ledger_entries')) {
            $stmt = $db->prepare("SELECT ledger_code, SUM(CASE WHEN debit_credit='D' THEN amount ELSE -amount END) as balance FROM ledger_entries WHERE member_id = ? GROUP BY ledger_code");
            $stmt->execute([$mid]);
            $rec['gl_balances'] = $stmt->fetchAll();
        } else { $rec['gl_balances'] = []; }
        $result['section_4'][] = $rec;
    }

    // SECTION 5 - Posting engine execution: try to find which controller/service invoked
    // This requires code tracing; we'll search codebase for patterns mentioning ledger methods and transaction references tied to members/loans
    $result['section_5'] = [];
    // naive search: find files referencing postSharePurchaseFromSavings or postLoanDisbursement
    $searchPatterns = ['postSharePurchaseFromSavings','postSavingsDeposit','postLoanDisbursement','postLoanRepayment','postMonthlyInterest','postSavingsInterest'];
    foreach ($searchPatterns as $pat) {
        $matches = [];
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../'));
        foreach ($it as $file) {
            if ($file->isFile() && in_array($file->getExtension(), ['php'])) {
                $contents = file_get_contents($file->getPathname());
                if (strpos($contents, $pat) !== false) {
                    $matches[] = str_replace('\\','/',$file->getPathname());
                }
            }
        }
        $result['section_5'][$pat] = $matches;
    }

    // SECTION 6 - ledger entries for newly added members (already collected in section_2), but aggregate all
    $result['section_6'] = [];
    foreach ($new_member_ids as $mid) {
        if (tableExists($db, 'ledger_entries')) {
            $stmt = $db->prepare("SELECT id AS entry_id, ledger_code, ledger_name, CASE WHEN debit_credit='D' THEN amount ELSE 0 END AS debit, CASE WHEN debit_credit='C' THEN amount ELSE 0 END AS credit, reference, member_id, created_at FROM ledger_entries WHERE member_id = ? ORDER BY created_at DESC");
            $stmt->execute([$mid]);
            $result['section_6'][$mid] = $stmt->fetchAll();
        } else { $result['section_6'][$mid] = []; }
    }

    // SECTION 7 - COA mapping validation: load chart_of_accounts and compare with LedgerService constants
    $result['section_7'] = [];
    if (tableExists($db, 'chart_of_accounts')) {
        $stmt = $db->prepare("SELECT account_code, account_name FROM chart_of_accounts");
        $stmt->execute();
        $coa = $stmt->fetchAll(PDO::FETCH_UNIQUE);
        $result['section_7']['chart_of_accounts'] = $coa;
    } else { $result['section_7']['chart_of_accounts'] = []; }
    // Parse LedgerService constants
    $lsFile = __DIR__ . '/../app/Services/LedgerService.php';
    if (file_exists($lsFile)) {
        $lsContents = file_get_contents($lsFile);
        preg_match_all('/const\s+(COA_[A-Z0-9_]+)\s*=\s*' . "'" . '([0-9]+)' . "'" . '\s*;/i', $lsContents, $matches, PREG_SET_ORDER);
        $constants = [];
        foreach ($matches as $m) { $constants[$m[1]] = $m[2]; }
        $result['section_7']['ledgerservice_constants'] = $constants;
    } else { $result['section_7']['ledgerservice_constants'] = []; }

    // Cross-compare for ledger entries collected
    $result['section_7']['comparisons'] = [];
    foreach ($result['section_6'] as $mid=>$entries) {
        foreach ($entries as $e) {
            $code = $e['ledger_code'];
            $dbName = isset($result['section_7']['chart_of_accounts'][$code]) ? $result['section_7']['chart_of_accounts'][$code]['account_name'] : null;
            // find constant name by value
            $constName = null;
            foreach ($result['section_7']['ledgerservice_constants'] as $k=>$v) {
                if ($v == $code) { $constName = $k; break; }
            }
            $status = ($dbName && $constName) ? 'MATCH' : 'MISMATCH';
            $result['section_7']['comparisons'][] = ['entry_id'=>$e['entry_id'],'ledger_code'=>$code,'db_account_name'=>$dbName,'ledgerservice_constant'=>$constName,'status'=>$status];
        }
    }

    // SECTION 8 - Root cause quick heuristic
    // If any new member has ledger_entries empty but subledger non-empty -> C
    $heuristic = [];
    foreach ($result['section_4'] as $rec) {
        $mid = $rec['member_id'];
        $sub_total = 0;
        if (!empty($rec['shares']) && isset($rec['shares']['shares_value'])) $sub_total += floatval($rec['shares']['shares_value']);
        if (!empty($rec['savings']) && isset($rec['savings']['savings_balance'])) $sub_total += floatval($rec['savings']['savings_balance']);
        if (!empty($rec['loans']) && isset($rec['loans']['loans_outstanding'])) $sub_total -= floatval($rec['loans']['loans_outstanding']);
        $gl_total = 0;
        if (!empty($rec['gl_balances'])) {
            foreach ($rec['gl_balances'] as $g) $gl_total += floatval($g['balance']);
        }
        $variance = $sub_total - $gl_total;
        $heuristic[$mid] = ['sub_total'=>$sub_total,'gl_total'=>$gl_total,'variance'=>$variance];
    }
    $result['section_8'] = $heuristic;

    echo json_encode($result, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    $result['errors'][] = $e->getMessage();
    echo json_encode($result, JSON_PRETTY_PRINT);
}
