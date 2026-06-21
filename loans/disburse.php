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

$stmt = $db->prepare("SELECT l.*, l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount, m.full_name, m.membership_no FROM loans l JOIN members m ON l.member_id = m.member_id WHERE l.loan_id = ? AND l.status = 'approved'");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan) {
    header('Location: list.php');
    exit();
}

$error = '';
$success = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Use LoanService to perform disbursement and ensure ledger posting
        require_once __DIR__ . '/../app/Services/LoanService.php';

        $dbConn = Database::getInstance()->getConnection();
        $loanService = new \SACCO\Services\LoanService($dbConn);

        // If amount_approved is missing or zero, set it to the requested amount
        if (empty($loan['amount_approved']) || $loan['amount_approved'] <= 0) {
            $stmt = $dbConn->prepare("UPDATE loans SET amount_approved = ?, approval_date = NOW(), status = 'approved', approved_by = ? , outstanding_balance = ? WHERE loan_id = ?");
            $stmt->execute([$loan['loan_amount'], $_SESSION['user_id'], $loan['loan_amount'], $loanId]);
            // refresh loan data
            $stmt2 = $dbConn->prepare("SELECT l.*, l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount, m.full_name, m.membership_no FROM loans l JOIN members m ON l.member_id = m.member_id WHERE l.loan_id = ? AND l.status IN ('approved','disbursed')");
            $stmt2->execute([$loanId]);
            $loan = $stmt2->fetch();
        }

        // default disbursement method and empty bank details for admin
        $result = $loanService->disburseLoan($loanId, 'cash', [], $_SESSION['user_id']);

        if (!empty($result['success'])) {
            header('Location: list.php?status=disbursed&success=1');
            exit();
        }

        $error = 'Error disbursing loan: ' . ($result['message'] ?? 'Unknown error');
    } catch (Exception $e) {
        $error = 'Error disbursing loan: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disburse Loan - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Disburse Loan</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <p><strong>Loan Reference:</strong> <?php echo htmlspecialchars($loan['loan_reference']); ?></p>
                            <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['full_name']); ?></p>
                            <p><strong>Membership No:</strong> <?php echo htmlspecialchars($loan['membership_no']); ?></p>
                            <p><strong>Requested Amount:</strong> <?php echo formatMoney($loan['loan_amount']); ?></p>
                            <p><strong>Status:</strong> <span class="badge bg-info">Approved</span></p>
                        </div>

                        <form method="POST">
                            <div class="alert alert-warning">
                                This action will move the loan to <strong>Disbursed</strong> status and enable repayments.
                            </div>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Confirm Disbursement</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
