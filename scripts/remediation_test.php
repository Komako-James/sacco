<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/AuditAuthNotificationServices.php';
require_once __DIR__ . '/../app/Services/LoanService.php';
require_once __DIR__ . '/../app/Services/LedgerService.php';

use SACCO\Services\AuditService;
use SACCO\Services\LoanService;

$db = Database::getInstance()->getConnection();
AuditService::setDatabase($db);

$loanService = new LoanService($db);

$result = [
    'apply' => null,
    'approve' => null,
    'disburse' => null,
    'repay' => null,
    'ledger_entries' => []
];

try {
    // 1) Create loan row directly (bypass eligibility checks for test)
    $loanRef = 'LN' . date('YmdHis') . rand(100,999);
    $stmt = $db->prepare("INSERT INTO loans (loan_ref_no, member_id, product_id, amount_requested, interest_rate, repayment_period_months, processing_fee, purpose, application_date, status, applied_by, outstanding_balance) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'applied', ?, ?)");
    $stmt->execute([$loanRef, 1, 3, 10000, 10.00, 12, 0.00, 'Remediation test loan', 1, 10000]);
    $loanId = $db->lastInsertId();
    $result['apply'] = ['success' => true, 'loan_id' => $loanId, 'loan_ref' => $loanRef];

    // 2) Approve loan (direct update to avoid schema differences in approval helper)
    $stmt = $db->prepare("UPDATE loans SET amount_approved = ?, approval_date = NOW(), status = 'approved', approved_by = ? , outstanding_balance = ? WHERE loan_id = ?");
    $stmt->execute([10000, 1, 10000, $loanId]);
    $result['approve'] = ['success' => true, 'loan_id' => $loanId];

    // 3) Disburse loan (direct ledger posting to avoid schedule/schema differences)
    $stmt = $db->prepare("UPDATE loans SET status = 'disbursed', disbursement_date = NOW(), disbursed_by = ? WHERE loan_id = ?");
    $stmt->execute([1, $loanId]);

    try {
        \SACCO\Services\LedgerService::postLoanDisbursement($loanId, 10000, 0, 1);
        $result['disburse'] = ['success' => true];
    } catch (Exception $e) {
        $result['disburse'] = ['success' => false, 'message' => $e->getMessage()];
        echo json_encode($result, JSON_PRETTY_PRINT);
        exit(0);
    }

    // 4) Post a repayment (direct ledger posting to avoid schedule/schema dependencies)
    try {
        \SACCO\Services\LedgerService::postLoanRepayment(0, ['principal_paid' => 1000, 'interest_paid' => 0, 'penalty_paid' => 0], 1);
        $result['repay'] = ['success' => true];
    } catch (Exception $e) {
        $result['repay'] = ['success' => false, 'message' => $e->getMessage()];
    }

    // 5) Query ledger entries for last 2 hours
    $stmt = $db->prepare("SELECT entry_id, ledger_code, ledger_name, debit, credit, receipt_number, transaction_reference, description, member_id, created_at FROM ledger_entries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 2 HOUR) ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result['ledger_entries'] = $rows;

    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    echo json_encode($result, JSON_PRETTY_PRINT);
}

?>