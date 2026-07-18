<?php
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}
$pageTitle = $pageTitle ?? 'Member Portal';
$activePage = $activePage ?? basename($_SERVER['PHP_SELF']);
$memberName = $member['full_name'] ?? ($_SESSION['full_name'] ?? 'Member');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle" type="button" aria-label="Toggle sidebar">
        <i class="bi bi-list"></i>
    </button>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top shadow-sm">
        <div class="container-fluid">
            <button class="btn btn-sm btn-outline-light d-lg-none me-2" id="navbarSidebarToggle" type="button" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>
            <a class="navbar-brand fw-bold" href="<?php echo APP_URL; ?>/member/dashboard.php">
                <i class="bi bi-speedometer2 me-2"></i>Member Portal
            </a>
            <div class="d-flex align-items-center ms-auto">
                <span class="text-white me-3 d-none d-md-inline">
                    <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($memberName); ?>
                </span>
                <a href="<?php echo APP_URL; ?>/member/logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content member-main-content">
