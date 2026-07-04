<?php
namespace sacco\Services;

require_once __DIR__ . '/../../config/db_connection.php';

class RoleService
{
    private $db;

    public function __construct()
    {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getAllRoles(): array
    {
        $stmt = $this->db->query('SELECT role_id, role_name, label, description, created_at FROM roles ORDER BY label');
        return $stmt->fetchAll();
    }

    public function getRoleById(int $roleId): ?array
    {
        $stmt = $this->db->prepare('SELECT role_id, role_name, label, description FROM roles WHERE role_id = ?');
        $stmt->execute([$roleId]);
        $role = $stmt->fetch();
        return $role ?: null;
    }

    public function getRoleByName(string $roleName): ?array
    {
        $stmt = $this->db->prepare('SELECT role_id, role_name, label, description FROM roles WHERE role_name = ?');
        $stmt->execute([$roleName]);
        $role = $stmt->fetch();
        return $role ?: null;
    }

    public function createRole(string $roleName, string $label, string $description = ''): array
    {
        if ($this->roleExists($roleName)) {
            return ['success' => false, 'message' => 'Role name already exists.'];
        }

        $stmt = $this->db->prepare('INSERT INTO roles (role_name, label, description, created_at) VALUES (?, ?, ?, NOW())');
        $success = $stmt->execute([$roleName, $label, $description]);

        return ['success' => $success, 'message' => $success ? 'Role created successfully.' : 'Unable to create role.'];
    }

    public function updateRole(int $roleId, string $label, string $description = ''): bool
    {
        $stmt = $this->db->prepare('UPDATE roles SET label = ?, description = ? WHERE role_id = ?');
        return $stmt->execute([$label, $description, $roleId]);
    }

    public function deleteRole(int $roleId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM roles WHERE role_id = ?');
        return $stmt->execute([$roleId]);
    }

    public function roleExists(string $roleName): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM roles WHERE role_name = ?');
        $stmt->execute([$roleName]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getAllPermissions(): array
    {
        $stmt = $this->db->query('SELECT permission_id, permission_key, label, description, created_at FROM permissions ORDER BY permission_key');
        return $stmt->fetchAll();
    }

    public function getPermissionById(int $permissionId): ?array
    {
        $stmt = $this->db->prepare('SELECT permission_id, permission_key, label, description FROM permissions WHERE permission_id = ?');
        $stmt->execute([$permissionId]);
        return $stmt->fetch() ?: null;
    }

    public function getPermissionByKey(string $permissionKey): ?array
    {
        $stmt = $this->db->prepare('SELECT permission_id, permission_key, label, description FROM permissions WHERE permission_key = ?');
        $stmt->execute([$permissionKey]);
        return $stmt->fetch() ?: null;
    }

    public function createPermission(string $permissionKey, string $label, string $description = ''): array
    {
        if ($this->permissionExists($permissionKey)) {
            return ['success' => false, 'message' => 'Permission key already exists.'];
        }

        $stmt = $this->db->prepare('INSERT INTO permissions (permission_key, label, description, created_at) VALUES (?, ?, ?, NOW())');
        $success = $stmt->execute([$permissionKey, $label, $description]);

        return ['success' => $success, 'message' => $success ? 'Permission created successfully.' : 'Unable to create permission.'];
    }

    public function updatePermission(int $permissionId, string $label, string $description = ''): bool
    {
        $stmt = $this->db->prepare('UPDATE permissions SET label = ?, description = ? WHERE permission_id = ?');
        return $stmt->execute([$label, $description, $permissionId]);
    }

    public function deletePermission(int $permissionId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM permissions WHERE permission_id = ?');
        return $stmt->execute([$permissionId]);
    }

    public function permissionExists(string $permissionKey): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM permissions WHERE permission_key = ?');
        $stmt->execute([$permissionKey]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function getPermissionsForRole(int $roleId): array
    {
        $stmt = $this->db->prepare(
            'SELECT p.permission_id, p.permission_key, p.label, p.description
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.permission_key'
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll();
    }

    public function assignPermissionToRole(int $roleId, int $permissionId): array
    {
        if ($this->rolePermissionExists($roleId, $permissionId)) {
            return ['success' => false, 'message' => 'Permission is already assigned to this role.'];
        }

        $stmt = $this->db->prepare('INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)');
        $success = $stmt->execute([$roleId, $permissionId]);

        return ['success' => $success, 'message' => $success ? 'Permission assigned successfully.' : 'Unable to assign permission.'];
    }

    public function removePermissionFromRole(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM role_permissions WHERE role_id = ? AND permission_id = ?');
        return $stmt->execute([$roleId, $permissionId]);
    }

    public function rolePermissionExists(int $roleId, int $permissionId): bool
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM role_permissions WHERE role_id = ? AND permission_id = ?');
        $stmt->execute([$roleId, $permissionId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
