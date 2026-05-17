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

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../config/session_config.php';
require_once __DIR__ . '/../../app/Services/LoanService.php';
require_once __DIR__ . '/../../app/Services/LedgerService.php';

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
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }
            
            $username = $input['username'] ?? null;
            $password = $input['password'] ?? null;
            
            if (!$username || !$password) {
                http_response_code(400);
                $response['message'] = 'Username and password required';
                break;
            }
            
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                $response['status'] = 'success';
                $response['data'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ];
                $response['message'] = 'Login successful';
            } else {
                http_response_code(401);
                $response['message'] = 'Invalid credentials';
            }
            break;
            
        case 'logout':
            if ($method !== 'POST') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }
            
            session_destroy();
            $response['status'] = 'success';
            $response['message'] = 'Logout successful';
            break;
            
        case 'session':
            if ($method !== 'GET') {
                http_response_code(405);
                $response['message'] = 'Method not allowed';
                break;
            }
            
            if (isset($_SESSION['user_id'])) {
                $response['status'] = 'success';
                $response['data'] = [
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role']
                ];
            } else {
                http_response_code(401);
                $response['message'] = 'No active session';
            }
            break;
            
        default:
            http_response_code(404);
            $response['message'] = 'Auth endpoint not found';
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
    
    if ($method === 'GET' && is_numeric($action)) {
        // Get savings account details
        $stmt = $db->prepare("SELECT * FROM savings_accounts WHERE savings_account_id = ?");
        $stmt->execute([$action]);
        $account = $stmt->fetch();
        
        if ($account) {
            // Get transactions
            $txStmt = $db->prepare("
                SELECT * FROM savings_transactions
                WHERE savings_account_id = ?
                ORDER BY transaction_date DESC
                LIMIT 50
            ");
            $txStmt->execute([$action]);
            
            $response['status'] = 'success';
            $response['data'] = [
                'account' => $account,
                'transactions' => $txStmt->fetchAll()
            ];
        } else {
            http_response_code(404);
            $response['message'] = 'Account not found';
        }
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
