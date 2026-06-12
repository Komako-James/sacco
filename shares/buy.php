<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$user = $auth->getCurrentUser();
$shareService = new \SACCO\Services\ShareService();
$db = getDB();

$message = '';
$messageType = 'success';
$shareTablesAvailable = true;
$member = null;
$savingsAccounts = [];
$action = $_POST['action'] ?? '';

$membershipNo = trim($_POST['membership_no'] ?? '');
if ($membershipNo !== '') {
    $member = $shareService->getMemberByMembershipNumber($membershipNo);
}

try {
    if ($membershipNo !== '' && !$member) {
        $savingsAccounts = [];
    } else {
        $sql =
            'SELECT sa.account_id, sa.account_number, sa.balance, sa.account_type, m.full_name, m.membership_no
             FROM savings_accounts sa
             JOIN members m ON sa.member_id = m.member_id
             WHERE sa.status = ?';
        $params = ['active'];

        if ($member) {
            $sql .= ' AND m.membership_no = ?';
            $params[] = $member['membership_no'];
        }

        $sql .= ' ORDER BY m.full_name, sa.account_type';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $savingsAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $shareTablesAvailable = false;
    $savingsAccounts = [];
    $message = 'Unable to load savings accounts. Please check your database configuration.';
    $messageType = 'danger';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipNo = trim($_POST['membership_no'] ?? '');
    $accountId = (int) ($_POST['savings_account_id'] ?? 0);
    $shareCount = (int) ($_POST['shares'] ?? 0);

    if ($action === 'search') {
        if (empty($membershipNo)) {
            $message = 'Enter a membership number to search for the member.';
            $messageType = 'danger';
        } elseif (!$member) {
            $message = 'Member not found. Please verify the membership number.';
            $messageType = 'danger';
        }
    } else {
        if (empty($membershipNo) || $accountId <= 0 || $shareCount <= 0) {
            $message = 'Please provide a member, a savings account, and the number of shares.';
            $messageType = 'danger';
        } else {
            $member = $shareService->getMemberByMembershipNumber($membershipNo);
            if (!$member) {
                $message = 'Member not found. Please verify the membership number.';
                $messageType = 'danger';
            } else {
                try {
                    $result = $shareService->purchaseSharesFromSavings($member['member_id'], $accountId, $shareCount, $user['user_id']);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'danger';
                } catch (Exception $e) {
                    $message = 'Purchase failed: ' . $e->getMessage();
                    $messageType = 'danger';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buy Shares - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/shares/index.php">Shares</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Buy Shares</li>
                </ol>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0"><i class="bi bi-cart-plus me-2"></i> Buy Shares from Savings</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
                            <?php endif; ?>

                            <?php if (!$shareTablesAvailable): ?>
                                <div class="alert alert-warning">Share module is not available. Verify your share tables and run migrations.</div>
                            <?php endif; ?>

                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                <label for="membership_no" class="form-label">Member Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="membership_no" name="membership_no" required value="<?php echo htmlspecialchars($_POST['membership_no'] ?? ''); ?>">
                                    <button type="submit" class="btn btn-outline-secondary" name="action" value="search" formnovalidate>
                                        <i class="bi bi-search"></i> Search
                                    </button>
                                </div>
                                <div class="invalid-feedback">Enter the member's membership number.</div>
                            </div>

                                <?php if (!empty($membershipNo) && $member): ?>
                                <div class="alert alert-secondary py-2 mb-3">
                                    <strong>Member:</strong> <?php echo htmlspecialchars($member['full_name']); ?> <br>
                                    <strong>Membership No:</strong> <?php echo htmlspecialchars($member['membership_no']); ?>
                                </div>
                                <?php elseif (!empty($membershipNo) && !$member): ?>
                                <div class="alert alert-danger py-2 mb-3">
                                    Member not found. Please verify the membership number.
                                </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="savings_account_id" class="form-label">Savings Account</label>
                                    <select class="form-select" id="savings_account_id" name="savings_account_id" required>
                                        <option value="">Select an active savings account</option>
                                        <?php if (empty($savingsAccounts) && !empty($membershipNo) && $member): ?>
                                            <option value="" disabled>No active savings account found for this member.</option>
                                        <?php endif; ?>
                                        <?php foreach ($savingsAccounts as $account): ?>
                                            <option value="<?php echo $account['account_id']; ?>" <?php echo (int)($_POST['savings_account_id'] ?? 0) === (int)$account['account_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($account['membership_no']); ?> | <?php echo htmlspecialchars($account['account_number']); ?> | <?php echo htmlspecialchars($account['account_type']); ?> | Balance: <?php echo formatMoney($account['balance']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Choose a savings account owned by the member.</div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="shares" class="form-label">Share Quantity</label>
                                        <input type="number" class="form-control" id="shares" name="shares" min="1" required value="<?php echo htmlspecialchars($_POST['shares'] ?? ''); ?>">
                                        <div class="invalid-feedback">Enter the number of shares to purchase.</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Total Cost</label>
                                        <input type="text" class="form-control" id="total_cost" readonly value="<?php echo formatMoney((int)($_POST['shares'] ?? 0) * SHARE_PRICE); ?>">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="2" disabled>Share purchase from savings account</textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-warning" name="action" value="purchase">Purchase Shares</button>
                                    <a href="index.php" class="btn btn-secondary">Back to Share Dashboard</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sharesInput = document.getElementById('shares');
        const totalCost = document.getElementById('total_cost');
        const pricePerShare = <?php echo json_encode(SHARE_PRICE); ?>;

        function updateCost() {
            const shares = parseInt(sharesInput.value, 10) || 0;
            totalCost.value = (shares * pricePerShare).toFixed(2);
        }

        sharesInput?.addEventListener('input', updateCost);

        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
