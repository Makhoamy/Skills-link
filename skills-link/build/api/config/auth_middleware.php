<?php
require_once __DIR__ . '/db.php';

function require_auth(bool $allowUnverified = false): array
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/', $header, $m)) {
        fail('Missing or malformed Authorization header.', 401);
    }
    $token = $m[1];
    $tokenHash = hash('sha256', $token);

    $stmt = db()->prepare(
        'SELECT u.* FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token_hash = :h AND t.expires_at > NOW() AND u.deleted_at IS NULL AND u.is_active = 1'
    );
    $stmt->execute(['h' => $tokenHash]);
    $user = $stmt->fetch();

    if (!$user) {
        fail('Invalid or expired session.', 401);
    }
    if (!$allowUnverified && !(int)$user['is_verified']) {
        respond(['success' => false, 'error' => 'Please verify your email address to continue.', 'code' => 'email_not_verified'], 403);
    }
    return $user;
}

function require_role(array $user, string $role): void
{
    if ($user['role'] !== $role) {
        fail("This action requires the '{$role}' role.", 403);
    }
}
