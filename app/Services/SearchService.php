<?php
namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';

class SearchService
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function search(string $term): array
    {
        $term = trim($term);
        if ($term === '') {
            return ['members' => [], 'loans' => [], 'transactions' => []];
        }

        $searchTerm = '%' . $term . '%';

        $members = $this->searchMembers($searchTerm);
        $loans = $this->searchLoans($searchTerm);
        $transactions = $this->searchTransactions($searchTerm);

        return [
            'members' => $members,
            'loans' => $loans,
            'transactions' => $transactions
        ];
    }

    private function searchMembers(string $searchTerm): array
    {
        $stmt = $this->db->prepare('SELECT member_id, membership_no, full_name, phone, email FROM members WHERE full_name LIKE ? OR membership_no LIKE ? OR national_id LIKE ? OR phone LIKE ? LIMIT 20');
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    private function searchLoans(string $searchTerm): array
    {
        $stmt = $this->db->prepare('SELECT loan_id, loan_ref_no, member_id, amount_requested, status FROM loans WHERE loan_ref_no LIKE ? OR purpose LIKE ? LIMIT 20');
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }

    private function searchTransactions(string $searchTerm): array
    {
        $stmt = $this->db->prepare('SELECT st.trans_id, st.receipt_no, st.amount, st.reference_no, sa.account_type, m.full_name FROM savings_transactions st JOIN savings_accounts sa ON st.account_id = sa.account_id JOIN members m ON sa.member_id = m.member_id WHERE st.receipt_no LIKE ? OR st.reference_no LIKE ? LIMIT 20');
        $stmt->execute([$searchTerm, $searchTerm]);
        return $stmt->fetchAll();
    }
}
