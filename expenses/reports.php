<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$auth->requireLogin();
$auth->requireRole(['admin','accountant','manager']);
$db = getDB();

// Simple recent expenses report
try {
    $stmt = $db->prepare('SELECT * FROM expenses ORDER BY expense_date DESC LIMIT 200');
    $stmt->execute();
    $rows = $stmt->fetchAll();
} catch (Exception $e) {
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Expense Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Expense Reports</h1>
                <a href="index.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category / Account</th>
                                    <th class="text-end">Amount</th>
                                    <th>Description</th>
                                    <th>Payment</th>
                                    <th>Reference</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="6" class="text-center">No expense records found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($r['expense_date'] ?? $r['created_at'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['category'] ?? ($r['account_code'] ?? '')); ?></td>
                                            <td class="text-end"><?php echo isset($r['amount']) ? formatMoney($r['amount']) : ''; ?></td>
                                            <td><?php echo htmlspecialchars($r['description'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['payment_method'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($r['reference_no'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
