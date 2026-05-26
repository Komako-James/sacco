<?php
require_once __DIR__ . '/../migrations/run_migrations.php';
require_once __DIR__ . '/../scripts/process_standing_orders.php';

echo "Smoke test completed.\n";
?>