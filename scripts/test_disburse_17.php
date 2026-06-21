<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/LoanService.php';
$db = Database::getInstance()->getConnection();
$loanService = new \SACCO\Services\LoanService($db);
$loanId = 17;
try {
    $res = $loanService->disburseLoan($loanId, 'cash', [], 1);
    file_put_contents(__DIR__ . '/test_disburse_17_result.json', json_encode($res, JSON_PRETTY_PRINT));
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/test_disburse_17_result.json', json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_PRETTY_PRINT));
}
