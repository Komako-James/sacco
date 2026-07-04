<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/RoleService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$roleService = new \sacco\Services\RoleService();

$errors = [];
$success = '';
$action = $_POST['action'] ?? '';
$csrfToken = $_POST['csrf_token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($csrfToken)) {
        $errors[] = 'Invalid CSRF token. Please refresh the page and try again.';
    } else {
        switch ($action) {
            case 'create_role':
                $roleName = trim($_POST['role_name'] ?? '');
                $label = trim($_POST['label'] ?? '');
                $description = trim($_POST['description'] ?? '');
                if ($roleName === '') {
                    $errors[] = 'Role name is required.';
                }
                if ($label === '') {
                    $errors[] = 'Role label is required.';
                }

                if (empty($errors)) {
                    $result = $roleService->createRole($roleName, $label, $description);
                    if (!$result['success']) {
                        $errors[] = $result['message'];
                    } else {
                        $success = $result['message'];
                    }
                }
                break;

            case 'update_role':
                $roleId = (int)($_POST['role_id'] ?? 0);
                $label = trim($_POST['label'] ?? '');
                $description = trim($_POST['description'] ?? '');
                if ($roleId <= 0) {
                    $errors[] = 'Invalid role selected.';
                }
                if ($label === '') {
                    $errors[] = 'Role label is required.';
                }

                if (empty($errors)) {
                    if (!$roleService->getRoleById($roleId)) {
                        $errors[] = 'Role not found.';
                    } else {
                        $updated = $roleService->updateRole($roleId, $label, $description);
                        if ($updated) {
                            $success = 'Role updated successfully.';
                        } else {
                            $errors[] = 'Unable to update role.';
                        }
                    }
                }
                break;

            case 'create_permission':
                $permissionKey = trim($_POST['permission_key'] ?? '');
                $label = trim($_POST['permission_label'] ?? '');
                $description = trim($_POST['permission_description'] ?? '');
                if ($permissionKey === '') {
                    $errors[] = 'Permission key is required.';
                }
                if ($label === '') {
                    $errors[] = 'Permission label is required.';
                }

                if (empty($errors)) {
                    $result = $roleService->createPermission($permissionKey, $label, $description);
                    if (!$result['success']) {
                        $errors[] = $result['message'];
                    } else {
                        $success = $result['message'];
                    }
                }
                break;

            case 'assign_permission':
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permissionId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId <= 0 || $permissionId <= 0) {
                    $errors[] = 'Invalid role or permission selected.';
                }

                if (empty($errors)) {
                    $result = $roleService->assignPermissionToRole($roleId, $permissionId);
                    if (!$result['success']) {
                        $errors[] = $result['message'];
                    } else {
                        $success = $result['message'];
                    }
                }
                break;

            case 'remove_permission':
                $roleId = (int)($_POST['role_id'] ?? 0);
                $permissionId = (int)($_POST['permission_id'] ?? 0);
                if ($roleId <= 0 || $permissionId <= 0) {
                    $errors[] = 'Invalid role or permission selected.';
                }

                if (empty($errors)) {
                    if ($roleService->removePermissionFromRole($roleId, $permissionId)) {
                        $success = 'Permission removed from role successfully.';
                    } else {
                        $errors[] = 'Unable to remove permission from role.';
                    }
                }
                break;
        }
    }
}

$roles = $roleService->getAllRoles();
$permissions = $roleService->getAllPermissions();
$currentRoleId = isset($_GET['role_id']) ? (int)$_GET['role_id'] : ($roles[0]['role_id'] ?? 0);
$currentRole = $roleService->getRoleById($currentRoleId);
$rolePermissions = $currentRole ? $roleService->getPermissionsForRole($currentRole['role_id']) : [];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Roles & Permissions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <main class="main-content p-4">
        <div class="container-fluid">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3">Roles & Permissions</h1>
                    <p class="text-muted mb-0">Manage staff roles, permissions, and role assignments.</p>
                </div>
            </div>

                    

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="card">
                        <div class="card-header">Roles</div>
                        <div class="card-body">
                            <form method="GET" class="mb-3">
                                <label class="form-label">Select role</label>
                                <select name="role_id" class="form-select" onchange="this.form.submit()">
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['role_id']; ?>" <?php echo $role['role_id'] === $currentRoleId ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role['label']); ?> (<?php echo htmlspecialchars($role['role_name']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </form>

                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Label</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roles as $role): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                            <td><?php echo htmlspecialchars($role['label']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header">Create New Role</div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfInputField(); ?>
                                <input type="hidden" name="action" value="create_role">
                                <div class="mb-3">
                                    <label class="form-label">Role Name</label>
                                    <input type="text" name="role_name" class="form-control" required>
                                    <div class="form-text">Unique machine-readable role name.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Label</label>
                                    <input type="text" name="label" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <button class="btn btn-primary">Create Role</button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">Role details</div>
                        <div class="card-body">
                            <?php if ($currentRole): ?>
                                <form method="POST">
                                    <?php echo csrfInputField(); ?>
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="role_id" value="<?php echo $currentRole['role_id']; ?>">
                                    <div class="mb-3">
                                        <label class="form-label">Role Name</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentRole['role_name']); ?>" disabled>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Label</label>
                                        <input type="text" name="label" class="form-control" value="<?php echo htmlspecialchars($currentRole['label']); ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Description</label>
                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($currentRole['description']); ?></textarea>
                                    </div>
                                    <button class="btn btn-primary">Save Role</button>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-info">Please select a role to view details.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">Permissions</div>
                        <div class="card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Key</th>
                                        <th>Label</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($permissions as $permission): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($permission['permission_key']); ?></td>
                                            <td><?php echo htmlspecialchars($permission['label']); ?></td>
                                            <td><?php echo htmlspecialchars($permission['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">Create New Permission</div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfInputField(); ?>
                                <input type="hidden" name="action" value="create_permission">
                                <div class="mb-3">
                                    <label class="form-label">Permission Key</label>
                                    <input type="text" name="permission_key" class="form-control" required>
                                    <div class="form-text">Use a dot-separated key like <code>users.manage</code>.</div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Label</label>
                                    <input type="text" name="permission_label" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <textarea name="permission_description" class="form-control" rows="3"></textarea>
                                </div>
                                <button class="btn btn-primary">Create Permission</button>
                            </form>
                        </div>
                    </div>

                    <?php if ($currentRole): ?>
                        <div class="card mb-4">
                            <div class="card-header">Role Permissions for <?php echo htmlspecialchars($currentRole['label']); ?></div>
                            <div class="card-body">
                                <?php if (!empty($rolePermissions)): ?>
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Key</th>
                                                <th>Label</th>
                                                <th>Description</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($rolePermissions as $permission): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($permission['permission_key']); ?></td>
                                                    <td><?php echo htmlspecialchars($permission['label']); ?></td>
                                                    <td><?php echo htmlspecialchars($permission['description']); ?></td>
                                                    <td>
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrfInputField(); ?>
                                                            <input type="hidden" name="action" value="remove_permission">
                                                            <input type="hidden" name="role_id" value="<?php echo $currentRole['role_id']; ?>">
                                                            <input type="hidden" name="permission_id" value="<?php echo $permission['permission_id']; ?>">
                                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-warning">No permissions assigned to this role yet.</div>
                                <?php endif; ?>

                                <?php if (!empty($permissions)): ?>
                                    <form method="POST" class="mt-3 row g-3 align-items-end">
                                        <?php echo csrfInputField(); ?>
                                        <input type="hidden" name="action" value="assign_permission">
                                        <input type="hidden" name="role_id" value="<?php echo $currentRole['role_id']; ?>">
                                        <div class="col-md-8">
                                            <label class="form-label">Assign Permission</label>
                                            <select name="permission_id" class="form-select" required>
                                                <option value="">Select permission</option>
                                                <?php foreach ($permissions as $permission): ?>
                                                    <?php if (!in_array($permission['permission_id'], array_column($rolePermissions, 'permission_id'), true)): ?>
                                                        <option value="<?php echo $permission['permission_id']; ?>"><?php echo htmlspecialchars($permission['label']); ?> (<?php echo htmlspecialchars($permission['permission_key']); ?>)</option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <button class="btn btn-success w-100">Assign Permission</button>
                                        </div>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
