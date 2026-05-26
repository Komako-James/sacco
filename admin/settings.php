<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../app/Services/SettingsService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$settingsService = new \SACCO\Services\SettingsService();
$error = '';
$success = '';
$settings = $settingsService->getAllSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['setting_key'] ?? '';
    $value = $_POST['setting_value'] ?? '';
    $label = $_POST['setting_label'] ?? '';
    $group = $_POST['setting_group'] ?? 'general';

    if (empty($key) || empty($value) || empty($label)) {
        $error = 'Setting key, label, and value are required.';
    } else {
        $settingsService->saveSetting($key, $value, $label, $group);
        $success = 'Setting saved successfully.';
        $settings = $settingsService->getAllSettings();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="pt-4 pb-3 border-bottom">
                    <h1 class="h2">System Settings</h1>
                    <p class="text-muted">Dynamic system configuration for interest rates, fees, SMS templates, and account rules.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success mt-4"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card mt-4 mb-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3 needs-validation" novalidate>
                            <div class="col-md-4">
                                <label for="setting_key" class="form-label">Setting Key</label>
                                <input type="text" id="setting_key" name="setting_key" class="form-control" required>
                                <div class="invalid-feedback">Setting key is required.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="setting_label" class="form-label">Label</label>
                                <input type="text" id="setting_label" name="setting_label" class="form-control" required>
                                <div class="invalid-feedback">Label is required.</div>
                            </div>
                            <div class="col-md-4">
                                <label for="setting_group" class="form-label">Group</label>
                                <input type="text" id="setting_group" name="setting_group" class="form-control" value="general">
                            </div>
                            <div class="col-12">
                                <label for="setting_value" class="form-label">Value</label>
                                <textarea id="setting_value" name="setting_value" class="form-control" rows="3" required></textarea>
                                <div class="invalid-feedback">Value is required.</div>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-primary">Save Setting</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">Existing Settings</div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Key</th>
                                    <th>Label</th>
                                    <th>Group</th>
                                    <th>Value</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($settings as $setting): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($setting['setting_key']); ?></td>
                                        <td><?php echo htmlspecialchars($setting['label']); ?></td>
                                        <td><?php echo htmlspecialchars($setting['group']); ?></td>
                                        <td><pre class="m-0 small text-wrap"><?php echo htmlspecialchars($setting['setting_value']); ?></pre></td>
                                        <td><?php echo htmlspecialchars($setting['updated_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
