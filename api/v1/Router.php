<?php
/**
 * SACCO REST API - Core Endpoints
 * RESTful JSON API for member portal, admin dashboard, and mobile app
 * 
 * Base URL: /api/v1/
 * Authentication: Bearer token (from login) or Biometric
 */

namespace SACCO\API;

use SACCO\Services\LoanService;
use SACCO\Services\LedgerService;
use SACCO\Services\InterestCalculationService;
use SACCO\Services\SalaryDeductionService;
use SACCO\Services\AuthenticationService;
use SACCO\Services\AuditService;
use SACCO\Services\NotificationService;

class APIRouter
{
    private $db;
    private $requestMethod;
    private $requestPath;
    private $requestBody;
    private $userId;
    private $userRole;

    public function __construct($db)
    {
        $this->db = $db;
        $this->requestMethod = $_SERVER['REQUEST_METHOD'];
        $this->requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $this->requestBody = json_decode(file_get_contents('php://input'), true) ?? [];
        
        // Parse auth header
        $this->parseAuth();
    }

    /**
     * Route and handle requests
     */
    public function route()
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        if ($this->requestMethod === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        try {
            // Remove /api/v1/ prefix
            $path = str_replace('/api/v1/', '', $this->requestPath);
            $parts = explode('/', trim($path, '/'));

            // Route to appropriate handler
            $resource = $parts[0] ?? '';
            $action = $parts[1] ?? null;
            $id = $parts[2] ?? null;

            // Public endpoints (no auth required)
            if ($resource === 'auth') {
                $this->handleAuth($action);
                return;
            }

            // Protected endpoints (auth required)
            if (!$this->userId) {
                $this->errorResponse('Unauthorized', 401);
                return;
            }

            switch ($resource) {
                case 'members':
                    $this->handleMembers($action, $id);
                    break;
                case 'loans':
                    $this->handleLoans($action, $id);
                    break;
                case 'savings':
                    $this->handleSavings($action, $id);
                    break;
                case 'salary-deductions':
                    $this->handleSalaryDeductions($action, $id);
                    break;
                case 'reports':
                    $this->handleReports($action);
                    break;
                case 'ledger':
                    $this->handleLedger($action);
                    break;
                case 'standing-orders':
                    $this->handleStandingOrders($action, $id);
                    break;
                default:
                    $this->errorResponse('Endpoint not found', 404);
            }
        } catch (\Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Authentication endpoints
     */
    private function handleAuth($action)
    {
        $authService = new AuthenticationService($this->db);

        switch ($action) {
            case 'login':
                if ($this->requestMethod !== 'POST') {
                    $this->errorResponse('Method not allowed', 405);
                    return;
                }

                $username = $this->requestBody['username'] ?? null;
                $password = $this->requestBody['password'] ?? null;

                if (!$username || !$password) {
                    $this->errorResponse('Username and password required', 400);
                    return;
                }

                $result = $authService->login($username, $password);
                if ($result['success']) {
                    $this->successResponse($result);
                } else {
                    $this->errorResponse($result['message'], 401);
                }
                break;

            case 'otp/verify':
                if ($this->requestMethod !== 'POST') {
                    $this->errorResponse('Method not allowed', 405);
                    return;
                }

                $userId = $this->requestBody['user_id'] ?? null;
                $otp = $this->requestBody['otp'] ?? null;

                if (!$userId || !$otp) {
                    $this->errorResponse('User ID and OTP required', 400);
                    return;
                }

                if ($authService->verifyOTP($userId, $otp)) {
                    $sessionId = $this->createSession($userId);
                    $this->successResponse(['session_id' => $sessionId]);
                } else {
                    $this->errorResponse('Invalid or expired OTP', 401);
                }
                break;

            case 'logout':
                // Invalidate session
                $this->successResponse(['message' => 'Logged out successfully']);
                break;

            default:
                $this->errorResponse('Auth action not found', 404);
        }
    }

    /**
     * Member endpoints
     */
    private function handleMembers($action, $id)
    {
        if ($this->requestMethod === 'GET') {
            if ($id) {
                // GET /members/{id}
                $this->getMemberDetails($id);
            } else {
                // GET /members (list)
                $this->listMembers();
            }
        } elseif ($this->requestMethod === 'POST' && !$action) {
            // POST /members (create)
            $this->createMember();
        } elseif ($this->requestMethod === 'PUT' && $id) {
            // PUT /members/{id}
            $this->updateMember($id);
        } elseif ($action === 'statement' && $id) {
            // GET /members/{id}/statement
            $this->getMemberStatement($id);
        } elseif ($action === 'balances' && $id) {
            // GET /members/{id}/balances
            $this->getMemberBalances($id);
        } else {
            $this->errorResponse('Member action not found', 404);
        }
    }

    /**
     * Loan endpoints
     */
    private function handleLoans($action, $id)
    {
        $loanService = new LoanService($this->db);

        if ($this->requestMethod === 'GET') {
            if ($id) {
                // GET /loans/{id}
                $loan = $loanService->getLoanById($id);
                if ($loan) {
                    $this->successResponse($loan);
                } else {
                    $this->errorResponse('Loan not found', 404);
                }
            } else {
                // GET /loans (list)
                $this->listLoans();
            }
        } elseif ($this->requestMethod === 'POST' && $action === 'apply') {
            // POST /loans/apply
            $memberId = $this->requestBody['member_id'] ?? $this->userId;
            $productId = $this->requestBody['product_id'] ?? null;
            $amount = $this->requestBody['amount'] ?? null;
            $purpose = $this->requestBody['purpose'] ?? null;

            if (!$productId || !$amount) {
                $this->errorResponse('Product ID and amount required', 400);
                return;
            }

            $result = $loanService->applyForLoan($memberId, $productId, $amount, $purpose, $this->userId);
            if ($result['success']) {
                $this->successResponse($result, 201);
            } else {
                $this->errorResponse($result['message'], 400);
            }
        } elseif ($this->requestMethod === 'POST' && $id && $action === 'repay') {
            // POST /loans/{id}/repay
            $amount = $this->requestBody['amount'] ?? null;
            $method = $this->requestBody['payment_method'] ?? null;
            $reference = $this->requestBody['reference_number'] ?? null;

            if (!$amount || !$method) {
                $this->errorResponse('Amount and payment method required', 400);
                return;
            }

            $result = $loanService->processRepayment($id, $amount, $method, $reference, $this->userId);
            if ($result['success']) {
                $this->successResponse($result);
            } else {
                $this->errorResponse($result['message'], 400);
            }
        } else {
            $this->errorResponse('Loan action not found', 404);
        }
    }

    /**
     * Savings endpoints
     */
    private function handleSavings($action, $id)
    {
        // Implementation for savings operations
        // GET /savings - list accounts
        // POST /savings/deposit - record deposit
        // POST /savings/withdraw - record withdrawal
        $this->errorResponse('Not implemented', 501);
    }

    /**
     * Salary deduction endpoints
     */
    private function handleSalaryDeductions($action, $id)
    {
        $salaryService = new SalaryDeductionService($this->db);

        if ($action === 'batches' && $this->requestMethod === 'POST') {
            // POST /salary-deductions/batches (upload)
            $this->uploadSalaryBatch($salaryService);
        } elseif ($action === 'batches' && $id && $this->requestMethod === 'GET') {
            // GET /salary-deductions/batches/{id} (details)
            $batch = $salaryService->generateBatchReport($id);
            if ($batch) {
                $this->successResponse($batch);
            } else {
                $this->errorResponse('Batch not found', 404);
            }
        } elseif ($action === 'batches' && $id && $this->requestMethod === 'POST') {
            // POST /salary-deductions/batches/{id}/process
            $result = $salaryService->processBatchDeductions($id, $this->userId);
            if ($result['success']) {
                $this->successResponse($result);
            } else {
                $this->errorResponse($result['message'], 400);
            }
        } else {
            $this->errorResponse('Salary deduction action not found', 404);
        }
    }

    /**
     * Report endpoints
     */
    private function handleReports($action)
    {
        if ($this->requestMethod !== 'GET') {
            $this->errorResponse('Only GET allowed', 405);
            return;
        }

        switch ($action) {
            case 'defaulters':
                $defaulters = (new LoanService($this->db))->getDefaulters('active');
                $this->successResponse(['data' => $defaulters]);
                break;

            case 'trial-balance':
                $date = $_GET['date'] ?? date('Y-m-d');
                $trialBalance = LedgerService::generateTrialBalance($date);
                $this->successResponse(['data' => $trialBalance, 'date' => $date]);
                break;

            case 'balance-sheet':
                $date = $_GET['date'] ?? date('Y-m-d');
                $balanceSheet = LedgerService::generateBalanceSheet($date);
                $this->successResponse($balanceSheet);
                break;

            case 'income-statement':
                $startDate = $_GET['start_date'] ?? date('Y-m-01');
                $endDate = $_GET['end_date'] ?? date('Y-m-d');
                $incomeStatement = LedgerService::generateIncomeStatement($startDate, $endDate);
                $this->successResponse($incomeStatement);
                break;

            default:
                $this->errorResponse('Report not found', 404);
        }
    }

    /**
     * Ledger endpoints
     */
    private function handleLedger($action)
    {
        // GET /ledger/entries
        // GET /ledger/day-sheet
        // POST /ledger/reverse/{entryId}
        $this->errorResponse('Not implemented', 501);
    }

    /**
     * Standing order endpoints
     */
    private function handleStandingOrders($action, $id)
    {
        // GET /standing-orders
        // POST /standing-orders (create)
        // GET /standing-orders/{id}
        // POST /standing-orders/{id}/receipt (record receipt)
        $this->errorResponse('Not implemented', 501);
    }

    // ===== Helper Methods =====

    private function successResponse($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'success',
            'data' => $data
        ]);
        exit;
    }

    private function errorResponse($message, $statusCode = 400)
    {
        http_response_code($statusCode);
        echo json_encode([
            'status' => 'error',
            'message' => $message
        ]);
        exit;
    }

    private function parseAuth()
    {
        // Get bearer token
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';

        if (preg_match('/Bearer\s+(.+)/', $auth, $matches)) {
            $token = $matches[1];
            // Validate token and get user
            $stmt = $this->db->prepare("
                SELECT s.user_id, u.role
                FROM sessions s
                JOIN users u ON s.user_id = u.user_id
                WHERE s.session_id = ? AND s.is_active = TRUE AND s.expires_at > NOW()
            ");
            $stmt->execute([$token]);
            $session = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($session) {
                $this->userId = $session['user_id'];
                $this->userRole = $session['role'];
            }
        }
    }

    private function createSession($userId)
    {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $stmt = $this->db->prepare("
            INSERT INTO sessions (session_id, user_id, expires_at, is_active)
            VALUES (?, ?, ?, TRUE)
        ");
        $stmt->execute([$sessionId, $userId, $expiresAt]);

        return $sessionId;
    }

    // Placeholder methods for member operations
    private function getMemberDetails($id) { $this->errorResponse('Not implemented', 501); }
    private function listMembers() { $this->errorResponse('Not implemented', 501); }
    private function createMember() { $this->errorResponse('Not implemented', 501); }
    private function updateMember($id) { $this->errorResponse('Not implemented', 501); }
    private function getMemberStatement($id) { $this->errorResponse('Not implemented', 501); }
    private function getMemberBalances($id) { $this->errorResponse('Not implemented', 501); }
    private function listLoans() { $this->errorResponse('Not implemented', 501); }
    private function uploadSalaryBatch($service) { $this->errorResponse('Not implemented', 501); }
}

// Main API entry point
if (php_sapi_name() !== 'cli') {
    global $db;
    if (!isset($db)) {
        require_once __DIR__ . '/../../config/db_connection.php';
        $db = Database::getInstance()->getConnection();
    }
    
    $router = new APIRouter($db);
    $router->route();
}

?>
