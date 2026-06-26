<?php
require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal - Dividend History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <h1 class="mb-3">Member Portal - Dividend History</h1>
            <div class="alert alert-info">
                Module under development. Functionality will be available in upcoming release.
            </div>
        </div>
    </div>
</body>
</html>
