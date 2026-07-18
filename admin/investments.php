<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$service = new \SACCO\Services\InvestmentService();
$filters = [];
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$typeId = trim($_GET['type_id'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$editId = (int)($_GET['edit_id'] ?? 0);
$editInvestment = null;
if ($editId > 0) {
    $editInvestment = $service->getInvestmentById($editId);
}
if ($search) $filters['search'] = $search;
if ($status) $filters['status'] = $status;
if ($typeId) $filters['type_id'] = $typeId;
$offset = ($page - 1) * $perPage;
$investments = $service->getInvestments($filters, $perPage, $offset);
$investmentTypes = $service->getInvestmentTypes();

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investments - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../includes/head_includes.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>
<div class="container mt-4">
    <?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></div><?php unset($_SESSION['flash_success']); endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></div><?php unset($_SESSION['flash_error']); endif; ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>Investments</h3>
        <a href="investments.php#new" class="btn btn-primary">New Investment</a>
    </div>

    <form class="mb-3" method="get">
        <div class="row g-2">
            <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search investments" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option><option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option><option value="matured" <?php echo $status === 'matured' ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option><option value="sold" <?php echo $status === 'sold' ? 'selected' : ''; ?>>Sold</option><option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select></div>
            <div class="col-md-3"><select name="type_id" class="form-select"><option value="">All types</option><?php foreach ($investmentTypes as $type): ?><option value="<?php echo (int)$type['id']; ?>" <?php echo $typeId == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Search</button></div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Type</th>
                    <th>Institution</th>
                    <th>Principal</th>
                    <th>Current Value</th>
                    <th>Investment Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($investments as $inv): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inv['name']); ?></td>
                        <td><?php echo htmlspecialchars($inv['type_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($inv['institution']); ?></td>
                        <td><?php echo formatMoney($inv['principal']); ?></td>
                        <td><?php echo formatMoney($inv['current_value']); ?></td>
                        <td><?php echo htmlspecialchars($inv['investment_date']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($inv['status'])); ?></span></td>
                        <td>
                            <a href="?edit_id=<?php echo (int)$inv['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                            <a href="investment_delete.php?id=<?php echo (int)$inv['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this investment?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <div></div>
        <nav aria-label="Page navigation">
            <ul class="pagination mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Previous</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Next</a></li>
            </ul>
        </nav>
    </div>

    <hr>
    <h4 id="new"><?php echo $editInvestment ? 'Edit Investment' : 'Create Investment'; ?></h4>
    <form action="<?php echo $editInvestment ? 'investment_update.php' : 'investment_save.php'; ?>" method="post">
        <?php echo csrfInputField(); ?>
        <?php if ($editInvestment): ?><input type="hidden" name="id" value="<?php echo (int)$editInvestment['id']; ?>"><?php endif; ?>
        <div class="row">
            <div class="col-md-6 mb-2"><label class="form-label">Name</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($editInvestment['name'] ?? ''); ?>" required></div>
            <div class="col-md-6 mb-2"><label class="form-label">Investment Type</label><select name="type_id" class="form-select"><?php foreach ($investmentTypes as $type): ?><option value="<?php echo (int)$type['id']; ?>" <?php echo (($editInvestment['type_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-6 mb-2"><label class="form-label">Institution</label><input name="institution" class="form-control" value="<?php echo htmlspecialchars($editInvestment['institution'] ?? ''); ?>"></div>
            <div class="col-md-6 mb-2"><label class="form-label">Reference</label><input name="reference" class="form-control" value="<?php echo htmlspecialchars($editInvestment['reference'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Investment Date</label><input type="date" name="investment_date" class="form-control" value="<?php echo htmlspecialchars($editInvestment['investment_date'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Maturity Date</label><input type="date" name="maturity_date" class="form-control" value="<?php echo htmlspecialchars($editInvestment['maturity_date'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Currency</label><select name="currency" class="form-select"><?php $selectedCurrency = normalizeCurrencyCode($editInvestment['currency'] ?? 'UGX'); ?><?php foreach (['UGX','USD','EUR','GBP','KES','TZS','RWF'] as $currencyCode): ?><option value="<?php echo htmlspecialchars($currencyCode); ?>" <?php echo $selectedCurrency === $currencyCode ? 'selected' : ''; ?>><?php echo htmlspecialchars($currencyCode); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-4 mb-2"><label class="form-label">Principal</label><input type="number" step="0.01" name="principal" class="form-control" value="<?php echo htmlspecialchars($editInvestment['principal'] ?? ''); ?>" required></div>
            <div class="col-md-4 mb-2"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.0001" name="interest_rate" class="form-control" value="<?php echo htmlspecialchars($editInvestment['interest_rate'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Expected Return</label><input type="number" step="0.01" name="expected_return" class="form-control" value="<?php echo htmlspecialchars($editInvestment['expected_return'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Current Value</label><input type="number" step="0.01" name="current_value" class="form-control" value="<?php echo htmlspecialchars($editInvestment['current_value'] ?? ''); ?>"></div>
            <div class="col-md-4 mb-2"><label class="form-label">Interest Payment Frequency</label><select name="interest_payment_frequency" class="form-select"><option value="Monthly" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Monthly') ? 'selected' : ''; ?>>Monthly</option><option value="Quarterly" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Quarterly') ? 'selected' : ''; ?>>Quarterly</option><option value="Semi-Annually" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Semi-Annually') ? 'selected' : ''; ?>>Semi-Annually</option><option value="Annually" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Annually') ? 'selected' : ''; ?>>Annually</option><option value="At Maturity" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'At Maturity') ? 'selected' : ''; ?>>At Maturity</option></select></div>
            <div class="col-md-4 mb-2"><label class="form-label">Auto Recognize Interest</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="auto_recognize_interest" value="1" <?php echo !empty($editInvestment['auto_recognize_interest']) ? 'checked' : ''; ?>><label class="form-check-label">Create reminders/entries</label></div></div>
            <div class="col-md-4 mb-2"><label class="form-label">Status</label><select name="status" class="form-select"><option value="draft" <?php echo (($editInvestment['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option><option value="active" <?php echo (($editInvestment['status'] ?? 'draft') === 'active') ? 'selected' : ''; ?>>Active</option><option value="suspended" <?php echo (($editInvestment['status'] ?? 'draft') === 'suspended') ? 'selected' : ''; ?>>Suspended</option><option value="matured" <?php echo (($editInvestment['status'] ?? 'draft') === 'matured') ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo (($editInvestment['status'] ?? 'draft') === 'closed') ? 'selected' : ''; ?>>Closed</option><option value="sold" <?php echo (($editInvestment['status'] ?? 'draft') === 'sold') ? 'selected' : ''; ?>>Sold</option><option value="cancelled" <?php echo (($editInvestment['status'] ?? 'draft') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option></select></div>
            <div class="col-md-4 mb-2"><label class="form-label">Supporting Documents</label><input name="attachments" class="form-control" value="<?php echo htmlspecialchars($editInvestment['attachments'] ?? ''); ?>"></div>
            <div class="col-12 mb-2"><label class="form-label">Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($editInvestment['description'] ?? ''); ?></textarea></div>
        </div>
        <button class="btn btn-success"><?php echo $editInvestment ? 'Update Investment' : 'Create Investment'; ?></button>
    </form>

    <hr>
    <h4>Record Transaction</h4>
    <form action="investment_transaction.php" method="post" class="row g-2">
        <?php echo csrfInputField(); ?>
        <div class="col-md-4"><select name="investment_id" class="form-select" required><?php foreach ($investments as $inv): ?><option value="<?php echo (int)$inv['id']; ?>"><?php echo htmlspecialchars($inv['name']); ?></option><?php endforeach; ?></select></div>
        <div class="col-md-3"><select name="type" class="form-select"><option value="interest_received">Interest Received</option><option value="partial_withdrawal">Partial Withdrawal</option><option value="sale">Sale</option><option value="capital_gain">Capital Gain</option><option value="capital_loss">Capital Loss</option><option value="additional_investment">Additional Investment</option></select></div>
        <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
        <div class="col-md-2"><input type="text" name="description" class="form-control" placeholder="Description"></div>
        <div class="col-md-1"><button class="btn btn-outline-secondary w-100">Save</button></div>
    </form>

</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
