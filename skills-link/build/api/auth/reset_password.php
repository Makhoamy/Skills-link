<?php
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/password_policy.php';
require_method('POST');

$in = body();
$token = (string)($in['token'] ?? '');
$password = (string)($in['password'] ?? '');

if ($token === '') {
    fail('The reset link is not valid.');
}

$row = q('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()', [hash('sha256', $token)])->fetch();
if (!$row) {
    fail('This reset link is invalid or has expired. Please ask for a new one.');
}

$userId = (int)$row['user_id'];

$email = (string)q('SELECT email FROM users WHERE id = ?', [$userId])->fetchColumn();
if ($err = password_error($password, $email)) {
    fail($err);
}

q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_BCRYPT), $userId]);
q('DELETE FROM password_resets WHERE user_id = ?', [$userId]);
// log them out everywhere
q('DELETE FROM auth_tokens WHERE user_id = ?', [$userId]);

json_out(['success' => true, 'message' => 'Your password has been changed. You can now sign in.']);
