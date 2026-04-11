<?php
declare(strict_types=1);

$requiredPermission = 'tortoise.update';
require_once 'includes/auth_check.php';

$tortoiseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$tortoiseId) {
    header('Location: index.php');
    exit;
}

header('Location: create.php?id=' . urlencode((string) $tortoiseId));
exit;
