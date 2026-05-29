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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sourceMembership = trim($_POST['source_membership_no'] ?? '');
    $destinationMembership = trim($_POST['destination_membership_no'] ?? '');
    $shareCount = (int) ($_POST['shares'] ?? 0);
    $note = trim($_POST['note'] ?? '');

    if (empty($sourceMembership) || empty($destinationMembership) || $shareCount <= 0) {
        $message = 'Please enter source and destination membership numbers and a valid share quantity.';
        $messageType = 'danger';
    } else {
        $sourceMember = $shareService->getMemberByMembershipNumber($sourceMembership);
        if (!$sourceMember) {
            $message = 'Source member not found. Please verify the source membership number.';
            $messageType = 'danger';
        } else {
            $result = $shareService->transferSharesByMembershipNumber($sourceMember['member_id'], $destinationMembership, $shareCount, $user['user_id'], $note);
            $message = $result['message'];
            $messageType = $result['success'] ? 'success' : 'danger';
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

                            <form method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="source_membership_no" class="form-label">Source Member Number</label>
                                    <input type="text" class="form-control" id="source_membership_no" name="source_membership_no" required value="<?php echo htmlspecialchars($_POST['source_membership_no'] ?? ''); ?>">
                                    <div class="invalid-feedback">Enter the source member's number.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="destination_membership_no" class="form-label">Destination Member Number</label>
                                    <input type="text" class="form-control" id="destination_membership_no" name="destination_membership_no" required value="<?php echo htmlspecialchars($_POST['destination_membership_no'] ?? ''); ?>">
                                    <div class="invalid-feedback">Enter the destination member's number.</div>
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
