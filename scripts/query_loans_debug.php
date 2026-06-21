<?php
require_once __DIR__ . '/../config/db_connection.php';
try {
    $db = Database::getInstance()->getConnection();
    $ids = [15,17];
    $stmt = $db->prepare('SELECT loan_id, loan_ref_no, status, amount_requested, amount_approved, outstanding_balance, product_id FROM loans WHERE loan_id = ?');
    $result = [];
    foreach ($ids as $id) {
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $result[$id] = $row ?: null;
    }
    echo json_encode(['success' => true, 'rows' => $result]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
