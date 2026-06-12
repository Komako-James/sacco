<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$user = $auth->getCurrentUser();
$shareService = new \SACCO\Services\ShareService();

$message = '';
$messageType = 'success';
$action = $_POST['action'] ?? 'transfer';
$sourceMember = null;
$destinationMember = null;
$transferSummary = null;
$sourceMembership = trim($_POST['source_membership_no'] ?? '');
$destinationMembership = trim($_POST['destination_membership_no'] ?? '');

if ($sourceMembership !== '') {
    $sourceMember = $shareService->getMemberByMembershipNumber($sourceMembership);
}

if ($destinationMembership !== '') {
    $destinationMember = $shareService->getMemberByMembershipNumber($destinationMembership);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shareCount = (int) ($_POST['shares'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if ($action === 'search') {
        if (empty($sourceMembership) && empty($destinationMembership)) {
            $message = 'Enter at least one membership number to search.';
            $messageType = 'danger';
        } elseif ($sourceMembership !== '' && !$sourceMember) {
            $message = 'Source member not found. Please verify the source membership number.';
            $messageType = 'danger';
        } elseif ($destinationMembership !== '' && !$destinationMember) {
            $message = 'Destination member not found. Please verify the destination membership number.';
            $messageType = 'danger';
        }
    } else {
        if (empty($sourceMembership) || empty($destinationMembership) || $shareCount <= 0) {
            $message = 'Please enter source and destination membership numbers and a valid share quantity.';
            $messageType = 'danger';
        } elseif (!$sourceMember) {
            $message = 'Source member not found. Please verify the source membership number.';
            $messageType = 'danger';
        } elseif (!$destinationMember) {
            $message = 'Destination member not found. Please verify the destination membership number.';
            $messageType = 'danger';
        } else {
            $result = $shareService->transferSharesByMembershipNumber($sourceMember['member_id'], $destinationMembership, $shareCount, $user['user_id'], $note);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
            if ($result['success']) {
                $transferSummary = [
                    'source_membership' => $sourceMembership,
                    'source_name' => $sourceMember['full_name'],
                    'destination_membership' => $destinationMembership,
                    'destination_name' => $destinationMember['full_name'],
                    'shares' => $shareCount,
                    'amount' => $shareCount * SHARE_PRICE,
                    'note' => $note,
                ];
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
    <title>Transfer Shares - <?php echo APP_NAME; ?></title>
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
                    <li class="breadcrumb-item active" aria-current="page">Transfer Shares</li>
                </ol>
            </nav>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h4 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i> Transfer Shares</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-<?php echo htmlspecialchars($messageType); ?>"><?php echo htmlspecialchars($message); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($transferSummary)): ?>
                                <div class="card mb-4 border-success">
                                    <div class="card-header bg-success text-white py-2">
                                        <strong>Transfer Summary</strong>
                                    </div>
                                    <div class="card-body py-3">
                                        <div class="row">
                                            <div class="col-md-6 mb-2">
                                                <strong>Source Member</strong><br>
                                                <?php echo htmlspecialchars($transferSummary['source_name']); ?> <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transferSummary['source_membership']); ?></small>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Destination Member</strong><br>
                                                <?php echo htmlspecialchars($transferSummary['destination_name']); ?> <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($transferSummary['destination_membership']); ?></small>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Shares Transferred</strong><br>
                                                <?php echo htmlspecialchars($transferSummary['shares']); ?>
                                            </div>
                                            <div class="col-md-6 mb-2">
                                                <strong>Total Value</strong><br>
                                                <?php echo htmlspecialchars(formatMoney($transferSummary['amount'])); ?>
                                            </div>
                                            <?php if (!empty($transferSummary['note'])): ?>
                                            <div class="col-12">
                                                <strong>Note</strong><br>
                                                <?php echo htmlspecialchars($transferSummary['note']); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="source_membership_no" class="form-label">Source Member Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="source_membership_no" name="source_membership_no" required value="<?php echo htmlspecialchars($_POST['source_membership_no'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-outline-secondary" name="action" value="search" formnovalidate>
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Enter the source member's number.</div>
                                    <?php if ($sourceMember): ?>
                                        <div class="mt-2 small text-muted">Source Member: <?php echo htmlspecialchars($sourceMember['full_name']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="destination_membership_no" class="form-label">Destination Member Number</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="destination_membership_no" name="destination_membership_no" required value="<?php echo htmlspecialchars($_POST['destination_membership_no'] ?? ''); ?>">
                                        <button type="submit" class="btn btn-outline-secondary" name="action" value="search" formnovalidate>
                                            <i class="bi bi-search"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">Enter the destination member's number.</div>
                                    <?php if ($destinationMember): ?>
                                        <div class="mt-2 small text-muted">Destination Member: <?php echo htmlspecialchars($destinationMember['full_name']); ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <label for="shares" class="form-label">Share Quantity</label>
                                    <input type="number" class="form-control" id="shares" name="shares" min="1" required value="<?php echo htmlspecialchars($_POST['shares'] ?? ''); ?>">
                                    <div class="invalid-feedback">Enter the number of shares to transfer.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="note" class="form-label">Transfer Note</label>
                                    <textarea class="form-control" id="note" name="note" rows="3"><?php echo htmlspecialchars($_POST['note'] ?? ''); ?></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-info">Submit Transfer</button>
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
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
