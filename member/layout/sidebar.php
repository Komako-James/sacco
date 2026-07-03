<?php
if (!defined('APP_URL')) {
    require_once __DIR__ . '/../../config/constants.php';
}
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

function memberNavActive($path) {
    global $currentPath;
    return strpos($currentPath, $path) !== false ? ' active' : '';
}
?>
<aside class="sidebar" id="memberSidebar">
    <div class="sidebar-content">
        <div class="sidebar-brand">
            <div class="brand-logo">
                <img src="<?php echo COMPANY_LOGO; ?>" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>" class="logo-img">
            </div>
            <div class="brand-text">
                <h5 class="brand-name">Member Portal</h5>
                <small class="brand-subtitle">SACCO Access</small>
            </div>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Member'); ?></div>
                <div class="user-role">Member</div>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/dashboard.php" class="menu-link<?php echo memberNavActive('/member/dashboard.php'); ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/savings.php" class="menu-link<?php echo memberNavActive('/member/savings.php'); ?>">
                    <i class="bi bi-piggy-bank"></i>
                    <span>Savings</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/shares.php" class="menu-link<?php echo memberNavActive('/member/shares.php'); ?>">
                    <i class="bi bi-collection"></i>
                    <span>Shares</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/loans.php" class="menu-link<?php echo memberNavActive('/member/loans.php'); ?>">
                    <i class="bi bi-cash-coin"></i>
                    <span>Loans</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/transactions.php" class="menu-link<?php echo memberNavActive('/member/transactions.php'); ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Transactions</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/statements.php" class="menu-link<?php echo memberNavActive('/member/statements.php'); ?>">
                    <i class="bi bi-file-earmark-text"></i>
                    <span>Statements</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/repayment-schedule.php" class="menu-link<?php echo memberNavActive('/member/repayment-schedule.php'); ?>">
                    <i class="bi bi-calendar-event"></i>
                    <span>Repayment Schedule</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/profile.php" class="menu-link<?php echo memberNavActive('/member/profile.php'); ?>">
                    <i class="bi bi-person"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/security.php" class="menu-link<?php echo memberNavActive('/member/security.php'); ?>">
                    <i class="bi bi-shield-lock"></i>
                    <span>Security</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/member/dividend-history.php" class="menu-link<?php echo memberNavActive('/member/dividend-history.php'); ?>">
                    <i class="bi bi-clock-history"></i>
                    <span>Dividend History</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
