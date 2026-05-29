<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'finance']);

$db = Database::getInstance()->getConnection();

$type = $_GET['type'] ?? 'members';
$format = $_GET['format'] ?? 'csv';

// Set headers for download
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $type . '_' . date('Y-m-d_His') . '.csv"');
}

if ($type === 'members') {
    exportMembers($db, $format);
} elseif ($type === 'loans') {
    exportLoans($db, $format);
} elseif ($type === 'savings') {
    exportSavings($db, $format);
} else {
    die('Invalid export type');
}

function exportMembers($db, $format) {
    $stmt = $db->query("
        SELECT 
            member_id,
            membership_no,
            full_name,
            national_id,
            phone,
            email,
            gender,
            join_date,
            status
        FROM members
        ORDER BY membership_no
    ");
    $data = $stmt->fetchAll();
    
    if ($format === 'csv') {
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Member ID', 'Membership No', 'Full Name', 'National ID', 'Phone', 'Email', 'Gender', 'Join Date', 'Status']);
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}

function exportLoans($db, $format) {
    $stmt = $db->query("
        SELECT 
            l.loan_id,
            l.loan_ref_no AS loan_reference,
            m.membership_no,
            m.full_name,
            l.amount_requested AS loan_amount,
            l.outstanding_balance,
            l.interest_rate,
            l.repayment_period,
            l.status,
            l.created_at
        FROM loans l
        JOIN members m ON l.member_id = m.member_id
        ORDER BY l.created_at DESC
    ");
    $data = $stmt->fetchAll();
    
    if ($format === 'csv') {
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Loan ID', 'Reference', 'Membership No', 'Member Name', 'Amount', 'Outstanding', 'Interest Rate', 'Months', 'Status', 'Date']);
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}

function exportSavings($db, $format) {
    $stmt = $db->query("
        SELECT 
            sa.account_id,
            sa.account_number,
            m.membership_no,
            m.full_name,
            sa.account_type,
            sa.balance,
            sa.interest_rate,
            sa.status,
            sa.created_at
        FROM savings_accounts sa
        JOIN members m ON sa.member_id = m.member_id
        ORDER BY m.membership_no
    ");
    $data = $stmt->fetchAll();
    
    if ($format === 'csv') {
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['Account ID', 'Account Number', 'Membership No', 'Member Name', 'Account Type', 'Balance', 'Interest Rate', 'Status', 'Opened']);
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }
}
?>
