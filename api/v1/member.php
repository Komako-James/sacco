<?php
/**
 * API Router for Member Endpoints
 * Handles: GET/POST for member profile, savings, loans, transactions, etc.
 */

require_once '../../config/db_connection.php';
require_once '../../config/constants.php';
require_once '../../member/auth-middleware.php';
require_once '../../includes/functions.php';

header('Content-Type: application/json');

// Verify member session
if (!isset($_SESSION['session_token'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$parts = explode('/', trim($path, '/'));

// Extract endpoint (e.g., 'member' from /api/v1/member/profile)
$endpoint = $parts[count($parts) - 2] ?? '';
$action = $parts[count($parts) - 1] ?? '';

$db = getDB();
$response = ['success' => false, 'message' => 'Endpoint not found'];

try {
    switch (true) {
        // GET /api/v1/member/profile
        case ($method === 'GET' && $endpoint === 'member' && $action === 'profile'):
            $member = getMemberData();
            $response = [
                'success' => true,
                'data' => $member
            ];
            break;

        // GET /api/v1/member/savings
        case ($method === 'GET' && $endpoint === 'member' && $action === 'savings'):
            $stmt = $db->prepare("
                SELECT * FROM savings_accounts
                WHERE member_id = ?
            ");
            $stmt->execute([$_SESSION['member_id']]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $accounts
            ];
            break;

        // GET /api/v1/member/loans
        case ($method === 'GET' && $endpoint === 'member' && $action === 'loans'):
            $stmt = $db->prepare("
                SELECT l.*, l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount
                FROM loans l
                WHERE l.member_id = ?
            ");
            $stmt->execute([$_SESSION['member_id']]);
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $loans
            ];
            break;

        // GET /api/v1/member/shares
        case ($method === 'GET' && $endpoint === 'member' && $action === 'shares'):
            $stmt = $db->prepare("SELECT * FROM member_share_holdings WHERE member_id = ?");
            $stmt->execute([$_SESSION['member_id']]);
            $holdings = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$holdings) {
                $holdings = [
                    'shares_owned' => 0,
                    'share_price' => SHARE_PRICE,
                    'total_invested' => 0
                ];
            }

            $stmt = $db->prepare("SELECT * FROM member_share_transactions WHERE member_id = ? ORDER BY transaction_date DESC LIMIT 20");
            $stmt->execute([$_SESSION['member_id']]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => [
                    'holdings' => $holdings,
                    'transactions' => $transactions
                ]
            ];
            break;

        // POST /api/v1/member/shares/purchase
        case ($method === 'POST' && $endpoint === 'shares' && $action === 'purchase'):
            $input = json_decode(file_get_contents('php://input'), true);
            $accountId = isset($input['savings_account_id']) ? (int) $input['savings_account_id'] : 0;
            $shares = isset($input['shares']) ? (int) $input['shares'] : 0;

            if ($accountId <= 0 || $shares <= 0) {
                throw new Exception('savings_account_id and shares are required');
            }

            $amount = $shares * SHARE_PRICE;
            $reference = 'SHR-' . $_SESSION['member_id'] . '-' . time();

            // Begin a transaction to make the withdrawal and share updates atomic
            $db->beginTransaction();
            // Use SavingsService to perform withdrawal and post ledger entries (transaction-aware)
            require_once __DIR__ . '/../../app/Services/SavingsService.php';
            $savingsService = new \SACCO\Services\SavingsService();
            $withdrawRes = $savingsService->withdraw($_SESSION['member_id'], $accountId, $amount, 'internal', $_SESSION['member_user_id'] ?? 0, $reference);

            if (empty($withdrawRes['success'])) {
                // rollback outer transaction if withdraw failed
                if ($db->inTransaction()) $db->rollBack();
                throw new Exception('Failed to withdraw from savings: ' . ($withdrawRes['message'] ?? 'unknown'));
            }

            $holdingStmt = $db->prepare("SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE");
            $holdingStmt->execute([$_SESSION['member_id']]);
            $holding = $holdingStmt->fetch(PDO::FETCH_ASSOC);

            if ($holding) {
                $shareId = $holding['share_id'];
                $updateHolding = $db->prepare("UPDATE member_share_holdings SET shares_owned = shares_owned + ?, total_invested = total_invested + ?, last_purchase_date = NOW(), updated_at = NOW() WHERE share_id = ?");
                $updateHolding->execute([$shares, $amount, $shareId]);
            } else {
                $insertHolding = $db->prepare("INSERT INTO member_share_holdings (member_id, shares_owned, share_price, total_invested, last_purchase_date) VALUES (?, ?, ?, ?, NOW())");
                $insertHolding->execute([$_SESSION['member_id'], $shares, SHARE_PRICE, $amount]);
                $shareId = $db->lastInsertId();
            }

            $insertShareTxn = $db->prepare("INSERT INTO member_share_transactions (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, transaction_date, created_by, description) VALUES (?, ?, ?, 'purchase', ?, ?, ?, NOW(), ?, 'Share purchase from savings account')");
            $insertShareTxn->execute([$_SESSION['member_id'], $shareId, $accountId, $shares, $amount, $reference, $_SESSION['member_user_id']]);

            $db->commit();
            $response = [
                'success' => true,
                'message' => 'Shares purchased successfully',
                'data' => [
                    'shares' => $shares,
                    'amount' => $amount,
                    'reference' => $reference
                ]
            ];
            break;

        // GET /api/v1/member/transactions
        case ($method === 'GET' && $endpoint === 'member' && $action === 'transactions'):
            $limit = $_GET['limit'] ?? 50;
            $offset = $_GET['offset'] ?? 0;

            $stmt = $db->prepare("
                SELECT st.*, sa.account_type
                FROM savings_transactions st
                JOIN savings_accounts sa ON st.account_id = sa.account_id
                WHERE sa.member_id = ?
                ORDER BY st.transaction_date DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$_SESSION['member_id'], $limit, $offset]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $transactions,
                'limit' => $limit,
                'offset' => $offset
            ];
            break;

        // GET /api/v1/member/statements
        case ($method === 'GET' && $endpoint === 'member' && $action === 'statements'):
            $accountId = $_GET['account_id'] ?? null;
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;

            if (!$accountId) {
                throw new Exception('account_id required');
            }

            $stmt = $db->prepare("
                SELECT st.*, sa.account_type
                FROM savings_transactions st
                JOIN savings_accounts sa ON st.account_id = sa.account_id
                WHERE sa.member_id = ? AND sa.account_id = ?
                AND st.transaction_date BETWEEN ? AND ?
                ORDER BY st.transaction_date DESC
            ");
            $stmt->execute([
                $_SESSION['member_id'],
                $accountId,
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
            $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $transactions
            ];
            break;

        // GET /api/v1/member/repayment-schedule
        case ($method === 'GET' && $endpoint === 'member' && $action === 'repayment-schedule'):
            $stmt = $db->prepare("
                SELECT l.loan_ref_no AS loan_reference, lrs.*
                FROM loans l
                JOIN loan_repayment_schedule lrs ON l.loan_id = lrs.loan_id
                WHERE l.member_id = ? AND l.status IN ('approved', 'disbursed')
                ORDER BY l.loan_id, lrs.due_date
            ");
            $stmt->execute([$_SESSION['member_id']]);
            $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $response = [
                'success' => true,
                'data' => $schedules
            ];
            break;

        // POST /api/v1/member/password/change
        case ($method === 'POST' && $endpoint === 'member' && $action === 'change'):
            $input = json_decode(file_get_contents('php://input'), true);
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword)) {
                throw new Exception('current_password and new_password required');
            }

            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['member_user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }

            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
            $stmt = $db->prepare("
                UPDATE users SET password_hash = ?, password_changed_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$newPasswordHash, $_SESSION['member_user_id']]);

            $response = [
                'success' => true,
                'message' => 'Password changed successfully'
            ];
            break;

        // POST /api/v1/member/profile/update
        case ($method === 'POST' && $endpoint === 'member' && $action === 'update'):
            $input = json_decode(file_get_contents('php://input'), true);
            $phone = $input['phone'] ?? '';
            $email = $input['email'] ?? '';
            $address = $input['address'] ?? '';

            if (empty($phone) || empty($email)) {
                throw new Exception('phone and email required');
            }

            $stmt = $db->prepare("
                UPDATE members SET phone = ?, email = ?, address = ?
                WHERE member_id = ?
            ");
            $stmt->execute([$phone, $email, $address, $_SESSION['member_id']]);

            $response = [
                'success' => true,
                'message' => 'Profile updated successfully'
            ];
            break;

        // POST /api/v1/member/profile/photo
        case ($method === 'POST' && $endpoint === 'member' && $action === 'photo'):
            if (!isset($_FILES['photo'])) {
                throw new Exception('photo file required');
            }

            $file = $_FILES['photo'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            $allowed = ['image/jpeg', 'image/png', 'image/gif'];

            if ($file['size'] > $maxSize) {
                throw new Exception('File too large. Maximum 5MB.');
            }

            if (!in_array($file['type'], $allowed)) {
                throw new Exception('Only JPG, PNG, and GIF files allowed.');
            }

            $uploadDir = '../../uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = 'member_' . $_SESSION['member_id'] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filePath = $uploadDir . $fileName;

            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                throw new Exception('Failed to upload file');
            }

            $stmt = $db->prepare("UPDATE members SET profile_photo = ? WHERE member_id = ?");
            $stmt->execute([$fileName, $_SESSION['member_id']]);

            $response = [
                'success' => true,
                'message' => 'Profile photo updated',
                'photo_url' => '/uploads/profiles/' . $fileName
            ];
            break;

        default:
            http_response_code(404);
            $response = ['success' => false, 'message' => 'Endpoint not found'];
    }
} catch (Exception $e) {
    http_response_code(400);
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);
