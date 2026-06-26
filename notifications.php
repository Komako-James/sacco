<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';
require_once 'includes/functions.php';
require_once 'includes/placeholder.php';

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Communication Center</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php
    $extra = '<div class="row">'
        . '<div class="col-md-2"><div class="card text-white bg-primary"><div class="card-body"><h5>SMS Sent Today</h5><h3>1,254</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-success"><div class="card-body"><h5>Emails Sent Today</h5><h3>432</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-warning"><div class="card-body"><h5>Loan Reminders Pending</h5><h3>48</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-danger"><div class="card-body"><h5>Overdue Loan Alerts</h5><h3>12</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-secondary"><div class="card-body"><h5>Queued Messages</h5><h3>27</h3></div></div></div>'
        . '</div>';

    renderPlaceholder('Communication Center', 'bi bi-megaphone', 'Demo', 'This dashboard shows messaging activity (sample values).', $extra);
    ?>
</body>
</html>
