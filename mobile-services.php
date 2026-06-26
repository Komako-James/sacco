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
    <title><?php echo APP_NAME; ?> - Payments & Mobile Money</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php
    $cards = '<div class="row">'
        . '<div class="col-md-2"><div class="card text-white bg-primary"><div class="card-body"><h5>MTN Collections</h5><h3>8,432</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-success"><div class="card-body"><h5>Airtel Collections</h5><h3>5,129</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-info"><div class="card-body"><h5>Transactions Today</h5><h3>3,210</h3></div></div></div>'
        . '<div class="col-md-2"><div class="card text-white bg-danger"><div class="card-body"><h5>Failed Transactions</h5><h3>24</h3></div></div></div>'
        . '<div class="col-md-3"><div class="card text-dark bg-light"><div class="card-body"><h5>Pending Reconciliations</h5><h3>58</h3></div></div></div>'
        . '</div>'
        . '<div class="card mt-4"><div class="card-body"><h5>Future Integrations</h5><ul><li>MTN Mobile Money</li><li>Airtel Money</li><li>Bank Integrations</li><li>Payment Gateway APIs</li></ul></div></div>';

    renderPlaceholder('Payments & Mobile Money', 'bi bi-wallet-fill', 'Demo', 'Payments and mobile money activity (sample values).', $cards);
    ?>
</body>
</html>
