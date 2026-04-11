<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');

require_once __DIR__ . '/permission.php';

$timeoutDuration = 30 * 60;
$enforceIdleTimeout = false;

if (isset($_SESSION['user_id'])) {
    if ($enforceIdleTimeout && isset($_SESSION['last_activity']) && (time() - (int) $_SESSION['last_activity']) > $timeoutDuration) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
        header('Location: login.php?expired=1');
        exit;
    }

    $_SESSION['last_activity'] = time();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?auth=required');
    exit;
}

if (!isset($_SESSION['permissions']) || !is_array($_SESSION['permissions'])) {
    loadUserPermissions((int) $_SESSION['user_id']);
}

if (isset($requiredPermission) && is_string($requiredPermission) && $requiredPermission !== '') {
    if (!hasPermission($requiredPermission)) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

if (isset($requiredAnyPermissions) && is_array($requiredAnyPermissions) && count($requiredAnyPermissions) > 0) {
    $allowed = false;
    foreach ($requiredAnyPermissions as $permission) {
        if (hasPermission((string) $permission)) {
            $allowed = true;
            break;
        }
    }

    if (!$allowed) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}
?>
