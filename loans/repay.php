<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
$auth->requireLogin();
$auth->requireRole(['admin', 'finance']);

$loanId = $_GET['id'] ?? null;
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

// Load loan and schedule
$stmt = $db->prepare("SELECT l.*, l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount, m.full_name, m.membership_no FROM loans l JOIN members m ON l.member_id = m.member_id WHERE l.loan_id = ?");
$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT schedule_id, installment_no, due_date, principal_amount, interest_amount, total_due, status FROM loan_repayment_schedule WHERE loan_id = ? ORDER BY installment_no");
$stmt->execute([$loanId]);
$schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $paymentMethod = $_POST['payment_method'] ?? '';
    $referenceNo = $_POST['reference_no'] ?? '';

    try {
        require_once __DIR__ . '/../app/Services/LoanService.php';
        $loanService = new \SACCO\Services\LoanService($db);

        $result = $loanService->processRepayment($loanId, $amount, $paymentMethod, $referenceNo, $_SESSION['user_id']);

        if (!empty($result['success'])) {
            $success = 'Payment recorded successfully. Receipt: ' . ($result['receipt_number'] ?? '');
            // refresh outstanding balance and schedule
            $stmt = $db->prepare("SELECT outstanding_balance FROM loans WHERE loan_id = ?");
            $stmt->execute([$loanId]);
            $loan['outstanding_balance'] = $stmt->fetchColumn();
        } else {
            $error = 'Error processing payment: ' . ($result['message'] ?? 'Unknown error');
        }
    } catch (Exception $e) {
        $error = 'Error processing payment: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Repayment - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/loans/list.php">Loans</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Loan Repayment</li>
                </ol>
            </nav>

            <div class="row">
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Loan Repayment</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php elseif ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['full_name']); ?></p>
                                <p><strong>Membership No:</strong> <?php echo htmlspecialchars($loan['membership_no']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Loan Reference:</strong> <?php echo htmlspecialchars($loan['loan_reference']); ?></p>
                                <p><strong>Original Amount:</strong> <?php echo formatMoney($loan['loan_amount']); ?></p>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Outstanding Balance:</strong> <?php echo formatMoney($loan['outstanding_balance']); ?></label>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Payment Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required placeholder="0.00">
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select payment method</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Reference No (e.g., Transaction ID)</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" placeholder="Optional">
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list.php" class="btn btn-secondary">Back</a>
                                <button type="submit" class="btn btn-primary">Record Payment</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Repayment Schedule</h5>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        <?php if (!$schedule): ?>
                            <p class="text-muted">Repayment schedule is not available for this loan.</p>
                        <?php else: ?>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Inst</th>
                                        <th>Due Date</th>
                                        <th>Principal</th>
                                        <th>Interest</th>
                                        <th>Total Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($schedule as $s): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($s['installment_no']); ?></td>
                                        <td><?php echo htmlspecialchars($s['due_date']); ?></td>
                                        <td><?php echo formatMoney($s['principal_amount']); ?></td>
                                        <td><?php echo formatMoney($s['interest_amount']); ?></td>
                                        <td><?php echo formatMoney($s['total_due']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($s['status'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
