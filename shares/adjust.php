<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant']);
$user = $auth->getCurrentUser();
$shareService = new \SACCO\Services\ShareService();

$message = '';
$messageType = 'success';
$member = null;
$currentHolding = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipNo = trim($_POST['membership_no'] ?? '');
    $adjustmentType = trim($_POST['adjustment_type'] ?? 'increase');
    $shareCount = (int) ($_POST['shares'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if (empty($membershipNo) || $shareCount <= 0 || empty($note)) {
        $message = 'Please enter membership number, adjustment quantity, and reason.';
        $messageType = 'danger';
    } else {
        $member = $shareService->getMemberByMembershipNumber($membershipNo);
        if (!$member) {
            $message = 'Member not found. Please verify the membership number.';
            $messageType = 'danger';
        } else {
            $increase = $adjustmentType === 'increase';
            $result = $shareService->adjustMemberShares($member['member_id'], $shareCount, $user['user_id'], $note, $increase);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
        }
    }
}

if (!empty($_GET['membership_no'])) {
    $member = $shareService->getMemberByMembershipNumber(trim($_GET['membership_no']));
}

if ($member) {
    $currentHolding = $shareService->getMemberShareHolding($member['member_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adjust Shares - <?php echo APP_NAME; ?></title>
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
                    <li class="breadcrumb-item active" aria-current="page">Adjust Shares</li>
                </ol>
            </nav>

            <?php if ($message): ?>
                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="bi bi-tools me-2"></i> Share Adjustment</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" class="row g-3 needs-validation" novalidate>
                                <div class="col-md-6">
                                    <label for="membership_no" class="form-label">Membership Number</label>
                                    <input type="text" class="form-control" id="membership_no" name="membership_no" required value="<?php echo htmlspecialchars($_POST['membership_no'] ?? $_GET['membership_no'] ?? ''); ?>">
                                    <div class="invalid-feedback">Enter the member's membership number.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="adjustment_type" class="form-label">Adjustment Type</label>
                                    <select class="form-select" id="adjustment_type" name="adjustment_type">
                                        <option value="increase" <?php echo (($_POST['adjustment_type'] ?? '') === 'increase') ? 'selected' : ''; ?>>Increase Shares</option>
                                        <option value="decrease" <?php echo (($_POST['adjustment_type'] ?? '') === 'decrease') ? 'selected' : ''; ?>>Decrease Shares</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="shares" class="form-label">Number of Shares</label>
                                    <input type="number" class="form-control" id="shares" name="shares" min="1" required value="<?php echo htmlspecialchars($_POST['shares'] ?? ''); ?>">
                                    <div class="invalid-feedback">Enter a positive share quantity.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="note" class="form-label">Reason</label>
                                    <textarea class="form-control" id="note" name="note" rows="3" required><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                                    <div class="invalid-feedback">Provide a reason for the adjustment.</div>
                                </div>
                                <div class="col-12 d-grid gap-2">
                                    <button type="submit" class="btn btn-danger">Submit Adjustment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i> Current Holding</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($member): ?>
                                <p class="mb-1"><strong>Member:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
                                <p class="mb-1"><strong>Membership No:</strong> <?php echo htmlspecialchars($member['membership_no']); ?></p>
                                <p class="mb-1"><strong>Shares Owned:</strong> <?php echo number_format($currentHolding['shares_owned'] ?? 0); ?></p>
                                <p class="mb-0"><strong>Current Value:</strong> <?php echo formatMoney((int)($currentHolding['shares_owned'] ?? 0) * SHARE_PRICE); ?></p>
                            <?php else: ?>
                                <p class="text-muted mb-0">Enter a membership number to view current holding details.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Adjustment Notes</h5>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0">
                                <li>Positive adjustments add shares to the member's holding.</li>
                                <li>Negative adjustments decrease shares and update the investment value.</li>
                                <li>Adjustments are audited and posted to the general ledger.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
