<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/placeholder.php';

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dynamic Configuration</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php
    $form = '<div class="row">'
        . '<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5>Loan Products</h5><form><div class="mb-2"><input class="form-control" placeholder="Product name"/></div><div class="mb-2"><input class="form-control" placeholder="Interest rate (%)"/></div><button class="btn btn-sm btn-primary" disabled>Save</button></form></div></div></div>'
        . '<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5>Savings Products</h5><form><div class="mb-2"><input class="form-control" placeholder="Account type"/></div><div class="mb-2"><input class="form-control" placeholder="Minimum balance"/></div><button class="btn btn-sm btn-primary" disabled>Save</button></form></div></div></div>'
        . '</div>'
        . '<div class="row"><div class="col-md-6"><div class="card"><div class="card-body"><h5>Share Settings</h5><form><div class="mb-2"><input class="form-control" placeholder="Share price"/></div><button class="btn btn-sm btn-primary" disabled>Save</button></form></div></div></div>'
        . '<div class="col-md-6"><div class="card"><div class="card-body"><h5>SMS / Mobile Money</h5><form><div class="mb-2"><input class="form-control" placeholder="SMS Provider"/></div><div class="mb-2"><input class="form-control" placeholder="Mobile Money Provider"/></div><button class="btn btn-sm btn-primary" disabled>Save</button></form></div></div></div></div>';

    renderPlaceholder('Dynamic Configuration', 'bi bi-sliders', 'Demo', 'Editable-looking configuration sections (no backend).', $form);
    ?>
</body>
</html>
