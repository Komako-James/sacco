<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/DividendService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$svc = new \SACCO\Services\DividendService();
$declsStmt = \Database::getInstance()->getConnection()->query('SELECT * FROM dividend_declarations ORDER BY created_at DESC LIMIT 200');
$declarations = $declsStmt->fetchAll(PDO::FETCH_ASSOC);
$pendingPaymentsStmt = \Database::getInstance()->getConnection()->prepare('SELECT dp.id, dp.member_id, dp.declaration_id, dp.net_dividend, dp.status, dd.name AS declaration_name, m.full_name FROM dividend_payments dp JOIN dividend_declarations dd ON dp.declaration_id = dd.id LEFT JOIN members m ON dp.member_id = m.member_id WHERE dp.status != ? ORDER BY dp.id DESC LIMIT 100');
$pendingPaymentsStmt->execute(['paid']);
$pendingPayments = $pendingPaymentsStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Dividends - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../includes/head_includes.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>
<div class="container mt-4">
    <?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></div><?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></div><?php unset($_SESSION['flash_error']); endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Dividends</h3>
        <a href="dividends.php#new" class="btn btn-primary">New Declaration</a>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Name</th><th>Year</th><th>Rate</th><th>Declared</th><th>Payment Date</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
                <?php foreach ($declarations as $d): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($d['name']); ?></td>
                        <td><?php echo htmlspecialchars($d['financial_year']); ?></td>
                        <td><?php echo htmlspecialchars($d['rate']); ?></td>
                        <td><?php echo htmlspecialchars($d['declaration_date']); ?></td>
                        <td><?php echo htmlspecialchars($d['payment_date']); ?></td>
                        <td><span class="badge bg-info"><?php echo htmlspecialchars($d['status']); ?></span></td>
                        <td>
                            <form method="post" action="dividend_calculate.php" style="display:inline">
                                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                <button class="btn btn-sm btn-secondary">Calculate</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>
    <h4>Pending Dividend Payments</h4>
    <div class="table-responsive mb-3">
        <table class="table table-sm table-striped">
            <thead><tr><th>Member</th><th>Declaration</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                <?php foreach ($pendingPayments as $payment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($payment['full_name'] ?? $payment['member_id']); ?></td>
                        <td><?php echo htmlspecialchars($payment['declaration_name']); ?></td>
                        <td><?php echo formatMoney($payment['net_dividend']); ?></td>
                        <td><?php echo htmlspecialchars($payment['status']); ?></td>
                        <td>
                            <form method="post" action="dividend_pay.php" style="display:inline">
                                <?php echo csrfInputField(); ?>
                                <input type="hidden" name="id" value="<?php echo (int)$payment['id']; ?>">
                                <button class="btn btn-sm btn-success">Mark Paid</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <hr>
    <h4 id="new">Declare Dividend</h4>
    <form action="dividend_save.php" method="post">
        <?php echo csrfInputField(); ?>
        <div class="row">
            <div class="col-md-6 mb-2"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
            <div class="col-md-3 mb-2"><label class="form-label">Financial Year</label><input name="financial_year" class="form-control" placeholder="2025/2026"></div>
            <div class="col-md-3 mb-2"><label class="form-label">Declaration Date</label><input type="date" name="declaration_date" class="form-control"></div>
            <div class="col-md-3 mb-2"><label class="form-label">Payment Date</label><input type="date" name="payment_date" class="form-control"></div>
            <div class="col-md-3 mb-2"><label class="form-label">Rate (per share)</label><input type="number" step="0.0001" name="rate" class="form-control"></div>
            <div class="col-md-3 mb-2"><label class="form-label">Source</label><select name="source" class="form-control"><option value="share_capital">Share Capital</option><option value="investment_income">Investment Income</option></select></div>
            <div class="col-12 mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
        </div>
        <button class="btn btn-success">Declare Dividend</button>
    </form>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
