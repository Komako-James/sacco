<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';
require_once 'includes/functions.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();
$db = getDB();

// Get notification statistics
$stmt = $db->query("SELECT COUNT(*) as sms_today FROM sms_queue WHERE DATE(created_at) = CURDATE() AND message_type IN ('sms', 'general')");
$smsStats = $stmt->fetch();

$stmt = $db->query("SELECT COUNT(*) as emails_today FROM sms_queue WHERE DATE(created_at) = CURDATE() AND message_type = 'email'");
$emailStats = $stmt->fetch();

$stmt = $db->query("SELECT COUNT(*) as pending FROM sms_queue WHERE delivery_status = 'pending'");
$pendingStats = $stmt->fetch();

$stmt = $db->query("SELECT COUNT(*) as overdue FROM loans WHERE status = 'disbursed' AND DATE_ADD(DATE_SUB(NOW(), INTERVAL 30 DAY), INTERVAL installment_number MONTH) < CURDATE()");
$overdueStats = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Notifications & Communications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="page-header">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Communication Center</li>
                        </ol>
                    </nav>
                    <h1 class="page-title">Communication Center</h1>
                    <p class="page-subtitle">Monitor outbound messages, delivery status, and key alerts.</p>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card dashboard-card text-white bg-primary border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">SMS Today</h6>
                                    <h3 class="mb-0"><?php echo $smsStats['sms_today'] ?? 0; ?></h3>
                                </div>
                                <i class="bi bi-chat-left-text fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card text-white bg-success border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">Emails Today</h6>
                                    <h3 class="mb-0"><?php echo $emailStats['emails_today'] ?? 0; ?></h3>
                                </div>
                                <i class="bi bi-envelope-open fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card text-white bg-warning border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">Pending Messages</h6>
                                    <h3 class="mb-0"><?php echo $pendingStats['pending'] ?? 0; ?></h3>
                                </div>
                                <i class="bi bi-hourglass-split fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card dashboard-card text-white bg-danger border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="card-title">Overdue Alerts</h6>
                                    <h3 class="mb-0"><?php echo $overdueStats['overdue'] ?? 0; ?></h3>
                                </div>
                                <i class="bi bi-bell-exclamation fs-3"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Message Queue</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">This module tracks all system communications including SMS, emails, and notifications sent to members.</p>
                    <p class="mb-0"><small><strong>Status:</strong> Active</small></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
