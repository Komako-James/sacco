<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$auth->requireLogin();
$service = new \SACCO\Services\StandingOrderService();
$db = getDB();

$errors = [];
$successMessage = '';

$members = $db->query('SELECT member_id, full_name, membership_no FROM members WHERE status = "active" ORDER BY full_name')->fetchAll();
$savingsAccounts = $db->query('SELECT account_id, account_number, account_type, member_id FROM savings_accounts WHERE status = "active" ORDER BY account_number')->fetchAll();
$loans = $db->query('SELECT loan_id, loan_ref_no, member_id, amount_requested, status FROM loans WHERE status IN ("approved", "disbursed") ORDER BY loan_ref_no')->fetchAll();

$input = [
    'member_id' => '',
    'amount' => '',
    'frequency' => 'monthly',
    'next_run_date' => '',
    'end_date' => '',
    'savings_account_id' => '',
    'loan_id' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = array_merge($input, array_map('trim', $_POST));

    if (!is_numeric($input['member_id']) || (int)$input['member_id'] <= 0) {
        $errors[] = 'Please select a member.';
    }

    if (!is_numeric($input['amount']) || (float)$input['amount'] <= 0) {
        $errors[] = 'Amount must be greater than zero.';
    }

    $allowedFrequencies = ['weekly', 'fortnightly', 'monthly'];
    if (!in_array($input['frequency'], $allowedFrequencies, true)) {
        $errors[] = 'Please select a valid frequency.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['next_run_date'])) {
        $errors[] = 'Next run date must be a valid YYYY-MM-DD date.';
    }

    if ($input['end_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $input['end_date'])) {
        $errors[] = 'End date must be a valid YYYY-MM-DD date.';
    }

    if ($input['savings_account_id'] === '' && $input['loan_id'] === '') {
        $errors[] = 'Please select a destination savings account or loan account.';
    }

    if ($input['savings_account_id'] !== '' && $input['loan_id'] !== '') {
        $errors[] = 'Please select only one destination: either savings or loan.';
    }

    if (empty($errors)) {
        $memberId = (int)$input['member_id'];
        $amount = (float)$input['amount'];
        $frequency = $input['frequency'];
        $nextRunDate = $input['next_run_date'];
        $endDate = $input['end_date'] ?: null;
        $savingsAccountId = $input['savings_account_id'] !== '' ? (int)$input['savings_account_id'] : null;
        $loanId = $input['loan_id'] !== '' ? (int)$input['loan_id'] : null;

        $standingOrderId = $service->createStandingOrder($memberId, $amount, $frequency, $nextRunDate, $savingsAccountId, $loanId, $endDate, $_SESSION['user_id'] ?? null);
        if ($standingOrderId) {
            $_SESSION['flash_success'] = 'Standing order created successfully.';
            header('Location: list.php');
            exit;
        }
        $errors[] = 'Unable to create the standing order. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Standing Order - <?php echo APP_NAME; ?></title>
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
                        <li class="breadcrumb-item active" aria-current="page">Create New</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h4 mb-3">Create Standing Order</h1>
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="create.php">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Member</label>
                                    <select name="member_id" class="form-select" required>
                                        <option value="">Select member</option>
                                        <?php foreach ($members as $member): ?>
                                            <option value="<?php echo $member['member_id']; ?>"<?php echo $input['member_id'] == $member['member_id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($member['full_name'] . ' (' . $member['membership_no'] . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" name="amount" class="form-control" value="<?php echo htmlspecialchars($input['amount']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Frequency</label>
                                    <select name="frequency" class="form-select" required>
                                        <?php foreach (['weekly', 'fortnightly', 'monthly'] as $freq): ?>
                                            <option value="<?php echo $freq; ?>"<?php echo $input['frequency'] === $freq ? ' selected' : ''; ?>><?php echo ucfirst(str_replace('-', ' ', $freq)); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Next Run Date</label>
                                    <input type="date" name="next_run_date" class="form-control" value="<?php echo htmlspecialchars($input['next_run_date']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">End Date (optional)</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($input['end_date']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination Savings Account</label>
                                    <select name="savings_account_id" class="form-select">
                                        <option value="">Select savings account</option>
                                        <?php foreach ($savingsAccounts as $account): ?>
                                            <option value="<?php echo $account['account_id']; ?>"<?php echo $input['savings_account_id'] == $account['account_id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($account['account_number'] . ' (' . ucfirst($account['account_type']) . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Destination Loan</label>
                                    <select name="loan_id" class="form-select">
                                        <option value="">Select loan</option>
                                        <?php foreach ($loans as $loan): ?>
                                            <option value="<?php echo $loan['loan_id']; ?>"<?php echo $input['loan_id'] == $loan['loan_id'] ? ' selected' : ''; ?>><?php echo htmlspecialchars($loan['loan_ref_no'] . ' (UGX ' . number_format($loan['amount_requested'], 2) . ')'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Create Standing Order</button>
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
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
