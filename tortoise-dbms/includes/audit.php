<?php
declare(strict_types=1);

function auditLog(
    PDO $pdo,
    string $actionCode,
    string $entityType,
    ?string $entityId = null,
    ?array $before = null,
    ?array $after = null
): void {
    try {
        $actorUserId = $_SESSION['user_id'] ?? null;
        $actorRoleId = $_SESSION['role_id'] ?? null;
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $requestUri = substr((string) ($_SERVER['REQUEST_URI'] ?? ''), 0, 255);

        $stmt = $pdo->prepare('
            INSERT INTO audit_logs (
                actor_user_id,
                actor_role_id,
                action_code,
                entity_type,
                entity_id,
                before_json,
                after_json,
                ip_address,
                user_agent,
                request_uri
            ) VALUES (
                :actor_user_id,
                :actor_role_id,
                :action_code,
                :entity_type,
                :entity_id,
                :before_json,
                :after_json,
                :ip_address,
                :user_agent,
                :request_uri
            )
        ');

        $stmt->execute([
            ':actor_user_id' => $actorUserId,
            ':actor_role_id' => $actorRoleId,
            ':action_code' => $actionCode,
            ':entity_type' => $entityType,
            ':entity_id' => $entityId,
            ':before_json' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            ':after_json' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            ':ip_address' => $ipAddress,
            ':user_agent' => $userAgent,
            ':request_uri' => $requestUri,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}
