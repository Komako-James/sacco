<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/LedgerService.php';

$auth->requireLogin();
$auth->requireRole(['admin','accountant','manager','cashier','teller']);
$user = $auth->getCurrentUser();
$db = getDB();

$error = null;
$success = null;

// Fetch expense accounts for selection
try {
    $acctStmt = $db->prepare("SELECT account_code, account_name FROM chart_of_accounts WHERE account_type = 'expense' ORDER BY account_name");
    $acctStmt->execute();
    $expenseAccounts = $acctStmt->fetchAll();
} catch (Exception $e) {
    $expenseAccounts = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $account_code = $_POST['account_code'] ?? ($expenseAccounts[0]['account_code'] ?? null);
    $amount = (float) ($_POST['amount'] ?? 0);
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $description = trim($_POST['description'] ?? '');
    $reference_no = trim($_POST['reference_no'] ?? '');

    if ($amount <= 0 || !$account_code) {
        $error = 'Please enter a valid amount and select an expense account.';
    } else {
        try {
            $db->beginTransaction();

            $ins = $db->prepare('INSERT INTO expenses (expense_date, category, amount, description, payment_method, reference_no, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            $categoryLabel = $account_code;
            $ins->execute([$expense_date, $categoryLabel, $amount, $description, $payment_method, $reference_no, $user['user_id']]);

            // Post to ledger (double-entry)
            try {
                \SACCO\Services\LedgerService::postOperatingExpense($amount, $account_code, $payment_method, $description, (int)$user['user_id']);
            } catch (Exception $e) {
                // Ledger posting failed — rollback
                $db->rollback();
                throw $e;
            }

            $db->commit();
            $success = 'Expense recorded successfully.';
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollback();
            }
            $error = 'Failed to record expense: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Record Expense</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>.form-card{max-width:820px;margin:0 auto}</style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Record Expense</h1>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <div class="card form-card">
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="post" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date" name="expense_date" class="form-control" value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Expense Account</label>
                            <select name="account_code" class="form-select" required>
                                <?php foreach ($expenseAccounts as $acc): ?>
                                    <option value="<?php echo htmlspecialchars($acc['account_code']); ?>" <?php echo (($_POST['account_code'] ?? '') === $acc['account_code']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($acc['account_name']); ?> (<?php echo htmlspecialchars($acc['account_code']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="cash" <?php echo (($_POST['payment_method'] ?? '') === 'cash') ? 'selected' : ''; ?>>Cash</option>
                                <option value="bank" <?php echo (($_POST['payment_method'] ?? '') === 'bank') ? 'selected' : ''; ?>>Bank</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Reference / Receipt No.</label>
                            <input type="text" name="reference_no" class="form-control" value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>">
                        </div>

                        <div class="col-12 text-end">
                            <button class="btn btn-primary" type="submit"><i class="bi bi-save"></i> Save Expense</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
