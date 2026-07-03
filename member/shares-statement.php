<?php
/**
 * Member Share Statement
 */

require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

requireMemberLogin();
$member = getMemberData();
$shareService = new \SACCO\Services\ShareService();

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$message = '';
$downloadLink = null;

$statementRows = $shareService->getShareStatement($member['member_id'], $startDate, $endDate);
$shareHolding = $shareService->getMemberShareHolding($member['member_id']);
$totalShares = $shareHolding['shares_owned'] ?? 0;
$totalValue = $totalShares * SHARE_PRICE;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_statement'])) {
    $html = '<h2>Share Statement for ' . htmlspecialchars($member['full_name']) . '</h2>';
    $html .= '<p>Period: ' . htmlspecialchars($startDate) . ' to ' . htmlspecialchars($endDate) . '</p>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" width="100%">';
    $html .= '<thead><tr><th>Date</th><th>Type</th><th>Shares</th><th>Amount</th><th>Reference</th><th>Counterparty</th><th>Description</th></tr></thead><tbody>';

    foreach ($statementRows as $row) {
        $html .= '<tr>' .
            '<td>' . htmlspecialchars(date('Y-m-d H:i', strtotime($row['transaction_date']))) . '</td>' .
            '<td>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $row['transaction_type']))) . '</td>' .
            '<td>' . htmlspecialchars($row['shares']) . '</td>' .
            '<td>' . htmlspecialchars(number_format($row['amount'], 2)) . '</td>' .
            '<td>' . htmlspecialchars($row['reference_number']) . '</td>' .
            '<td>' . htmlspecialchars($row['related_member_name'] ?? '-') . '</td>' .
            '<td>' . htmlspecialchars($row['description']) . '</td>' .
            '</tr>';
    }
    $html .= '</tbody></table>';
    $html .= '<p><strong>Total Shares:</strong> ' . htmlspecialchars($totalShares) . '</p>';
    $html .= '<p><strong>Total Value:</strong> ' . htmlspecialchars(formatMoney($totalValue)) . '</p>';

    $result = generatePDF($html, 'share_statement_' . $member['member_id'] . '_' . date('YmdHis'));
    if ($result['success']) {
        $downloadLink = $result['download_url'];
        $message = 'Statement generated successfully. Use the link below to download.';
    } else {
        $message = 'Unable to generate statement: ' . $result['message'];
    }
}

$pageTitle = 'Share Statement - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
            <a href="shares.php" class="btn btn-sm btn-outline-secondary mb-3"><i class="bi bi-arrow-left"></i> Back to Shares</a>
            <h2>Share Statement</h2>
            <p class="text-muted">View and download your share purchase and transfer history.</p>

            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($downloadLink): ?>
                <div class="mb-3">
                    <a href="<?php echo htmlspecialchars($downloadLink); ?>" class="btn btn-success" target="_blank"><i class="bi bi-download"></i> Download Statement</a>
                </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-4">
                            <strong>Total Shares</strong>
                            <p><?php echo htmlspecialchars($totalShares); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Total Share Value</strong>
                            <p><?php echo htmlspecialchars(formatMoney($totalValue)); ?></p>
                        </div>
                        <div class="col-md-4">
                            <strong>Period</strong>
                            <p><?php echo htmlspecialchars($startDate); ?> &mdash; <?php echo htmlspecialchars($endDate); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <button type="submit" name="download_statement" value="1" class="btn btn-success">Download</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Share Transactions</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($statementRows)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Type</th>
                                        <th>Shares</th>
                                        <th>Amount</th>
                                        <th>Reference</th>
                                        <th>Counterparty</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statementRows as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('Y-m-d H:i', strtotime($row['transaction_date']))); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['transaction_type']))); ?></td>
                                            <td><?php echo htmlspecialchars($row['shares']); ?></td>
                                            <td><?php echo htmlspecialchars(formatMoney($row['amount'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['reference_number']); ?></td>
                                            <td><?php echo htmlspecialchars($row['related_member_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No share transactions found for this period.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
