<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$service = new \SACCO\Services\StandingOrderService();
$results = $service->processDueOrders(date('Y-m-d'), 1); // processed_by=1 (system)

foreach ($results as $r) {
    echo json_encode($r) . PHP_EOL;
}

?>