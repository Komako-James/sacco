<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Roles & Permissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php
    $table = '<div class="card"><div class="card-body"><h5>Roles (sample)</h5><table class="table table-sm"><thead><tr><th>Role</th><th>Description</th><th>Actions</th></tr></thead><tbody><tr><td>Admin</td><td>Full access</td><td><button class="btn btn-sm btn-secondary" disabled>Edit</button></td></tr><tr><td>Manager</td><td>Manage operations</td><td><button class="btn btn-sm btn-secondary" disabled>Edit</button></td></tr><tr><td>Accountant</td><td>Accounting access</td><td><button class="btn btn-sm btn-secondary" disabled>Edit</button></td></tr></tbody></table></div></div>';
    renderPlaceholder('Roles & Permissions', 'bi bi-shield-lock', 'Demo', 'Role and permission management (UI only).', $table);
    ?>
</body>
</html>
