<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrfToken(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['_csrf'];
}

function verifyCsrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['_csrf'])
        && hash_equals((string) $_SESSION['_csrf'], $token);
}

function actionToken(string $name): string
{
    $key = '_action_' . $name;

    if (empty($_SESSION[$key])) {
        $_SESSION[$key] = bin2hex(random_bytes(24));
    }

    return (string) $_SESSION[$key];
}

function consumeActionToken(string $name, ?string $token): bool
{
    $key = '_action_' . $name;

    if (
        !is_string($token)
        || !isset($_SESSION[$key])
        || !hash_equals((string) $_SESSION[$key], $token)
    ) {
        return false;
    }

    unset($_SESSION[$key]);
    return true;
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($message) ? $message : null;
}

/**
 * Normalize a positive amount without converting it to float.
 * DECIMAL(18,2) supports up to 16 integer digits and 2 decimals.
 */
function normalizeAmount(mixed $value): ?string
{
    $value = trim((string) $value);

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        return null;
    }

    [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
    $integer = ltrim($integer, '0');
    $integer = $integer === '' ? '0' : $integer;
    $fraction = str_pad($fraction, 2, '0');

    if (strlen($integer) > 16) {
        return null;
    }

    if ($integer === '0' && $fraction === '00') {
        return null;
    }

    return $integer . '.' . $fraction;
}

function formatMoney(mixed $value): string
{
    $value = trim((string) $value);

    if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $value)) {
        return '0.00';
    }

    [$integer, $fraction] = array_pad(explode('.', $value, 2), 2, '');
    $integer = ltrim($integer, '0');
    $integer = $integer === '' ? '0' : $integer;
    $fraction = str_pad($fraction, 2, '0');

    return strrev(implode(',', str_split(strrev($integer), 3)))
        . '.' . $fraction;
}

function cleanNote(mixed $value): ?string
{
    $note = trim((string) $value);

    if ($note === '') {
        return null;
    }

    if (strlen($note) > 255) {
        return null;
    }

    return $note;
}

function clientIp(): ?string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)
        ? $ip
        : null;
}
function logAdminAction(
    string $action,
    string $description,
    ?string $targetType = null,
    ?int $targetId = null
): void {
    global $pdo;

    $adminId = isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : null;

    $ipAddress = clientIp();

    $stmt = $pdo->prepare("
        INSERT INTO admin_audit_logs
        (
            admin_id,
            action,
            target_type,
            target_id,
            description,
            ip_address
        )
        VALUES
        (
            :admin_id,
            :action,
            :target_type,
            :target_id,
            :description,
            :ip_address
        )
    ");

    $stmt->execute([
        'admin_id' => $adminId ?: null,
        'action' => $action,
        'target_type' => $targetType,
        'target_id' => $targetId,
        'description' => $description,
        'ip_address' => $ipAddress,
    ]);
}
function createAdminNotification(
    string $title,
    string $message,
    string $type = 'info',
    ?string $targetType = null,
    ?int $targetId = null
): void {
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO admin_notifications
        (
            title,
            message,
            type,
            target_type,
            target_id
        )
        VALUES
        (
            :title,
            :message,
            :type,
            :target_type,
            :target_id
        )
    ");

    $stmt->execute([
        ':title' => $title,
        ':message' => $message,
        ':type' => $type,
        ':target_type' => $targetType,
        ':target_id' => $targetId,
    ]);
}
