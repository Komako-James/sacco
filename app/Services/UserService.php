<?php
namespace sacco\Services;

require_once __DIR__ . '/../../config/db_connection.php';

class UserService
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getRoleOptions(): array
    {
        try {
            $stmt = $this->db->query("SELECT role_name, label FROM roles ORDER BY role_name");
            $roles = $stmt->fetchAll();
            if (!empty($roles)) {
                return array_column($roles, 'label', 'role_name');
            }
        } catch (\Exception $e) {
            // Fallback to static roles if roles table is missing
        }

        return [
            'admin' => 'Super Admin',
            'manager' => 'Manager',
            'accountant' => 'Accountant',
            'loan_officer' => 'Loan Officer',
            'cashier' => 'Teller',
            'audit' => 'Auditor',
            'viewer' => 'Viewer'
        ];
    }

    public function listUsers(int $page = 1, int $limit = 20, string $search = ''): array
    {
        $offset = ($page - 1) * $limit;
        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($search)) {
            $where .= ' AND (username LIKE ? OR full_name LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $searchTerm = '%' . $search . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $countStmt = $this->db->prepare("SELECT COUNT(*) AS total FROM users {$where}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM users {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $params[] = $limit;
        $params[] = $offset;
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int)ceil($total / $limit)
        ];
    }

    public function getUser(int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function createUser(array $data, int $createdBy): array
    {
        $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt = $this->db->prepare(
            'INSERT INTO users (username, email, password_hash, full_name, phone, role, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );

        $stmt->execute([
            $data['username'],
            $data['email'],
            $passwordHash,
            $data['full_name'],
            $data['phone'],
            $data['role'],
            $data['status'] ?? 'active',
            $createdBy
        ]);

        return ['success' => true, 'user_id' => (int)$this->db->lastInsertId()];
    }

    public function updateUser(int $userId, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['full_name'])) {
            $fields[] = 'full_name = ?';
            $params[] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $params[] = $data['email'];
        }
        if (isset($data['phone'])) {
            $fields[] = 'phone = ?';
            $params[] = $data['phone'];
        }
        if (isset($data['role'])) {
            $fields[] = 'role = ?';
            $params[] = $data['role'];
        }
        if (isset($data['status'])) {
            $fields[] = 'status = ?';
            $params[] = $data['status'];
        }
        if (isset($data['password']) && !empty($data['password'])) {
            $fields[] = 'password_hash = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE user_id = ?';
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function resetPassword(int $userId, string $password): bool
    {
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
        return $stmt->execute([$passwordHash, $userId]);
    }
}
