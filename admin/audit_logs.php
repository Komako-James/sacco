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
    <title><?php echo APP_NAME; ?> - Audit Logs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <?php
    $logs = '<div class="card"><div class="card-body"><h5>Recent Audit Events (sample)</h5><ul class="list-group list-group-flush"><li class="list-group-item">2026-06-01 10:12: User admin logged in</li><li class="list-group-item">2026-06-02 09:03: Role Manager updated</li><li class="list-group-item">2026-06-03 14:22: Backup started</li></ul></div></div>';
    renderPlaceholder('Audit Logs', 'bi bi-card-list', 'Demo', 'Immutable audit trail viewer (UI only).', $logs);
    ?>
</body>
</html>
