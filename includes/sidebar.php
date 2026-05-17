<?php
// Sidebar component
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="col-md-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="../dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['list.php', 'view.php', 'register.php']) ? 'active' : ''; ?>" href="../members/list.php">
                    <i class="bi bi-people"></i> Members
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['list.php', 'apply.php', 'approve.php', 'repay.php']) && strpos($_SERVER['REQUEST_URI'], 'loans') ? 'active' : ''; ?>" href="../loans/list.php">
                    <i class="bi bi-cash-coin"></i> Loans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo in_array($currentPage, ['deposit.php', 'withdraw.php', 'reports.php']) ? 'active' : ''; ?>" href="../savings/deposit.php">
                    <i class="bi bi-piggy-bank"></i> Savings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../reports/portfolio.php">
                    <i class="bi bi-graph-up"></i> Reports
                </a>
            </li>
            <hr>
            <li class="nav-item">
                <a class="nav-link" href="../logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>
