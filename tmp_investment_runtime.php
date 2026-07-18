<?php
require 'config/db_connection.php';
require 'app/Services/InvestmentService.php';
$svc = new SACCO\Services\InvestmentService();
$stats = $svc->getDashboardStats();
echo json_encode($stats);
