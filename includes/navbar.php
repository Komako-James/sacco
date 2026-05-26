<?php
// Navbar component
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="<?php echo APP_URL; ?>/dashboard.php">
            <img src="images/rakai.jpg" class="me-2 logo-sm">
            <div>
                <div class="fw-bold small mb-0">Rakai District SACCO</div>
                <div class="small opacity-75">P.O. Box 21 Kyotera | +256787187693 | +256702617840</div>
            </div>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <form class="d-flex ms-auto me-3 w-100 w-md-50" role="search">
                <input id="global-search" class="form-control form-control-sm me-2" type="search" placeholder="Search members, loans, receipts..." aria-label="Search">
            </form>
            <ul class="navbar-nav align-items-center">
                <li class="nav-item me-2 text-white small d-none d-lg-inline"><?php echo COMPANY_EMAIL; ?></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                        <li><span class="dropdown-item-text">Role: <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $_SESSION['role'] ?? 'guest'))); ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<script>window.APP_BASE_URL = '<?php echo APP_URL; ?>';</script>
