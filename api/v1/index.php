<?php
/**
 * SACCO API - Main Entry Point
 * Handles routing for all API endpoints
 * Version: 1.0.0
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/**
 * Handle standing orders requests
 */
function handleStandingOrdersRequest($method, $action, $id) {
    global $response;
    $db = Database::getInstance()->getConnection();
    $service = new \SACCO\Services\StandingOrderService();

    // POST /standing-orders -> create
    if ($method === 'POST' && $action === null) {
        $input = json_decode(file_get_contents('php://input'), true);
        $required = ['member_id','amount','frequency','next_run_date'];
        foreach ($required as $r) if (empty($input[$r])) { http_response_code(400); $response['message'] = "$r is required"; return; }
        $id = $service->createStandingOrder($input['member_id'],$input['amount'],$input['frequency'],$input['next_run_date'],$input['savings_account_id'] ?? null,$input['loan_id'] ?? null,$input['end_date'] ?? null,$_SESSION['user_id']);
        http_response_code(201); $response['status']='success'; $response['data']=['standing_order_id'=>$id]; $response['message']='Standing order created'; return;
    }

    // POST /standing-orders/{id}/cancel
    if ($method === 'POST' && $action === 'cancel' && is_numeric($id)) {
        $ok = $service->cancelStandingOrder((int)$id);
        if ($ok) { $response['status']='success'; $response['message']='Standing order cancelled'; } else { http_response_code(400); $response['message']='Failed to cancel'; }
        return;
    }

    // GET /standing-orders/due
    if ($method === 'GET' && $action === 'due') {
        $date = $_GET['date'] ?? date('Y-m-d');
        $res = $service->getDueOrders($date);
        $response['status']='success'; $response['data']=$res; return;
    }

    // POST /standing-orders/run -> trigger processing (admin)
    if ($method === 'POST' && $action === 'run') {
        $date = $_POST['date'] ?? date('Y-m-d');
        $res = $service->processDueOrders($date, $_SESSION['user_id']);
        $response['status']='success'; $response['data']=$res; return;
    }

    http_response_code(404); $response['message']='Standing orders endpoint not found';
}

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../config/session_config.php';
require_once __DIR__ . '/../../app/Services/LoanService.php';
require_once __DIR__ . '/../../app/Services/LedgerService.php';
require_once __DIR__ . '/../../app/Services/SavingsService.php';
require_once __DIR__ . '/../../app/Services/AuditAuthNotificationServices.php';
require_once __DIR__ . '/../../app/Services/StandingOrderService.php';

session_start();

$response = [
    'status' => 'error',
    'data' => null,
    'message' => 'Unknown error'
];

try {
    // Parse request
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($path, '/'))));
    
    if (count($segments) >= 2 && $segments[0] === 'api' && $segments[1] === 'v1') {
        $segments = array_slice($segments, 2);
    }
    
    $resource = $segments[0] ?? null;
    $segment1 = $segments[1] ?? null;
    $segment2 = $segments[2] ?? null;
    $segment3 = $segments[3] ?? null;
    $action = null;
    $id = null;
    $extra = null;
    
    if ($resource === 'salary-deductions') {
        $action = $segment1;
        $id = $segment2;
        $extra = $segment3;
    } elseif ($resource === 'auth' || $resource === 'reports') {
        $action = $segment1;
    } else {
        if (is_numeric($segment1)) {
            $id = $segment1;
            $action = $segment2;
            $extra = $segment3;
        } else {
            $action = $segment1;
            $id = $segment2;
            $extra = $segment3;
        }
    }
    
    // Require authentication (except for login)
    if (!($resource === 'auth' && $action === 'login')) {
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            $response['message'] = 'Unauthorized - Please login first';
            echo json_encode($response);
            exit;
        }
    }
    
    // Route to appropriate handler
    switch ($resource) {
        case 'auth':
            handleAuthRequest($method, $action);
            break;
            
        case 'members':
            handleMembersRequest($method, $action, $id);
            break;
            
        case 'loans':
            handleLoansRequest($method, $action, $id);
            break;
            
        case 'savings':
            handleSavingsRequest($method, $action, $id);
            break;

        case 'standing-orders':
            handleStandingOrdersRequest($method, $action, $id);
            break;
            
        case 'reports':
            handleReportsRequest($method, $action);
            break;
            
        case 'salary-deductions':
            handleSalaryDeductionsRequest($method, $action, $id, $extra);
            break;
            
        default:
            http_response_code(404);
            $response['message'] = 'Endpoint not found';
    }
    
} catch (Exception $e) {
    http_response_code(500);
    $response['message'] = $e->getMessage();
}

echo json_encode($response);

/**
 * Handle authentication requests
 */
function handleAuthRequest($method, $action) {
    global $response;
    $input = json_decode(file_get_contents('php://input'), true);
    $db = Database::getInstance()->getConnection();
    $auth = new \SACCO\Services\AuthenticationService($db);

    switch ($action) {
        case 'login':
            if ($method !== 'POST') { http_response_code(405); $response['message']='Method not allowed'; break; }
            $username = $input['username'] ?? null; $password = $input['password'] ?? null;
            if (!$username || !$password) { http_response_code(400); $response['message']='Username and password required'; break; }

            $res = $auth->login($username, $password);
            if ($res['success']) {
                // Provide session id to client (use token header in real clients)
                $response['status'] = 'success';
                $response['data'] = [
                    'user_id' => $res['user_id'] ?? null,
                    'session_id' => $res['session_id'] ?? null,
                    'requires_2fa' => $res['requires_2fa'] ?? false
                ];
                $response['message'] = $res['message'] ?? 'Login processed';
            } else {
                http_response_code(401);
                $response['message'] = $res['message'] ?? 'Invalid credentials';
            }
            break;

        case 'verify-otp':
            if ($method !== 'POST') { http_response_code(405); $response['message']='Method not allowed'; break; }
            $userId = $input['user_id'] ?? null; $otp = $input['otp'] ?? null; $sessionId = $input['session_id'] ?? null;
            if (!$userId || !$otp || !$sessionId) { http_response_code(400); $response['message']='user_id, otp and session_id required'; break; }
            $ok = $auth->verifyOTP($userId, $otp);
            if ($ok) {
                // activate session by returning success
                $response['status'] = 'success';
                $response['data'] = ['session_id' => $sessionId];
                $response['message'] = 'OTP verified';
            } else { http_response_code(400); $response['message']='Invalid or expired OTP'; }
            break;

        case 'logout':
            if ($method !== 'POST') { http_response_code(405); $response['message']='Method not allowed'; break; }
            $sessionId = $input['session_id'] ?? null;
            if ($sessionId) { $auth->logout($sessionId); session_destroy(); }
            $response['status']='success'; $response['message']='Logout successful';
            break;

        case 'session':
            if ($method !== 'GET') { http_response_code(405); $response['message']='Method not allowed'; break; }
            $sessionId = $_GET['session_id'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);
            if (!$sessionId) { http_response_code(401); $response['message']='No session token provided'; break; }
            $session = $auth->validateSession($sessionId);
            if ($session) { $response['status']='success'; $response['data']=$session; } else { http_response_code(401); $response['message']='Invalid or expired session'; }
            break;

        default:
            http_response_code(404); $response['message']='Auth endpoint not found';
    }
}

/**
 * Handle members requests
 */
function handleMembersRequest($method, $action, $id) {
    global $response;
    
    $db = Database::getInstance()->getConnection();
    
    if ($action === null && $id === null) {
        if ($method === 'GET') {
            // List members
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $stmt = $db->prepare("SELECT * FROM members WHERE account_status = 'Active' LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            
            $response['status'] = 'success';
            $response['data'] = $stmt->fetchAll();
            return;
        }
        
        if ($method === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("INSERT INTO members (
                    membership_number, national_id, full_name, email,
                    phone_number, date_of_birth, gender, employment_status,
                    created_by, account_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");
            
            $result = $stmt->execute([
                $input['membership_number'],
                $input['national_id'],
                $input['full_name'],
                $input['email'],
                $input['phone_number'],
                $input['date_of_birth'],
                $input['gender'],
                $input['employment_status'] ?? 'Employed',
                $_SESSION['user_id']
            ]);
            
            if ($result) {
                http_response_code(201);
                $response['status'] = 'success';
                $response['data'] = [
                    'member_id' => $db->lastInsertId(),
                    'membership_number' => $input['membership_number']
                ];
                $response['message'] = 'Member registered successfully';
            } else {
                http_response_code(400);
                $response['message'] = 'Failed to register member';
            }
            return;
        }
    }
    
    if (is_numeric($action) && $id === null) {
        if ($method === 'GET') {
            $stmt = $db->prepare("SELECT * FROM members WHERE member_id = ?");
            $stmt->execute([$action]);
            $member = $stmt->fetch();
            
            if ($member) {
                $response['status'] = 'success';
                $response['data'] = $member;
            } else {
                http_response_code(404);
                $response['message'] = 'Member not found';
            }
            return;
        }
        
        if ($method === 'PUT') {
            $input = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE members SET email = ?, phone_number = ?, basic_salary = ?, modified_by = ?, last_modified = NOW() WHERE member_id = ?");
            
            $result = $stmt->execute([
                $input['email'] ?? null,
                $input['phone_number'] ?? null,
                $input['basic_salary'] ?? 0,
                $_SESSION['user_id'],
                $action
            ]);
            
            if ($result) {
                $response['status'] = 'success';
                $response['message'] = 'Member updated successfully';
            } else {
                http_response_code(400);
                $response['message'] = 'Failed to update member';
            }
            return;
        }
    }
    
    if (is_numeric($action) && $id === 'statement' && $method === 'GET') {
        $memberId = $action;
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        
        $stmt = $db->prepare("SELECT m.*, COALESCE(sa.current_balance, 0) as savings_balance, COALESCE(sh.total_share_capital, 0) as share_balance, COALESCE(l.outstanding_balance, 0) as loan_balance
            FROM members m
            LEFT JOIN savings_accounts sa ON m.member_id = sa.member_id
            LEFT JOIN share_accounts sh ON m.member_id = sh.member_id
            LEFT JOIN loans l ON m.member_id = l.member_id AND l.loan_status = 'Disbursed'
            WHERE m.member_id = ?");
        
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        
        if ($member) {
            $response['status'] = 'success';
            $response['data'] = $member;
        } else {
            http_response_code(404);
            $response['message'] = 'Member not found';
        }
        return;
    }
    
    http_response_code(404);
    $response['message'] = 'Member endpoint not found';
}

/**
 * Handle loans requests
 */
function handleLoansRequest($method, $action, $id) {
    global $response;
    
    $loanService = new \SACCO\Services\LoanService();
    
    if ($action === 'apply' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $loanService->applyForLoan(
            $input['member_id'],
            $input['product_id'],
            $input['amount'],
            $input['purpose'] ?? '',
            $_SESSION['user_id']
        );
        
        if ($result['success']) {
            http_response_code(201);
            $response['status'] = 'success';
            $response['data'] = $result;
        } else {
            http_response_code(400);
            $response['message'] = $result['message'];
        }
        return;
    }
    
    if (is_numeric($action) && $id === null && $method === 'GET') {
        $loan = $loanService->getLoan($action);
        if ($loan) {
            $response['status'] = 'success';
            $response['data'] = $loan;
        } else {
            http_response_code(404);
            $response['message'] = 'Loan not found';
        }
        return;
    }
    
    if (is_numeric($action) && $id === 'repay' && $method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $result = $loanService->processRepayment(
            $action,
            $input['amount'],
            $input['method'],
            $input['reference'] ?? '',
            $_SESSION['user_id']
        );
        
        if ($result['success']) {
            $response['status'] = 'success';
            $response['data'] = $result;
        } else {
            http_response_code(400);
            $response['message'] = $result['message'];
        }
        return;
    }
    
    if ($action === null && $id === null && $method === 'GET') {
        // List loans
        $db = Database::getInstance()->getConnection();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset = ($page - 1) * $limit;
        $stmt = $db->prepare("SELECT * FROM loans ORDER BY loan_id DESC LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        
        $response['status'] = 'success';
        $response['data'] = $stmt->fetchAll();
        return;
    }
    
    http_response_code(404);
    $response['message'] = 'Loan endpoint not found';
}

/**
 * Handle savings requests
 */
function handleSavingsRequest($method, $action, $id) {
    global $response;
    
    $db = Database::getInstance()->getConnection();
    // Normalize path possibilities: support both /savings/{id} and /savings/{id}/transactions
    $savingsService = new \SACCO\Services\SavingsService();

    // 1) POST /api/v1/savings/deposit
    if ($method === 'POST' && $action === 'deposit') {
        $input = json_decode(file_get_contents('php://input'), true);
        $memberId = $input['member_id'] ?? null;
        $accountId = $input['savings_account_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $paymentMethod = $input['method'] ?? 'Cash';
        $reference = $input['reference'] ?? null;

        if (!$memberId || !$accountId || !$amount) {
            http_response_code(400);
            $response['message'] = 'member_id, savings_account_id and amount are required';
            return;
        }

        $result = $savingsService->deposit((int)$memberId, (int)$accountId, (float)$amount, $paymentMethod, $_SESSION['user_id'], $reference);
        if ($result['success']) {
            http_response_code(201);
            $response['status'] = 'success';
            $response['data'] = ['new_balance' => $result['new_balance']];
            $response['message'] = 'Deposit posted';
        } else {
            http_response_code(400);
            $response['message'] = $result['message'];
        }
        return;
    }

    // 2) POST /api/v1/savings/withdraw
    if ($method === 'POST' && $action === 'withdraw') {
        $input = json_decode(file_get_contents('php://input'), true);
        $memberId = $input['member_id'] ?? null;
        $accountId = $input['savings_account_id'] ?? null;
        $amount = $input['amount'] ?? null;
        $paymentMethod = $input['method'] ?? 'Cash';
        $reference = $input['reference'] ?? null;

        if (!$memberId || !$accountId || !$amount) {
            http_response_code(400);
            $response['message'] = 'member_id, savings_account_id and amount are required';
            return;
        }

        $result = $savingsService->withdraw((int)$memberId, (int)$accountId, (float)$amount, $paymentMethod, $_SESSION['user_id'], $reference);
        if ($result['success']) {
            $response['status'] = 'success';
            $response['data'] = ['new_balance' => $result['new_balance']];
            $response['message'] = 'Withdrawal processed';
        } else {
            http_response_code(400);
            $response['message'] = $result['message'];
        }
        return;
    }

    // 3) GET account details and transactions - support either mapping (action is id) or (id is id)
    $accountId = null;
    if (is_numeric($action) && ($id === null || $id === '')) {
        $accountId = (int)$action; // /savings/{id}
        $subAction = null;
    } elseif (is_numeric($id)) {
        $accountId = (int)$id; // /savings/{id}/{sub}
        $subAction = $action; // maybe 'transactions'
    }

    if ($method === 'GET' && $accountId !== null) {
        $account = $savingsService->getAccount($accountId);
        if (!$account) {
            http_response_code(404);
            $response['message'] = 'Account not found';
            return;
        }

        if ($subAction === 'transactions') {
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
            $txs = $savingsService->listTransactions($accountId, $limit);
            $response['status'] = 'success';
            $response['data'] = ['transactions' => $txs];
            return;
        }

        // Default: return account summary + recent transactions
        $txs = $savingsService->listTransactions($accountId, 50);
        $response['status'] = 'success';
        $response['data'] = ['account' => $account, 'transactions' => $txs];
        return;
    }
}

/**
 * Handle reports requests
 */
function handleReportsRequest($method, $action) {
    global $response;
    
    if ($method !== 'GET') {
        http_response_code(405);
        $response['message'] = 'Method not allowed';
        return;
    }
    
    $ledgerService = new \SACCO\Services\LedgerService();
    $loanService = new \SACCO\Services\LoanService();
    
    switch ($action) {
        case 'defaulters':
            $defaulters = $loanService->getDefaulters('Overdue', 100);
            $response['status'] = 'success';
            $response['data'] = $defaulters;
            break;
            
        case 'trial-balance':
            $date = $_GET['date'] ?? date('Y-m-d');
            $result = $ledgerService->generateTrialBalance($date);
            $response['status'] = 'success';
            $response['data'] = $result;
            break;
            
        case 'balance-sheet':
            $date = $_GET['date'] ?? date('Y-m-d');
            $result = $ledgerService->generateBalanceSheet($date);
            $response['status'] = 'success';
            $response['data'] = $result;
            break;
            
        case 'income-statement':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $result = $ledgerService->generateIncomeStatement($startDate, $endDate);
            $response['status'] = 'success';
            $response['data'] = $result;
            break;
            
        default:
            http_response_code(404);
            $response['message'] = 'Report not found';
    }
}

/**
 * Handle salary deductions requests
 */
function handleSalaryDeductionsRequest($method, $action, $id) {
    global $response;
    
    $db = Database::getInstance()->getConnection();
    
    if ($method === 'POST' && $action === 'batches') {
        // Handle file upload
        if (!isset($_FILES['csv_file'])) {
            http_response_code(400);
            $response['message'] = 'CSV file required';
            return;
        }
        
        $uploadDir = __DIR__ . '/../../assets/uploads/salary_deductions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = time() . '_' . basename($_FILES['csv_file']['name']);
        $filePath = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['csv_file']['tmp_name'], $filePath)) {
            $input = $_POST;
            
            // Create batch record
            $batchNumber = 'BATCH-' . date('YmdHis');
            $stmt = $db->prepare("
                INSERT INTO salary_deduction_batches (
                    employer_id, batch_month, batch_number, file_path,
                    uploaded_by, batch_status
                ) VALUES (?, ?, ?, ?, ?, 'Uploaded')
            ");
            
            $stmt->execute([
                $input['employer_id'],
                $input['batch_month'],
                $batchNumber,
                $filePath,
                $_SESSION['user_id']
            ]);
            
            http_response_code(201);
            $response['status'] = 'success';
            $response['data'] = [
                'batch_id' => $db->lastInsertId(),
                'batch_number' => $batchNumber
            ];
            $response['message'] = 'Batch uploaded successfully';
        } else {
            http_response_code(500);
            $response['message'] = 'Failed to upload file';
        }
    } elseif ($method === 'GET' && is_numeric($id)) {
        // Get batch details
        $stmt = $db->prepare("SELECT * FROM salary_deduction_batches WHERE batch_id = ?");
        $stmt->execute([$id]);
        $batch = $stmt->fetch();
        
        if ($batch) {
            // Get batch details
            $detailStmt = $db->prepare("
                SELECT * FROM salary_deduction_details
                WHERE batch_id = ?
            ");
            $detailStmt->execute([$id]);
            
            $response['status'] = 'success';
            $response['data'] = [
                'batch' => $batch,
                'details' => $detailStmt->fetchAll()
            ];
        } else {
            http_response_code(404);
            $response['message'] = 'Batch not found';
        }
    }
}

?>
