<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');

$in = body();
$email = trim((string)($in['email'] ?? ''));
$password = (string)($in['password'] ?? '');

if ($email === '' || $password === '') {
    fail('Email and password are required.');
}

$user = q('SELECT id, email, password_hash, role, is_active, is_verified FROM users WHERE email = ? AND deleted_at IS NULL', [$email])->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    fail('Invalid email or password.', 401);
}
if (!$user['is_active']) {
    fail('This account has been deactivated.', 403);
}

$token = bin2hex(random_bytes(32));
$expires = (new DateTime('+7 days'))->format('Y-m-d H:i:s');

q('INSERT INTO auth_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
  [$user['id'], hash('sha256', $token), $expires]);

// save the login so the admin can see it
q('INSERT INTO loginHistory (userID, loginDate, ipAddress) VALUES (?, NOW(), ?)',
  [$user['id'], $_SERVER['REMOTE_ADDR'] ?? null]);

// An unverified user gets a session, but it only works on the verify-email endpoints (see require_auth).
json_out([
    'success' => true,
    'token' => $token,
    'expiresAt' => $expires,
    'user' => [
        'id' => (int)$user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'isVerified' => (bool)$user['is_verified'],
    ],
]);
