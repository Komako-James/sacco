<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$db = getDB();
$summary = [
    'chart_of_accounts' => 0,
    'journal_entries' => 0,
    'journal_entry_lines' => 0,
    'bank_accounts' => 0,
    'cash_book' => 0,
    'ledger_entries' => 0
];

foreach ($summary as $table => $_) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as cnt FROM {$table}");
        $summary[$table] = (int) $stmt->fetchColumn();
    } catch (Exception $e) {
        $summary[$table] = 'N/A';
    }
}

$recentJournals = [];
try {
    $stmt = $db->query('SELECT journal_entry_id, entry_date, reference_number, description, status FROM journal_entries ORDER BY entry_date DESC LIMIT 5');
    $recentJournals = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentJournals = [];
}

$recentBanks = [];
try {
    $stmt = $db->query('SELECT bank_account_id, bank_name, account_number, branch, currency, is_active FROM bank_accounts ORDER BY created_at DESC LIMIT 5');
    $recentBanks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $recentBanks = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Dashboard - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h4 mb-0">Accounting Dashboard</h1>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Chart of Accounts</h5>
                                <p class="card-text display-6"><?php echo htmlspecialchars((string) $summary['chart_of_accounts']); ?></p>
                                <a href="chart_of_accounts.php" class="btn btn-primary">Manage COA</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Journal Entries</h5>
                                <p class="card-text display-6"><?php echo htmlspecialchars((string) $summary['journal_entries']); ?></p>
                                <a href="journal_entries.php" class="btn btn-primary">View Journals</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Bank Accounts</h5>
                                <p class="card-text display-6"><?php echo htmlspecialchars((string) $summary['bank_accounts']); ?></p>
                                <a href="bank_accounts.php" class="btn btn-primary">Manage Banks</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Cash Book Entries</h5>
                                <p class="card-text display-6"><?php echo htmlspecialchars((string) $summary['cash_book']); ?></p>
                                <a href="bank_accounts.php" class="btn btn-outline-secondary">Record Cash Flow</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Ledger Entries</h5>
                                <p class="card-text display-6"><?php echo htmlspecialchars((string) $summary['ledger_entries']); ?></p>
                                <span class="badge bg-info">Existing ledger engine in use</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Accounting Reports</h5>
                                <p class="card-text">Trial balance, balance sheet, income statement</p>
                                <a href="reports.php" class="btn btn-primary">View Reports</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">Recent Journal Entries</div>
                            <div class="card-body">
                                <?php if (empty($recentJournals)): ?>
                                    <p class="text-muted">No journal entries found.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Reference</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentJournals as $journal): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(formatDate($journal['entry_date'], 'Y-m-d H:i')); ?></td>
                                                    <td><?php echo htmlspecialchars($journal['reference_number']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst($journal['status'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">Recent Bank Accounts</div>
                            <div class="card-body">
                                <?php if (empty($recentBanks)): ?>
                                    <p class="text-muted">No bank accounts configured.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Bank</th>
                                                    <th>Account</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentBanks as $bank): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($bank['account_number']); ?></td>
                                                    <td><?php echo $bank['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
