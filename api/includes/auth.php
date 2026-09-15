<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/database.php';

function apiJson(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function generateApiToken(PDO $pdo, int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        'INSERT INTO api_tokens
            (user_id, token_hash, expires_at)
         VALUES
            (:user_id, :token_hash, DATE_ADD(UTC_TIMESTAMP(), INTERVAL 30 DAY))'
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':token_hash' => $tokenHash,
    ]);

    return $token;
}

function getBearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches)) {
        return null;
    }

    return trim($matches[1]);
}

function requireApiUser(PDO $pdo): array
{
    $token = getBearerToken();

    if ($token === null || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
        apiJson([
            'success' => false,
            'message' => 'رمز الدخول غير صالح أو مفقود'
        ], 401);
    }

    $tokenHash = hash('sha256', $token);

    $stmt = $pdo->prepare(
        'SELECT
            t.id AS token_id,
            t.user_id,
            u.full_name,
            u.username,
            u.phone,
            u.email,
            u.status,
            u.role
         FROM api_tokens t
         INNER JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = :token_hash
           AND t.expires_at > UTC_TIMESTAMP()
         LIMIT 1'
    );

    $stmt->execute([
        ':token_hash' => $tokenHash,
    ]);

    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active') {
        apiJson([
            'success' => false,
            'message' => 'رمز الدخول منتهي أو غير صالح'
        ], 401);
    }

    $update = $pdo->prepare(
        'UPDATE api_tokens
         SET last_used_at = UTC_TIMESTAMP()
         WHERE id = :id'
    );

    $update->execute([
        ':id' => $user['token_id'],
    ]);

    return $user;
}
