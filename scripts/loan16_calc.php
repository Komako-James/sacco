<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT loan_id, amount_approved, interest_rate, repayment_period_months, total_paid, outstanding_balance, status FROM loans WHERE loan_id = ?');
$stmt->execute([16]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$loan) { echo 'not found'; exit; }
$principal = (float)$loan['amount_approved'];
$annualRate = (float)$loan['interest_rate'];
$months = (int)$loan['repayment_period_months'];
$monthlyRate = $annualRate / 100 / 12;
if ($monthlyRate == 0) {
    $monthlyPayment = $principal / $months;
} else {
    $monthlyPayment = ($principal * $monthlyRate * pow(1 + $monthlyRate, $months)) / (pow(1 + $monthlyRate, $months) - 1);
}
$totalRepayable = round($monthlyPayment * $months, 2);
$totalInterest = round($totalRepayable - $principal, 2);
$expectedAfterPrincipalOnly = $totalInterest;
echo json_encode([
    'loan_id' => (int)$loan['loan_id'],
    'amount_approved' => $principal,
    'interest_rate' => $annualRate,
    'repayment_period_months' => $months,
    'monthly_rate' => $monthlyRate,
    'monthly_payment' => round($monthlyPayment, 2),
    'total_repayable' => $totalRepayable,
    'total_contractual_interest' => $totalInterest,
    'actual_total_paid' => (float)$loan['total_paid'],
    'actual_outstanding_balance' => (float)$loan['outstanding_balance'],
    'expected_remaining_if_only_principal_paid' => $expectedAfterPrincipalOnly,
    'difference_vs_actual' => round($expectedAfterPrincipalOnly - (float)$loan['outstanding_balance'], 2),
    'status' => $loan['status']
], JSON_PRETTY_PRINT);
