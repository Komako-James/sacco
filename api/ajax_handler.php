<?php
/**
 * AJAX Handler for dynamic requests
 */

require_once '../config/db_connection.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$db = Database::getInstance()->getConnection();

try {
    switch ($action) {
        case 'check_membership':
            checkMembership();
            break;
        case 'get_member_accounts':
            getMemberAccounts();
            break;
        case 'check_loan_eligibility':
            checkLoanEligibility();
            break;
        case 'get_loan_products':
            getLoanProducts();
            break;
        case 'calculate_repayment':
            calculateRepayment();
            break;
        case 'search_member':
            searchMember();
            break;
        case 'get_notifications':
            getNotifications();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function checkMembership() {
    global $db;
    $membershipNo = $_GET['membership_no'] ?? '';
    
    if (empty($membershipNo)) {
        throw new Exception('Membership number required');
    }
    
    $stmt = $db->prepare("SELECT member_id, full_name, status FROM members WHERE membership_no = ?");
    $stmt->execute([$membershipNo]);
    $member = $stmt->fetch();
    
    if ($member) {
        echo json_encode(['success' => true, 'member' => $member]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Member not found']);
    }
}

function getMemberAccounts() {
    global $db;
    $memberId = $_GET['member_id'] ?? '';
    
    if (empty($memberId)) {
        throw new Exception('Member ID required');
    }
    
    $stmt = $db->prepare("
        SELECT account_id, account_number, account_type, balance, status
        FROM savings_accounts
        WHERE member_id = ? AND status = 'active'
    ");
    $stmt->execute([$memberId]);
    $accounts = $stmt->fetchAll();
        if (empty($accounts)) {
        echo json_encode(['success' => false, 'message' => 'No active savings accounts found for this member.']);
        return;
    }
        echo json_encode(['success' => true, 'accounts' => $accounts]);
}

function checkLoanEligibility() {
    global $db;
    $memberId = $_GET['member_id'] ?? '';
    
    if (empty($memberId)) {
        throw new Exception('Member ID required');
    }
    
    $eligibility = checkMemberEligibility($memberId);
    echo json_encode(['success' => true, 'eligibility' => $eligibility]);
}

function getLoanProducts() {
    global $db;
    
    $stmt = $db->prepare("
        SELECT product_id, product_name, min_amount, max_amount, 
               default_interest_rate, min_repayment_months, max_repayment_months
        FROM loan_products
        WHERE status = 'active'
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'products' => $products]);
}

function calculateRepayment() {
    $amount = $_GET['amount'] ?? 0;
    $interestRate = $_GET['interest_rate'] ?? 0;
    $months = $_GET['months'] ?? 0;
    
    if ($amount <= 0 || $interestRate < 0 || $months <= 0) {
        throw new Exception('Invalid parameters');
    }
    
    $schedule = calculateLoanSchedule($amount, $interestRate, $months);
    
    $totalPayment = array_sum(array_column($schedule, 'total'));
    $totalInterest = array_sum(array_column($schedule, 'interest'));
    
    echo json_encode([
        'success' => true,
        'schedule' => $schedule,
        'summary' => [
            'principal' => $amount,
            'total_interest' => round($totalInterest, 2),
            'total_payment' => round($totalPayment, 2),
            'monthly_payment' => round($totalPayment / $months, 2)
        ]
    ]);
}

function searchMember() {
    global $db;
    $query = trim($_GET['q'] ?? '');

    if ($query === '') {
        echo json_encode(['success' => true, 'members' => []]);
        return;
    }

    $stmt = $db->prepare("SELECT member_id, membership_no, full_name, phone, email
        FROM members
        WHERE membership_no LIKE :query
           OR full_name LIKE :query
           OR phone LIKE :query
           OR email LIKE :query
        LIMIT 20");
    $stmt->execute([':query' => "%{$query}%"]);
    $members = $stmt->fetchAll();

    echo json_encode(['success' => true, 'members' => $members]);
}

function getNotifications() {
    global $db;

    // Basic notification count placeholder; extend with real alerts for pending loans, approvals, audits
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM loan_applications WHERE status = 'pending'");
    $stmt->execute();
    $pendingLoans = (int) $stmt->fetchColumn();

    echo json_encode(['success' => true, 'count' => $pendingLoans]);
}
?>
