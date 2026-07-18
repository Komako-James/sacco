<?php
// Navbar component
?>
<nav class="navbar navbar-expand-lg navbar-dark sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/dashboard.php">
            <img src="<?php echo COMPANY_LOGO; ?>" class="me-2 logo-sm" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>">
            <div>
                <div class="fw-bold small mb-0">Rakai District SACCO</div>
                <div class="small opacity-75"><?php echo COMPANY_ADDRESS; ?> • <?php echo COMPANY_PHONE; ?></div>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <form class="d-flex ms-auto me-3 w-100 w-lg-50" role="search">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-0"><i class="bi bi-search"></i></span>
                    <input id="global-search" class="form-control border-0" type="search" placeholder="Search members, loans, receipts..." aria-label="Search">
                </div>
            </form>
            <ul class="navbar-nav align-items-center">
                <li class="nav-item me-2 text-white small d-none d-lg-inline">
                    <i class="bi bi-envelope-open me-1"></i> <?php echo COMPANY_EMAIL; ?>
                </li>
                <li class="nav-item me-2">
                    <a class="nav-link position-relative" href="<?php echo APP_URL; ?>/notifications.php" aria-label="Notifications">
                        <i class="bi bi-bell-fill"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark">3</span>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle fs-5 me-2"></i>
                        <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><span class="dropdown-item-text">Role: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role'] ?? 'guest'))); ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile.php"><i class="bi bi-person-gear me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>window.APP_BASE_URL = '<?php echo APP_URL; ?>';</script>
