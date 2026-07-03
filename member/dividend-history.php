<?php
require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

requireMemberLogin();

$member = getMemberData();

$pageTitle = 'Dividend History - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
        <div class="mb-4">
            <h1 class="h3 mb-1">Dividend History</h1>
            <p class="text-muted">Dividend history and upcoming allocations will be displayed here.</p>
        </div>
        <div class="alert alert-info">
            Module under development. Functionality will be available in upcoming release.
        </div>
    </div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
