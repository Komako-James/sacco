<?php
// Enhanced includes/sidebar.php - Beautiful & User-Friendly
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];
$role = $_SESSION['role'] ?? 'viewer';

function hasPermission($permission) {
    global $auth;

    if (isset($auth) && method_exists($auth, 'can')) {
        return $auth->can($permission);
    }

    $permissions = $_SESSION['permissions'] ?? [];
    return in_array($permission, $permissions, true) || ($_SESSION['role'] ?? '') === 'admin';
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
                <img src="<?php echo COMPANY_LOGO; ?>" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>" class="logo-img">
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
                    <li class="has-submenu">
                        <a href="#" class="submenu-link" data-bs-toggle="collapse" data-bs-target="#memberPortalSubmenu">
                            <i class="bi bi-box-arrow-in-right"></i> Member Portal
                        </a>
                        <ul class="submenu collapse" id="memberPortalSubmenu">
                            <li><a href="<?php echo APP_URL; ?>/member/dashboard.php" class="submenu-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/savings.php" class="submenu-link"><i class="bi bi-piggy-bank"></i> Savings</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/shares.php" class="submenu-link"><i class="bi bi-collection"></i> Shares</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/loans.php" class="submenu-link"><i class="bi bi-cash-coin"></i> Loans</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/transactions.php" class="submenu-link"><i class="bi bi-receipt"></i> Transactions</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/statements.php" class="submenu-link"><i class="bi bi-file-earmark-text"></i> Statements</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/dividend-history.php" class="submenu-link"><i class="bi bi-clock-history"></i> Dividend History</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/profile.php" class="submenu-link"><i class="bi bi-person"></i> Profile</a></li>
                            <li><a href="<?php echo APP_URL; ?>/member/security.php" class="submenu-link"><i class="bi bi-shield-lock"></i> Security Settings</a></li>
                        </ul>
                    </li>
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
                    <?php if (hasPermission('loans.approve')): ?>
                    <li><a href="<?php echo APP_URL; ?>/loans/list.php?status=applied" class="submenu-link">
                        <i class="bi bi-check-circle"></i> Approvals</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo APP_URL; ?>/loans/list.php" class="submenu-link">
                        <i class="bi bi-arrow-repeat"></i> Repayments</a></li>
                    <li><a href="<?php echo APP_URL; ?>/loans/calculator.php" class="submenu-link">
                        <i class="bi bi-calculator"></i> Calculator</a></li>
                </ul>
            </li>

            <!-- Shares Module -->
            <li class="menu-item has-submenu">
                <a href="#"
                   class="menu-link <?php echo isActiveMenu('/shares') ? 'active' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#sharesSubmenu">
                    <i class="bi bi-diagram-3"></i>
                    <span>Shares</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/shares') ? 'show' : ''; ?>" id="sharesSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/shares/index.php" class="submenu-link">
                        <i class="bi bi-collection"></i> Share Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/buy.php" class="submenu-link">
                        <i class="bi bi-cart-plus"></i> Buy Shares</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/transfer.php" class="submenu-link">
                        <i class="bi bi-arrow-left-right"></i> Transfer Shares</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/holdings.php" class="submenu-link">
                        <i class="bi bi-people-fill"></i> Share Holdings</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/statement.php" class="submenu-link">
                        <i class="bi bi-file-earmark-text"></i> Share Statements</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/reports.php" class="submenu-link">
                        <i class="bi bi-graph-up"></i> Share Reports</a></li>
                    <li><a href="<?php echo APP_URL; ?>/shares/history.php" class="submenu-link">
                        <i class="bi bi-clock-history"></i> Share History</a></li>
                    <?php if ($role === 'admin' || $role === 'manager' || $role === 'accountant'): ?>
                    <li><a href="<?php echo APP_URL; ?>/shares/adjust.php" class="submenu-link">
                        <i class="bi bi-pencil-square"></i> Share Adjustments</a></li>
                    <?php endif; ?>
                </ul>
            </li>

            <!-- Batches Module -->
            <li class="menu-item has-submenu">
                <a href="#"
                   class="menu-link <?php echo isActiveMenu('/batches') ? 'active' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#batchesSubmenu">
                    <i class="bi bi-upload"></i>
                    <span>Batches</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/batches') ? 'show' : ''; ?>" id="batchesSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/batches/upload.php" class="submenu-link">
                        <i class="bi bi-file-earmark-arrow-up"></i> Upload Batch</a></li>
                    <li><a href="<?php echo APP_URL; ?>/batches/list.php" class="submenu-link">
                        <i class="bi bi-list-check"></i> View Batches</a></li>
                </ul>
            </li>
            <!-- Bank Reconciliation Module -->
            <li class="menu-item has-submenu">
                <a href="#"
                   class="menu-link <?php echo isActiveMenu('/reconciliation') ? 'active' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#reconciliationSubmenu">
                    <i class="bi bi-bank"></i>
                    <span>Reconciliation</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/reconciliation') ? 'show' : ''; ?>" id="reconciliationSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/reconciliation/upload.php" class="submenu-link">
                        <i class="bi bi-file-earmark-arrow-up"></i> Upload Statement</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reconciliation/matching.php" class="submenu-link">
                        <i class="bi bi-arrow-left-right"></i> Match Transactions</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reconciliation/reports.php" class="submenu-link">
                        <i class="bi bi-file-earmark-bar-graph"></i> Reports</a></li>
                </ul>
            </li>
            <!-- Standing Orders Module -->
            <li class="menu-item has-submenu">
                <a href="#"
                   class="menu-link <?php echo isActiveMenu('/standing-orders') ? 'active' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#standingOrdersSubmenu">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Standing Orders</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/standing-orders') ? 'show' : ''; ?>" id="standingOrdersSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/standing-orders/list.php" class="submenu-link">
                        <i class="bi bi-list-ul"></i> All Orders</a></li>
                    <li><a href="<?php echo APP_URL; ?>/standing-orders/create.php" class="submenu-link">
                        <i class="bi bi-plus-circle"></i> Create New</a></li>
                    <li><a href="<?php echo APP_URL; ?>/standing-orders/reconcile.php" class="submenu-link">
                        <i class="bi bi-check2-square"></i> Reconcile</a></li>
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
                    <li><a href="<?php echo APP_URL; ?>/reports/revenue_analysis.php" class="submenu-link">
                        <i class="bi bi-bar-chart-line"></i> Revenue Analysis</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/expense_analysis.php" class="submenu-link">
                        <i class="bi bi-graph-down"></i> Expense Analysis</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/profitability.php" class="submenu-link">
                        <i class="bi bi-cash-stack"></i> Profitability</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/executive_dashboard.php" class="submenu-link">
                        <i class="bi bi-speedometer2"></i> Executive Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/reports/transactions.php" class="submenu-link">
                        <i class="bi bi-receipt"></i> Transactions</a></li>
                </ul>
            </li>

            <!-- Demonstration Modules -->
            <li class="menu-item has-submenu">
                <a href="#" class="menu-link <?php echo isActiveMenu('/investments') ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#investmentsSubmenu">
                    <i class="bi bi-graph-up-arrow"></i>
                    <span>Investments</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/investments') ? 'show' : ''; ?>" id="investmentsSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/investments/index.php" class="submenu-link"><i class="bi bi-speedometer2"></i> Investment Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/investments/add.php" class="submenu-link"><i class="bi bi-plus-circle"></i> Add Investment</a></li>
                    <li><a href="<?php echo APP_URL; ?>/investments/portfolio.php" class="submenu-link"><i class="bi bi-collection"></i> Portfolio</a></li>
                    <li><a href="<?php echo APP_URL; ?>/investments/returns.php" class="submenu-link"><i class="bi bi-percent"></i> Returns</a></li>
                    <li><a href="<?php echo APP_URL; ?>/investments/reports.php" class="submenu-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                </ul>
            </li>

            <li class="menu-item has-submenu">
                <a href="#" class="menu-link <?php echo isActiveMenu('/dividends') ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#dividendsSubmenu">
                    <i class="bi bi-cash-stack"></i>
                    <span>Dividends</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/dividends') ? 'show' : ''; ?>" id="dividendsSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/dividends/index.php" class="submenu-link"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/dividends/calculate.php" class="submenu-link"><i class="bi bi-calculator"></i> Calculate Dividends</a></li>
                    <li><a href="<?php echo APP_URL; ?>/dividends/distribute.php" class="submenu-link"><i class="bi bi-send"></i> Distribute Dividends</a></li>
                    <li><a href="<?php echo APP_URL; ?>/dividends/history.php" class="submenu-link"><i class="bi bi-clock-history"></i> Dividend History</a></li>
                </ul>
            </li>

            <li class="menu-item has-submenu">
                <a href="#" class="menu-link <?php echo isActiveMenu('/expenses') ? 'active' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#expensesSubmenu">
                    <i class="bi bi-wallet2"></i>
                    <span>Expenses</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/expenses') ? 'show' : ''; ?>" id="expensesSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/expenses/index.php" class="submenu-link"><i class="bi bi-speedometer2"></i> Expense Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/expenses/create.php" class="submenu-link"><i class="bi bi-plus-circle"></i> Record Expense</a></li>
                    <li><a href="<?php echo APP_URL; ?>/expenses/categories.php" class="submenu-link"><i class="bi bi-tags"></i> Categories</a></li>
                    <li><a href="<?php echo APP_URL; ?>/expenses/reports.php" class="submenu-link"><i class="bi bi-graph-up"></i> Reports</a></li>
                </ul>
            </li>

            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/notifications.php"
                   class="menu-link <?php echo $currentPage === 'notifications.php' ? 'active' : ''; ?>">
                    <i class="bi bi-megaphone"></i>
                    <span>Communication Center</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?php echo APP_URL; ?>/mobile-services.php"
                   class="menu-link <?php echo $currentPage === 'mobile-services.php' ? 'active' : ''; ?>">
                    <i class="bi bi-wallet-fill"></i>
                    <span>Payments &amp; Mobile Money</span>
                </a>
            </li>

            <?php if (hasPermission('accounting.view')): ?>
            <li class="menu-item has-submenu">
                <a href="#"
                   class="menu-link <?php echo isActiveMenu('/accounting') ? 'active' : ''; ?>"
                   data-bs-toggle="collapse"
                   data-bs-target="#accountingSubmenu">
                    <i class="bi bi-calculator"></i>
                    <span>Accounting</span>
                    <i class="bi bi-chevron-down arrow"></i>
                </a>
                <ul class="submenu collapse <?php echo isActiveMenu('/accounting') ? 'show' : ''; ?>" id="accountingSubmenu">
                    <li><a href="<?php echo APP_URL; ?>/accounting/dashboard.php" class="submenu-link">
                        <i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/chart_of_accounts.php" class="submenu-link">
                        <i class="bi bi-book"></i> Chart of Accounts</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/journal_entries.php" class="submenu-link">
                        <i class="bi bi-journal-text"></i> Journal Entries</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/bank_accounts.php" class="submenu-link">
                        <i class="bi bi-bank"></i> Bank Accounts</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/trial_balance.php" class="submenu-link">
                        <i class="bi bi-table"></i> Trial Balance</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/income_statement.php" class="submenu-link">
                        <i class="bi bi-file-earmark-text"></i> Income Statement</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/balance_sheet.php" class="submenu-link">
                        <i class="bi bi-journal-bookmark"></i> Balance Sheet</a></li>
                    <li><a href="<?php echo APP_URL; ?>/accounting/reports.php" class="submenu-link">
                        <i class="bi bi-graph-up"></i> Accounting Reports</a></li>
                </ul>
            </li>
            <?php endif; ?>

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
                    <li><a href="<?php echo APP_URL; ?>/admin/dynamic_configuration.php" class="submenu-link">
                        <i class="bi bi-sliders"></i> Dynamic Configuration</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admin/roles_permissions.php" class="submenu-link">
                        <i class="bi bi-shield-lock"></i> Roles & Permissions</a></li>
                    <li><a href="<?php echo APP_URL; ?>/admin/audit_logs.php" class="submenu-link">
                        <i class="bi bi-card-list"></i> Audit Logs</a></li>
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
