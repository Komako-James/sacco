<?php
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/LoanService.php';
$db = Database::getInstance()->getConnection();
$loanService = new \SACCO\Services\LoanService($db);
$loanId = 17;
try {
    echo "START_PROCESS\n";
    file_put_contents(__DIR__ . '/test_repay_17_result.json', json_encode(['started'=>true], JSON_PRETTY_PRINT));
    // Direct test: run the same penalty aggregation SQL to reproduce error
    $db = \Database::getInstance()->getConnection();
    $sql = "SELECT COALESCE(SUM(late_penalty), 0) as total\n            FROM loan_repayment_schedule\n            WHERE loan_id = ? AND status IN ('pending','partial','overdue')";
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute([$loanId]);
        $res = ['success'=>true,'total'=> $stmt->fetch(PDO::FETCH_ASSOC)];
    } catch (Exception $e) {
        $res = ['success'=>false,'error'=>$e->getMessage(),'trace'=>$e->getTraceAsString(),'sql'=>$sql];
    }
    echo "DONE_PROCESS\n";
    file_put_contents(__DIR__ . '/test_repay_17_result.json', json_encode($res, JSON_PRETTY_PRINT));
} catch (Exception $e) {
    echo "EXCEPTION\n";
    $out = ['success'=>false,'error'=>$e->getMessage(),'trace'=>$e->getTraceAsString()];
    file_put_contents(__DIR__ . '/test_repay_17_result.json', json_encode($out, JSON_PRETTY_PRINT));
}
