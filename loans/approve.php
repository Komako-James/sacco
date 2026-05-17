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
    SELECT l.*, m.full_name, m.membership_no, lp.default_interest_rate
    FROM loans l
    JOIN members m ON l.member_id = m.member_id
    JOIN loan_products lp ON l.product_id = lp.product_id
    WHERE l.loan_id = ?
");
$stmt->execute([$loanId]);
$loan = $stmt->fetch();

if (!$loan || $loan['status'] !== 'application') {
    header('Location: list.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $decision = $_POST['decision'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    
    if ($decision === 'approve') {
        $stmt = $db->prepare("
            UPDATE loans 
            SET status = 'approved', approved_by = ?, approved_date = NOW(), remarks = ?
            WHERE loan_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $remarks, $loanId]);
        $success = 'Loan approved successfully';
    } elseif ($decision === 'reject') {
        $stmt = $db->prepare("
            UPDATE loans 
            SET status = 'rejected', approved_by = ?, approved_date = NOW(), remarks = ?
            WHERE loan_id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $remarks, $loanId]);
        $success = 'Loan rejected';
    }
    
    if ($success) {
        header('Location: list.php?success=1');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Loan - <?php echo APP_NAME; ?></title>
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
                        <h4 class="mb-0">Loan Approval</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Member:</strong> <?php echo htmlspecialchars($loan['full_name']); ?></p>
                                <p><strong>Membership No:</strong> <?php echo htmlspecialchars($loan['membership_no']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Loan Reference:</strong> <?php echo htmlspecialchars($loan['loan_reference']); ?></p>
                                <p><strong>Applied Date:</strong> <?php echo date('M d, Y', strtotime($loan['created_at'])); ?></p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <p><strong>Requested Amount:</strong> <?php echo formatMoney($loan['loan_amount']); ?></p>
                                <p><strong>Term:</strong> <?php echo $loan['repayment_period']; ?> months</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Interest Rate:</strong> <?php echo $loan['interest_rate']; ?>%</p>
                                <p><strong>Status:</strong> <span class="badge bg-warning">Pending Approval</span></p>
                            </div>
                        </div>

                        <hr>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label"><strong>Decision</strong></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="decision" id="approve" value="approve" required>
                                    <label class="form-check-label" for="approve">
                                        Approve Loan
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="decision" id="reject" value="reject">
                                    <label class="form-check-label" for="reject">
                                        Reject Loan
                                    </label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="remarks" class="form-label">Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="4" placeholder="Enter approval remarks..."></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" class="btn btn-primary">Submit Decision</button>
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
