<?php
require_once __DIR__ . '/../config/db_connection.php';

function generateMembershipNumber() {
    $prefix = 'SAC';
    $year = date('Y');
    $random = str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return $prefix . $year . $random;
}

function generateLoanReference() {
    return 'LN' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateReceiptNumber($prefix) {
    return $prefix . date('YmdHis') . rand(100, 999);
}

function formatMoney($amount) {
    return 'UGX ' . number_format($amount, 2);
}

function calculateLoanSchedule($amount, $interestRate, $periodMonths) {
    $monthlyInterestRate = ($interestRate / 100) / 12;
    $monthlyPayment = ($amount * $monthlyInterestRate * pow(1 + $monthlyInterestRate, $periodMonths)) / 
                     (pow(1 + $monthlyInterestRate, $periodMonths) - 1);
    
    $schedule = [];
    $balance = $amount;
    
    for ($i = 1; $i <= $periodMonths; $i++) {
        $interestPayment = $balance * $monthlyInterestRate;
        $principalPayment = $monthlyPayment - $interestPayment;
        $balance -= $principalPayment;
        
        $schedule[] = [
            'installment' => $i,
            'principal' => round($principalPayment, 2),
            'interest' => round($interestPayment, 2),
            'total' => round($monthlyPayment, 2),
            'balance' => round(max(0, $balance), 2)
        ];
    }
    
    return $schedule;
}

function checkMemberEligibility($memberId) {
    $db = getDB();
    
    // Check if member has saved for at least 3 months
    $stmt = $db->prepare("
        SELECT COUNT(*) as savings_count 
        FROM savings_transactions st
        JOIN savings_accounts sa ON st.account_id = sa.account_id
        WHERE sa.member_id = ? 
        AND st.transaction_type = 'deposit'
        AND st.transaction_date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    ");
    $stmt->execute([$memberId]);
    $result = $stmt->fetch();
    
    if ($result['savings_count'] == 0) {
        return ['eligible' => false, 'reason' => 'Member must save for at least 3 months before applying for a loan'];
    }
    
    // Check for active loans
    $stmt = $db->prepare("
        SELECT COUNT(*) as active_loans 
        FROM loans 
        WHERE member_id = ? AND status NOT IN ('completed', 'rejected')
    ");
    $stmt->execute([$memberId]);
    $result = $stmt->fetch();
    
    if ($result['active_loans'] > 0) {
        return ['eligible' => false, 'reason' => 'Member has an active loan'];
    }
    
    return ['eligible' => true, 'reason' => ''];
}

function getMemberGuarantorExposure($memberId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(amount_guaranteed), 0) as total_exposure
        FROM loan_guarantors lg
        JOIN loans l ON lg.loan_id = l.loan_id
        WHERE lg.guarantor_member_id = ? 
        AND lg.status = 'active'
        AND l.status NOT IN ('completed', 'rejected')
    ");
    $stmt->execute([$memberId]);
    $result = $stmt->fetch();
    return $result['total_exposure'];
}

function getMemberSavingsBalance($memberId) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(balance), 0) as total_savings
        FROM savings_accounts
        WHERE member_id = ? AND status = 'active'
    ");
    $stmt->execute([$memberId]);
    $result = $stmt->fetch();
    return $result['total_savings'];
}

function sendSMS($phone, $message) {
    // Implementation for SMS gateway (Africa's Talking)
    // This is a placeholder - implement actual API call
    $ch = curl_init();
    // ... curl configuration
    return true;
}

function logActivity($userId, $action, $table, $recordId, $oldData = null, $newData = null) {
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    return $stmt->execute([
        $userId, $action, $table, $recordId,
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null,
        $ip, $userAgent
    ]);
}

function generatePDF($html, $filename) {
    // Implementation for PDF generation (using Dompdf)
    // This is a placeholder
    return true;
}
?>
