<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function validateCsrfToken(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        return false;
    }
    return is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrfToken(): void
{
    $token = $_POST['csrf_token'] ?? null;
    if (!validateCsrfToken(is_string($token) ? $token : null)) {
        http_response_code(419);
        exit('CSRF token validation failed.');
    }
}

function postString(string $key, int $maxLen = 255, bool $required = true): ?string
{
    $value = trim((string) ($_POST[$key] ?? ''));
    if ($value === '') {
        return $required ? null : '';
    }
    if (strlen($value) > $maxLen) {
        return null;
    }
    return $value;
}

function postInt(string $key, bool $required = true): ?int
{
    $value = $_POST[$key] ?? null;
    if (($value === null || $value === '') && !$required) {
        return null;
    }
    $result = filter_var($value, FILTER_VALIDATE_INT);
    return ($result === false) ? null : (int) $result;
}

function postFloat(string $key, bool $required = true): ?float
{
    $value = $_POST[$key] ?? null;
    if (($value === null || $value === '') && !$required) {
        return null;
    }
    $result = filter_var($value, FILTER_VALIDATE_FLOAT);
    return ($result === false) ? null : (float) $result;
}
