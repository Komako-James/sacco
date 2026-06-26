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
    <title><?php echo APP_NAME; ?> - Expenses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    <?php renderPlaceholder('Expenses', 'bi bi-wallet2', 'Coming Soon', 'Expense recording and reporting.', '<a href="expenses/index.php" class="btn btn-primary">Open Expenses Module</a>'); ?>
</body>
</html>
