<?php
/**
 * Mobile Money API Handler
 * Handles MTN Mobile Money transactions
 */

require_once '../config/db_connection.php';
require_once '../config/constants.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        switch ($action) {
            case 'request_payment':
                requestMobileMoneyPayment($input);
                break;
            case 'check_status':
                checkPaymentStatus($input);
                break;
            default:
                throw new Exception('Invalid action');
        }
    } elseif ($method === 'GET' && $action === 'callback') {
        handleMobileMoneyCallback();
    } else {
        throw new Exception('Invalid request method');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function requestMobileMoneyPayment($data) {
    // Validate required fields
    $required = ['member_id', 'amount', 'phone', 'transaction_type'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception('Missing required field: ' . $field);
        }
    }
    
    $db = Database::getInstance()->getConnection();
    
    // Store payment request
    $stmt = $db->prepare("
        INSERT INTO mobile_money_transactions 
        (member_id, amount, phone, transaction_type, status, created_at)
        VALUES (?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $data['member_id'],
        $data['amount'],
        $data['phone'],
        $data['transaction_type']
    ]);
    
    $transactionId = $db->lastInsertId();
    
    // Call MTN API (pseudocode - implement actual API call)
    $mtnResponse = callMTNAPI($data['phone'], $data['amount'], $transactionId);
    
    if ($mtnResponse['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Payment request initiated',
            'transaction_id' => $transactionId,
            'reference' => $mtnResponse['reference']
        ]);
    } else {
        throw new Exception('Failed to initiate payment: ' . $mtnResponse['message']);
    }
}

function checkPaymentStatus($data) {
    if (empty($data['transaction_id'])) {
        throw new Exception('Missing transaction_id');
    }
    
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT * FROM mobile_money_transactions WHERE id = ?");
    $stmt->execute([$data['transaction_id']]);
    $transaction = $stmt->fetch();
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    echo json_encode([
        'success' => true,
        'status' => $transaction['status'],
        'amount' => $transaction['amount'],
        'transaction_date' => $transaction['created_at']
    ]);
}

function handleMobileMoneyCallback() {
    // Verify callback is from MTN
    $data = $_GET;
    
    $db = Database::getInstance()->getConnection();
    
    // Update transaction status based on callback
    $status = $data['status'] ?? 'failed';
    $transactionId = $data['transaction_id'] ?? null;
    
    if ($transactionId) {
        $stmt = $db->prepare("
            UPDATE mobile_money_transactions 
            SET status = ? 
            WHERE id = ?
        ");
        $stmt->execute([$status, $transactionId]);
        
        if ($status === 'completed') {
            // Process the transaction (deposit/withdrawal)
            processPaymentTransaction($transactionId);
        }
    }
    
    echo json_encode(['success' => true]);
}

function callMTNAPI($phone, $amount, $transactionId) {
    // Implementation of actual MTN API call
    // This is a placeholder that simulates the API response
    
    // In production, implement actual cURL request to MTN sandbox/production
    // Reference: MTN Mobile Money API documentation
    
    return [
        'success' => true,
        'reference' => 'MTN' . date('YmdHis') . $transactionId,
        'message' => 'Payment request sent'
    ];
}

function processPaymentTransaction($mobileMoneyTransactionId) {
    // Move payment from mobile money to savings account
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM mobile_money_transactions WHERE id = ?");
    $stmt->execute([$mobileMoneyTransactionId]);
    $mtnTrans = $stmt->fetch();
    
    if (!$mtnTrans) return;
    
    // Get member's account
    $stmt = $db->prepare("
        SELECT * FROM savings_accounts 
        WHERE member_id = ? AND status = 'active' LIMIT 1
    ");
    $stmt->execute([$mtnTrans['member_id']]);
    $account = $stmt->fetch();
    
    if ($account) {
        // Delegate to SavingsService to ensure ledger posting and consistent transaction logging
        require_once __DIR__ . '/../app/Services/SavingsService.php';
        $savingsService = new \SACCO\Services\SavingsService();
        $savingsService->deposit((int)$mtnTrans['member_id'], (int)$account['account_id'], (float)$mtnTrans['amount'], 'mobile_money', 0, generateReceiptNumber('MTNM'));
    }
}
?>
