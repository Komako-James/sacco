<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'finance']);

$loanId = $_GET['id'] ?? null;
if (!$loanId) {
    header('Location: list.php');
    exit();
}

$db = Database::getInstance()->getConnection();

// Get loan
$stmt = $db->prepare("
    SELECT l.*, m.full_name, m.membership_no
    FROM loans l
    JOIN members m ON l.member_id = m.member_id
    WHERE l.loan_id = ? AND l.status = 'disbursed'
");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    header('Location: list.php');
    exit();
}

// Get schedule
$stmt = $db->prepare("SELECT * FROM loan_schedules WHERE loan_id = ? ORDER BY installment ASC");
$stmt->execute([$loanId]);
$schedule = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $referenceNo = $_POST['reference_no'] ?? '';
    
    if ($amount <= 0) {
        $error = 'Please enter a valid amount';
    } elseif ($amount > $loan['outstanding_balance']) {
        $error = 'Amount exceeds outstanding balance';
    } elseif (empty($paymentMethod)) {
        $error = 'Please select payment method';
    } else {
        try {
            $db->beginTransaction();
            
            // Record payment
            $receipt = generateReceiptNumber('LRP');
            $stmt = $db->prepare("
                INSERT INTO loan_repayments 
                (loan_id, amount, payment_method, reference_no, receipt_no, paid_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$loanId, $amount, $paymentMethod, $referenceNo, $receipt, $_SESSION['user_id']]);
            
            // Update loan balance
            $newBalance = $loan['outstanding_balance'] - $amount;
            $status = $newBalance <= 0 ? 'completed' : 'disbursed';
            
            $stmt = $db->prepare("
                UPDATE loans 
                SET outstanding_balance = ?, status = ?
                WHERE loan_id = ?
            ");
            $stmt->execute([$newBalance, $status, $loanId]);
            
            $db->commit();
            $success = 'Payment recorded successfully. Receipt: ' . $receipt;
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error processing payment: ' . $e->getMessage();
        }
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
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
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Inst</th>
                                    <th>Principal</th>
                                    <th>Interest</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($schedule as $s): ?>
                                <tr>
                                    <td><?php echo $s['installment']; ?></td>
                                    <td><?php echo formatMoney($s['principal']); ?></td>
                                    <td><?php echo formatMoney($s['interest']); ?></td>
                                    <td><?php echo formatMoney($s['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
