<?php
require 'config/db_connection.php';
require 'app/Services/InvestmentService.php';
require 'app/Services/DividendService.php';

$investmentService = new SACCO\Services\InvestmentService();
$dividendService = new SACCO\Services\DividendService();

try {
    echo 'investment_stats=' . json_encode($investmentService->getDashboardStats()) . PHP_EOL;
    echo 'dividend_stats=' . json_encode($dividendService->getDashboardStats()) . PHP_EOL;
    echo 'maturity_summary_count=' . count($investmentService->getMaturitySummary()) . PHP_EOL;
} catch (Throwable $e) {
    echo 'ERROR:' . $e->getMessage() . PHP_EOL;
}
