<?php
function renderPlaceholder($title, $icon = 'bi bi-gear', $status = 'Coming Soon', $description = null, $extraHtml = '') {
    if (!$description) {
        $description = "This module is under development and will be available in an upcoming release.";
    }
    ?>
    <!---- Placeholder block ---->
    <div class="container-fluid py-4">
        <div class="d-flex align-items-center mb-3">
            <i class="<?php echo htmlspecialchars($icon); ?> fs-1 me-3"></i>
            <div>
                <h1 class="h3 mb-1"><?php echo htmlspecialchars($title); ?></h1>
                <div>
                    <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($status); ?></span>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <p class="mb-0 text-muted"><?php echo htmlspecialchars($description); ?></p>
            </div>
        </div>

        <?php if ($extraHtml): ?>
            <div class="mb-3"><?php echo $extraHtml; ?></div>
        <?php endif; ?>
    </div>
    <?php
}

?>
