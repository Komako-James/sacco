<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'cashier']);

$db = Database::getInstance()->getConnection();

$membershipNo = trim($_REQUEST['membership_no'] ?? '');
$member = null;
$error = '';
$success = '';

if ($membershipNo) {
    $stmt = $db->prepare('SELECT * FROM members WHERE membership_no = ?');
    $stmt->execute([$membershipNo]);
    $member = $stmt->fetch();
    if (!$member) {
        $error = 'Member not found. Please verify the membership number.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipNo = trim($_POST['membership_no'] ?? '');
    $accountType = $_POST['account_type'] ?? '';
    $initialDeposit = floatval($_POST['initial_deposit'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $description = trim($_POST['description'] ?? 'Initial savings account opening deposit');

    if (empty($membershipNo)) {
        $error = 'Membership number is required.';
    } else {
        $stmt = $db->prepare('SELECT * FROM members WHERE membership_no = ?');
        $stmt->execute([$membershipNo]);
        $member = $stmt->fetch();
        if (!$member) {
            $error = 'Member not found. Please verify the membership number.';
        } elseif ($member['status'] !== 'active') {
            $error = 'Member must be active to open a savings account.';
        }
    }

    if (!$error && empty($accountType)) {
        $error = 'Please select the savings account type.';
    }

    if (!$error && $initialDeposit < 0) {
        $error = 'Initial deposit must be zero or positive.';
    }

    if (!$error) {
        try {
            $db->beginTransaction();

            $accountNumber = generateSavingsAccountNumber();
            $openingBalance = $initialDeposit;

            $stmt = $db->prepare('INSERT INTO savings_accounts (member_id, account_type, account_number, balance, opening_balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([
                $member['member_id'],
                $accountType,
                $accountNumber,
                $openingBalance,
                $openingBalance,
                'active'
            ]);

            $accountId = $db->lastInsertId();

            if ($initialDeposit > 0) {
                $receipt = generateReceiptNumber('DEP');
                $stmt = $db->prepare('INSERT INTO savings_transactions (account_id, transaction_type, amount, balance_after, payment_method, reference_no, receipt_no, description, posted_by, status, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
                $stmt->execute([
                    $accountId,
                    'deposit',
                    $initialDeposit,
                    $openingBalance,
                    $paymentMethod,
                    $_POST['reference_no'] ?? null,
                    $receipt,
                    $description,
                    $_SESSION['user_id'] ?? null,
                    'completed'
                ]);
            }

            $db->commit();
            $success = "Savings account opened successfully: <strong>$accountNumber</strong>";
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error opening savings account: ' . $e->getMessage();
        }
    }
}

$accountTypes = [
    'monthly_savings' => 'Monthly Savings',
    'share_capital' => 'Share Capital',
    'voluntary' => 'Voluntary Savings',
    'fixed_deposit' => 'Fixed Deposit',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Open Savings Account - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse ms-auto">
                <a href="../logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Open Savings Account</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php elseif ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="membership_no" class="form-label">Membership Number</label>
                                <input type="text" class="form-control" id="membership_no" name="membership_no" value="<?php echo htmlspecialchars($membershipNo); ?>" required>
                                <div class="form-text">Use the member's 3-digit membership number (001-700).</div>
                            </div>

                            <?php if ($member): ?>
                            <div class="mb-3">
                                <p><strong>Member:</strong> <?php echo htmlspecialchars($member['full_name']); ?> (<?php echo htmlspecialchars($member['membership_no']); ?>)</p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
                            </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="account_type" class="form-label">Account Type</label>
                                <select class="form-select" id="account_type" name="account_type" required>
                                    <option value="">Select type</option>
                                    <?php foreach ($accountTypes as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo (($_POST['account_type'] ?? '') === $value) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="initial_deposit" class="form-label">Initial Deposit</label>
                                <input type="number" class="form-control" id="initial_deposit" name="initial_deposit" min="0" step="0.01" value="<?php echo htmlspecialchars($_POST['initial_deposit'] ?? '0.00'); ?>">
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method">
                                    <option value="cash" <?php echo (($_POST['payment_method'] ?? '') === 'cash') ? 'selected' : ''; ?>>Cash</option>
                                    <option value="mobile_money" <?php echo (($_POST['payment_method'] ?? '') === 'mobile_money') ? 'selected' : ''; ?>>Mobile Money</option>
                                    <option value="bank_transfer" <?php echo (($_POST['payment_method'] ?? '') === 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Reference No</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no" value="<?php echo htmlspecialchars($_POST['reference_no'] ?? ''); ?>" placeholder="Optional reference number">
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($_POST['description'] ?? 'Initial savings account opening deposit'); ?></textarea>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Open Account</button>
                                <a href="../members/list.php" class="btn btn-secondary">Back to Members</a>
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
