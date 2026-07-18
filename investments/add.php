<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();

$service = new \SACCO\Services\InvestmentService();
$investmentTypes = $service->getInvestmentTypes();
$editInvestment = null;
if (isset($_GET['edit_id']) && (int)$_GET['edit_id'] > 0) {
    $editInvestment = $service->getInvestmentById((int)$_GET['edit_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['flash_error'] = 'Invalid security token.';
        header('Location: add.php');
        exit;
    }
    $uploadedFiles = [];
    if (!empty($_FILES['attachments']['name'][0])) {
        $uploadDir = __DIR__ . '/../uploads/investments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        foreach ($_FILES['attachments']['name'] as $index => $name) {
            if ($_FILES['attachments']['error'][$index] !== UPLOAD_ERR_OK || empty($name)) {
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $safeName = 'investment_' . time() . '_' . $index . '.' . $ext;
            $destination = $uploadDir . $safeName;
            if (move_uploaded_file($_FILES['attachments']['tmp_name'][$index], $destination)) {
                $uploadedFiles[] = $safeName;
            }
        }
    }
    $existingAttachments = trim($_POST['attachments'] ?? '');
    $attachmentsValue = $existingAttachments !== '' ? $existingAttachments : implode(', ', $uploadedFiles);
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'type_id' => $_POST['type_id'] ?? null,
        'institution' => trim($_POST['institution'] ?? ''),
        'reference' => trim($_POST['reference'] ?? ''),
        'investment_date' => $_POST['investment_date'] ?? null,
        'maturity_date' => $_POST['maturity_date'] ?? null,
        'principal' => (float)($_POST['principal'] ?? 0),
        'interest_rate' => (float)($_POST['interest_rate'] ?? 0),
        'expected_return' => (float)($_POST['expected_return'] ?? 0),
        'current_value' => (float)($_POST['current_value'] ?? 0),
        'currency' => trim($_POST['currency'] ?? 'UGX'),
        'status' => trim($_POST['status'] ?? 'active'),
        'description' => trim($_POST['description'] ?? ''),
        'attachments' => $attachmentsValue,
        'interest_payment_frequency' => $_POST['interest_payment_frequency'] ?? 'At Maturity',
        'auto_recognize_interest' => !empty($_POST['auto_recognize_interest']) ? 1 : 0,
        'expected_interest' => (float)($_POST['expected_interest'] ?? $_POST['expected_return'] ?? 0),
        'created_by' => $_SESSION['user_id'] ?? null
    ];
    if (!empty($_POST['id'])) {
        $result = $service->updateInvestment((int)$_POST['id'], $data);
        $_SESSION['flash_success'] = $result['success'] ? 'Investment updated.' : ($result['message'] ?? 'Update failed.');
    } else {
        $result = $service->createInvestment($data);
        $_SESSION['flash_success'] = $result['success'] ? 'Investment created.' : ($result['message'] ?? 'Create failed.');
    }
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Add Investment</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1"><?php echo $editInvestment ? 'Edit Investment' : 'Add Investment'; ?></h1>
                    <p class="text-muted mb-0">Capture investment details, expected returns, maturity dates and interest expectations for Uganda SACCO portfolios.</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
            <?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></div><?php unset($_SESSION['flash_success']); endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></div><?php unset($_SESSION['flash_error']); endif; ?>
            <div class="card">
                <div class="card-body">
                    <form method="post" enctype="multipart/form-data">
                        <?php echo csrfInputField(); ?>
                        <?php if ($editInvestment): ?><input type="hidden" name="id" value="<?php echo (int)$editInvestment['id']; ?>"><?php endif; ?>
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Investment Name</label><input name="name" class="form-control" value="<?php echo htmlspecialchars($editInvestment['name'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Institution</label><input name="institution" class="form-control" value="<?php echo htmlspecialchars($editInvestment['institution'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Reference</label><input name="reference" class="form-control" value="<?php echo htmlspecialchars($editInvestment['reference'] ?? ''); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Investment Type</label><select name="type_id" class="form-select"><?php foreach ($investmentTypes as $type): ?><option value="<?php echo (int)$type['id']; ?>" <?php echo (($editInvestment['type_id'] ?? '') == $type['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Investment Date</label><input type="date" name="investment_date" class="form-control" value="<?php echo htmlspecialchars($editInvestment['investment_date'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Maturity Date</label><input type="date" name="maturity_date" class="form-control" value="<?php echo htmlspecialchars($editInvestment['maturity_date'] ?? ''); ?>"></div>
                            <div class="col-md-3"><label class="form-label">Currency</label><select name="currency" class="form-select"><?php $selectedCurrency = normalizeCurrencyCode($editInvestment['currency'] ?? 'UGX'); ?><?php foreach (['UGX','USD','EUR','GBP','KES','TZS','RWF'] as $currencyCode): ?><option value="<?php echo htmlspecialchars($currencyCode); ?>" <?php echo $selectedCurrency === $currencyCode ? 'selected' : ''; ?>><?php echo htmlspecialchars($currencyCode); ?></option><?php endforeach; ?></select></div>
                            <div class="col-md-3"><label class="form-label">Status</label><select name="status" class="form-select"><option value="active" <?php echo (($editInvestment['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option><option value="matured" <?php echo (($editInvestment['status'] ?? 'active') === 'matured') ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo (($editInvestment['status'] ?? 'active') === 'closed') ? 'selected' : ''; ?>>Closed</option><option value="sold" <?php echo (($editInvestment['status'] ?? 'active') === 'sold') ? 'selected' : ''; ?>>Sold</option><option value="cancelled" <?php echo (($editInvestment['status'] ?? 'active') === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option></select></div>
                            <div class="col-md-4"><label class="form-label">Principal</label><input type="number" step="0.01" name="principal" id="principal" class="form-control" value="<?php echo htmlspecialchars($editInvestment['principal'] ?? ''); ?>" required></div>
                            <div class="col-md-4"><label class="form-label">Interest Rate (%)</label><input type="number" step="0.0001" name="interest_rate" id="interest_rate" class="form-control" value="<?php echo htmlspecialchars($editInvestment['interest_rate'] ?? ''); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Expected Return</label><input type="number" step="0.01" name="expected_return" id="expected_return" class="form-control" value="<?php echo htmlspecialchars($editInvestment['expected_return'] ?? ''); ?>" readonly></div>
                            <div class="col-md-4"><label class="form-label">Current Value</label><input type="number" step="0.01" name="current_value" id="current_value" class="form-control" value="<?php echo htmlspecialchars($editInvestment['current_value'] ?? ''); ?>" <?php echo $editInvestment ? '' : 'readonly'; ?>></div>
                            <div class="col-md-4"><label class="form-label">Interest Payment Frequency</label><select name="interest_payment_frequency" class="form-select"><option value="Monthly" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Monthly') ? 'selected' : ''; ?>>Monthly</option><option value="Quarterly" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Quarterly') ? 'selected' : ''; ?>>Quarterly</option><option value="Semi-Annually" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Semi-Annually') ? 'selected' : ''; ?>>Semi-Annually</option><option value="Annually" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'Annually') ? 'selected' : ''; ?>>Annually</option><option value="At Maturity" <?php echo (($editInvestment['interest_payment_frequency'] ?? 'At Maturity') === 'At Maturity') ? 'selected' : ''; ?>>At Maturity</option></select></div>
                            <div class="col-md-4"><label class="form-label">Auto Recognize Interest</label><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="auto_recognize_interest" value="1" <?php echo !empty($editInvestment['auto_recognize_interest']) ? 'checked' : ''; ?>><label class="form-check-label">Create reminders and prepare accounting entries</label></div></div>
                            <div class="col-md-8"><label class="form-label">Supporting Documents</label><input type="file" name="attachments[]" class="form-control" multiple><small class="text-muted">Uploads are stored in the investments folder and the file names are attached to the record.</small></div>
                            <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control"><?php echo htmlspecialchars($editInvestment['description'] ?? ''); ?></textarea></div>
                        </div>
                        <button class="btn btn-success mt-3">Save Investment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
    (function(){
        const principal = document.getElementById('principal');
        const rate = document.getElementById('interest_rate');
        const investmentDate = document.querySelector('input[name="investment_date"]');
        const maturityDate = document.querySelector('input[name="maturity_date"]');
        const expected = document.getElementById('expected_return');
        const currentValue = document.getElementById('current_value');
        function calculate(){
            const principalValue = parseFloat(principal.value) || 0;
            const rateValue = parseFloat(rate.value) || 0;
            const start = investmentDate.value ? new Date(investmentDate.value) : null;
            const end = maturityDate.value ? new Date(maturityDate.value) : null;
            let value = 0;
            if (principalValue > 0 && rateValue > 0 && start && end && end > start) {
                const days = Math.max(1, Math.floor((end - start) / 86400000));
                const years = days / 365;
                value = principalValue * (rateValue / 100) * years;
            } else if (principalValue > 0 && rateValue > 0) {
                value = principalValue * (rateValue / 100);
            }
            if (expected) expected.value = value.toFixed(2);
            if (currentValue && currentValue.hasAttribute('readonly')) {
                currentValue.value = principalValue.toFixed(2);
            }
        }
        [principal, rate, investmentDate, maturityDate].forEach(el => el && el.addEventListener('input', calculate));
        [principal, rate, investmentDate, maturityDate].forEach(el => el && el.addEventListener('change', calculate));
        calculate();
    })();
    </script>
</body>
</html>
