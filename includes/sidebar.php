<?php
// Enhanced includes/sidebar.php - Beautiful & User-Friendly
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];
$role = $_SESSION['role'] ?? 'viewer';
$permissions = $_SESSION['permissions'] ?? [];

function hasPermission($permission) {
    global $permissions;
    return in_array($permission, $permissions) || $_SESSION['role'] === 'admin';
}

function isActiveMenu($path) {
    return strpos($_SERVER['REQUEST_URI'], $path) !== false;
}
?>

<nav class="sidebar" id="sidebar">
    <div class="sidebar-content">
        <!-- Brand Logo -->
        <div class="sidebar-brand">
            <div class="brand-logo">
                <img src="<?php echo APP_URL; ?>/assets/images/logo.svg" alt="SACCO" class="logo-img">
            </div>
            <div class="brand-text">
                <h5 class="brand-name">SACCO</h5>
                <small class="brand-subtitle">Management System</small>
            </div>
        </div>

        <!-- User Info -->
        <div class="user-profile">
            <div class="user-avatar">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="user-info">
                <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></div>
                <div class="user-role"><?php echo ucfirst($role); ?></div>
            </div>
        </div>

        <!-- Main Navigation -->
        <ul class="sidebar-menu">
            <!-- Dashboard -->
            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/dashboard.php" 
                   class="menu-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- Members Module -->
            <li class="menu-item has-submenu">
                <a href="#" 
                   class="menu-link <?php echo isActiveMenu('/members') ? 'active' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#membersSubmenu">
                    <i class="bi bi-people"></i>
                    <span>Members</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/members') ? 'show' : ''; ?>" id="membersSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/members/register.php" class="submenu-link">
                        <i class="bi bi-person-plus"></i> Register Member</a></li>
                    <li><a href="<?php echo APP_URL; ?>/members/list.php" class="submenu-link">
                        <i class="bi bi-list-ul"></i> All Members</a></li>
                    <li><a href="<?php echo APP_URL; ?>/members/statements.php" class="submenu-link">
                        <i class="bi bi-receipt"></i> Statements</a></li>
                </ul>
            </li>

            <!-- Savings Module -->
            <li class="menu-item has-submenu">
                <a href="#" 
                   class="menu-link <?php echo isActiveMenu('/savings') ? 'active' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#savingsSubmenu">
                    <i class="bi bi-piggy-bank"></i>
                    <span>Savings</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/savings') ? 'show' : ''; ?>" id="savingsSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/savings/open.php" class="submenu-link">
                        <i class="bi bi-plus-circle"></i> Open Account</a></li>
                    <li><a href="<?php echo APP_URL; ?>/savings/deposit.php" class="submenu-link">
                        <i class="bi bi-arrow-down-circle"></i> Make Deposit</a></li>
                    <li><a href="<?php echo APP_URL; ?>/savings/withdraw.php" class="submenu-link">
                        <i class="bi bi-arrow-up-circle"></i> Withdrawal</a></li>
                    <li><a href="<?php echo APP_URL; ?>/savings/accounts.php" class="submenu-link">
                        <i class="bi bi-bank"></i> All Accounts</a></li>
                    <li><a href="<?php echo APP_URL; ?>/savings/reports.php" class="submenu-link">
                        <i class="bi bi-graph-up"></i> Reports</a></li>
                </ul>
            </li>

            <!-- Loans Module -->
            <li class="menu-item has-submenu">
                <a href="#" 
                   class="menu-link <?php echo isActiveMenu('/loans') ? 'active' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#loansSubmenu">
                    <i class="bi bi-cash-coin"></i>
                    <span>Loans</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/loans') ? 'show' : ''; ?>" id="loansSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/loans/apply.php" class="submenu-link">
                        <i class="bi bi-file-earmark-plus"></i> Apply Loan</a></li>
                    <li><a href="<?php echo APP_URL; ?>/loans/list.php" class="submenu-link">
                        <i class="bi bi-list-ul"></i> All Loans</a></li>
                    <?php if (hasPermission('approve_loans')): ?>
                    <li><a href="<?php echo APP_URL; ?>/loans/approve.php" class="submenu-link">
                        <i class="bi bi-check-circle"></i> Approvals</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo APP_URL; ?>/loans/repay.php" class="submenu-link">
                        <i class="bi bi-arrow-repeat"></i> Repayments</a></li>
                    <li><a href="<?php echo APP_URL; ?>/loans/calculator.php" class="submenu-link">
                        <i class="bi bi-calculator"></i> Calculator</a></li>
                </ul>
            </li>

            <!-- Reports Module -->
            <li class="menu-item has-submenu">
                <a href="#" 
                   class="menu-link <?php echo isActiveMenu('/reports') ? 'active' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#reportsSubmenu">
                    <i class="bi bi-graph-up"></i>
                    <span>Reports</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/reports') ? 'show' : ''; ?>" id="reportsSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/reports/portfolio.php" class="submenu-link">
                        <i class="bi bi-briefcase"></i> Portfolio</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/members_list.php" class="submenu-link">
                        <i class="bi bi-people"></i> Members</a></li>
                    <li><a href="<?php echo APP_URL; ?>/savings/reports.php" class="submenu-link">
                        <i class="bi bi-piggy-bank"></i> Savings</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/transactions.php" class="submenu-link">
                        <i class="bi bi-receipt"></i> Transactions</a></li>
                </ul>
            </li>

            <!-- Administration (Admin Only) -->
            <?php if ($role === 'admin'): ?>
            <li class="menu-divider">
                <span>Administration</span>
            </li>
            <li class="menu-item has-submenu">
                <a href="#" 
                   class="menu-link <?php echo isActiveMenu('/admin') ? 'active' : ''; ?>" 
                   data-bs-toggle="collapse" 
                   data-bs-target="#adminSubmenu">
                    <i class="bi bi-gear"></i>
                    <span>System</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/admin') ? 'show' : ''; ?>" id="adminSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/admin/users/index.php" class="submenu-link">
                        <i class="bi bi-people-fill"></i> Users</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admin/settings.php" class="submenu-link">
                        <i class="bi bi-sliders"></i> Settings</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admin/backup.php" class="submenu-link">
                        <i class="bi bi-download"></i> Backup</a></li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Bottom Actions -->
        <div class="sidebar-footer">
            <a href="<?php echo APP_URL; ?>/profile.php" class="footer-link">
                <i class="bi bi-person-gear"></i>
                <span>Profile</span>
            </a>
            <a href="<?php echo APP_URL; ?>/logout.php" class="footer-link logout">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>
