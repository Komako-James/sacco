<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$shareService = new \SACCO\Services\ShareService();

$membershipNo = trim($_REQUEST['membership_no'] ?? '');
$statement = [];
$member = null;
$error = '';

if ($membershipNo !== '') {
    $member = $shareService->getMemberByMembershipNumber($membershipNo);
    if (!$member) {
        $error = 'Member not found. Please check the membership number.';
    } else {
        $shareHolding = $shareService->getMemberShareHolding($member['member_id']);
        $statement = $shareService->getShareStatement($member['member_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Statement - <?php echo APP_NAME; ?></title>
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
                    <li class="breadcrumb-item active" aria-current="page">Share Statement</li>
                </ol>
            </nav>

            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form method="get" class="row g-3 align-items-end">
                                <div class="col-md-6">
                                    <label for="membership_no" class="form-label">Membership Number</label>
                                    <input type="text" class="form-control" id="membership_no" name="membership_no" value="<?php echo htmlspecialchars($membershipNo); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary">View Statement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($member && !$error): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i> Share Account Statement</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <h6>Member</h6>
                                <p class="mb-1"><?php echo htmlspecialchars($member['full_name']); ?></p>
                                <p class="text-muted"><?php echo htmlspecialchars($member['membership_no']); ?></p>
                            </div>
                            <div class="col-md-4">
                                <h6>Current Shares</h6>
                                <p class="mb-1"><?php echo number_format($shareHolding['shares_owned'] ?? 0); ?></p>
                                <p class="text-muted">Value: <?php echo formatMoney(($shareHolding['shares_owned'] ?? 0) * SHARE_PRICE); ?></p>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="javascript:window.print()" class="btn btn-outline-secondary mt-3"><i class="bi bi-printer me-1"></i> Print</a>
                            </div>
                        </div>

                        <?php if (!empty($statement)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Type</th>
                                            <th>Shares</th>
                                            <th>Amount</th>
                                            <th>Reference</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($statement as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['transaction_date'] ?? $row['created_at'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars(ucfirst($row['transaction_type'] ?? $row['type'] ?? '')); ?></td>
                                                <td><?php echo number_format($row['shares'] ?? 0); ?></td>
                                                <td><?php echo formatMoney($row['amount'] ?? 0); ?></td>
                                                <td><?php echo htmlspecialchars($row['reference_number'] ?? $row['reference'] ?? $row['note'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-0">No share statement entries were found for this member.</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
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
