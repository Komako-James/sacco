<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$auth->requireLogin();
$service = new \SACCO\Services\StandingOrderService();

$standingOrderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = $service->getStandingOrderById($standingOrderId);
if (!$order) {
    http_response_code(404);
    echo 'Standing order not found.';
    exit;
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = trim($_POST['amount'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');
    $nextRunDate = trim($_POST['next_run_date'] ?? '');
    $endDate = trim($_POST['end_date'] ?? '');
    $savingsAccountId = trim($_POST['savings_account_id'] ?? '');
    $loanId = trim($_POST['loan_id'] ?? '');
    $status = trim($_POST['status'] ?? '');

    if (!is_numeric($amount) || (float)$amount <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }

    $allowedFrequencies = ['weekly', 'fortnightly', 'monthly'];
    if (!in_array($frequency, $allowedFrequencies, true)) {
        $errors[] = 'Please use a valid frequency.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextRunDate)) {
        $errors[] = 'Next run date must be a valid YYYY-MM-DD date.';
    }

    if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
        $errors[] = 'End date must be a valid YYYY-MM-DD date.';
    }

    if ($status !== '' && !in_array($status, ['active', 'suspended', 'cancelled'], true)) {
        $errors[] = 'Invalid status selected.';
    }

    if (empty($errors)) {
        $updateData = [
            'amount' => (float)$amount,
            'frequency' => $frequency,
            'next_run_date' => $nextRunDate,
            'end_date' => $endDate !== '' ? $endDate : null,
        ];

        if ($savingsAccountId !== '') {
            $updateData['savings_account_id'] = (int)$savingsAccountId;
        }
        if ($loanId !== '') {
            $updateData['loan_id'] = (int)$loanId;
        }
        if (!empty($order['status']) || $service->hasStatusColumn()) {
            if ($status === 'active') {
                $updateData['status'] = 'active';
                $updateData['is_active'] = 1;
            } elseif ($status === 'suspended') {
                $updateData['status'] = 'suspended';
                $updateData['is_active'] = 0;
            } elseif ($status === 'cancelled') {
                $updateData['status'] = 'cancelled';
                $updateData['is_active'] = 0;
            }
        } else {
            $updateData['is_active'] = $status === 'suspended' ? 0 : 1;
        }

        $success = $service->updateStandingOrder($standingOrderId, $updateData);
        if ($success) {
            $_SESSION['flash_success'] = 'Standing order updated successfully.';
            header('Location: view.php?id=' . $standingOrderId);
            exit;
        }
        $errors[] = 'Failed to update the standing order.';
    }
}

function buildStatusOptions($order) {
    $currentStatus = isset($order['status']) ? $order['status'] : (!empty($order['is_active']) ? 'active' : 'suspended');
    $options = [
        'active' => 'Active',
        'suspended' => 'Suspended',
    ];

    if (isset($order['status']) && $order['status'] === 'cancelled') {
        $options['cancelled'] = 'Cancelled';
    } else {
        $options['cancelled'] = 'Cancelled';
    }

    foreach ($options as $value => $label) {
        $selected = $currentStatus === $value ? ' selected' : '';
        echo "<option value=\"{$value}\"{$selected}>{$label}</option>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Standing Order #<?php echo htmlspecialchars($order['standing_order_id']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/standing-orders/list.php">Standing Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Edit Order</li>
                    </ol>
                </nav>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h4 mb-3">Edit Standing Order #<?php echo htmlspecialchars($order['standing_order_id']); ?></h1>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <form method="post" action="edit.php?id=<?php echo $standingOrderId; ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($_POST['amount'] ?? $order['amount']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Frequency</label>
                                    <select name="frequency" class="form-select" required>
                                        <?php foreach (['weekly', 'fortnightly', 'monthly'] as $freq): ?>
                                            <option value="<?php echo $freq; ?>"<?php echo (($_POST['frequency'] ?? $order['frequency']) === $freq) ? ' selected' : ''; ?>><?php echo ucfirst(str_replace('-', ' ', $freq)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Next Run Date</label>
                                    <input type="date" name="next_run_date" class="form-control" value="<?php echo htmlspecialchars($_POST['next_run_date'] ?? $order['next_run_date']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($_POST['end_date'] ?? $order['end_date']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination Savings Account ID</label>
                                    <input type="number" name="savings_account_id" class="form-control" value="<?php echo htmlspecialchars($_POST['savings_account_id'] ?? $order['savings_account_id']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination Loan ID</label>
                                    <input type="number" name="loan_id" class="form-control" value="<?php echo htmlspecialchars($_POST['loan_id'] ?? $order['loan_id']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select" required>
                                        <?php buildStatusOptions($order); ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="view.php?id=<?php echo $standingOrderId; ?>" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
