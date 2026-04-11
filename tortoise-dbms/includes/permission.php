<?php
declare(strict_types=1);

require_once __DIR__ . '/../db_connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function loadUserPermissions(int $user_id): array
{
    global $pdo;

    $stmt = $pdo->prepare('
        SELECT
            r.role_id,
            r.role_name,
            r.role_code,
            p.permission_key
        FROM USERS u
        INNER JOIN ROLES r ON r.role_id = u.role_id
        LEFT JOIN role_permissions rp ON rp.role_id = r.role_id AND rp.is_granted = 1
        LEFT JOIN permissions p ON p.permission_id = rp.permission_id
        WHERE u.user_id = :user_id
    ');
    $stmt->execute([':user_id' => $user_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        $_SESSION['permissions'] = [];
        return [];
    }

    $_SESSION['role_id'] = (int) $rows[0]['role_id'];
    $_SESSION['role_name'] = (string) $rows[0]['role_name'];
    $_SESSION['role_code'] = (string) $rows[0]['role_code'];

    $permissions = [];
    foreach ($rows as $row) {
        if (!empty($row['permission_key'])) {
            $permissions[$row['permission_key']] = true;
        }
    }

    $_SESSION['permissions'] = $permissions;
    $_SESSION['permissions_loaded_at'] = time();

    return $permissions;
}

function hasPermission(string $permission): bool
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $roleCode = strtoupper((string) ($_SESSION['role_code'] ?? ''));
    if ($roleCode === 'ADMIN') {
        return true;
    }

    if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
        loadUserPermissions((int) $_SESSION['user_id']);
    }

    return isset($_SESSION['permissions'][$permission]) && $_SESSION['permissions'][$permission] === true;
}

function requirePermission(string $permission): void
{
    if (!hasPermission($permission)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

function requireAnyPermission(array $permissions): void
{
    foreach ($permissions as $permission) {
        if (hasPermission((string) $permission)) {
            return;
        }
    }
    http_response_code(403);
    exit('403 Forbidden');
}
