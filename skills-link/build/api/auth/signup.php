<?php
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/mailer.php';
require __DIR__ . '/../lib/password_policy.php';
require_method('POST');

$in = body();

$email     = trim((string)($in['email'] ?? ''));
$password  = (string)($in['password'] ?? '');
$role      = (string)($in['role'] ?? '');
$phone     = trim((string)($in['phone'] ?? ''));
$consent   = (bool)($in['popiaConsent'] ?? false);
$marketing = (bool)($in['marketingConsent'] ?? false);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fail('Please provide a valid email address.');
}
if (!preg_match('/^\d{10}\z/', $phone)) {
    fail('Phone number must be exactly 10 digits.');
}
if ($err = password_error($password, $email)) {
    fail($err);
}
// Admins are never self-registered. They are added directly in the database (database/create_admin.php).
if (!in_array($role, ['jobseeker', 'recruiter'], true)) {
    fail('Role must be jobseeker or recruiter.');
}
if (!$consent) {
    fail('You must accept the Privacy Policy / POPIA consent to register.');
}

if (q('SELECT id FROM users WHERE email = ?', [$email])->fetch()) {
    fail('An account with that email already exists.', 409);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$policyVersion = '2026-01';
$ip = @inet_pton($_SERVER['REMOTE_ADDR'] ?? '') ?: null;

$pdo = db();
$pdo->beginTransaction();
try {
    q('INSERT INTO users (email, password_hash, role, phone, is_verified, popia_consent_at, popia_policy_version, marketing_consent)
       VALUES (?, ?, ?, ?, 1, NOW(), ?, ?)',
      [$email, $hash, $role, $phone, $policyVersion, $marketing ? 1 : 0]);
    $userId = (int)$pdo->lastInsertId();

    $log = 'INSERT INTO consent_log (user_id, user_ref, consent_type, consent_given, policy_version, ip_address) VALUES (?, ?, ?, 1, ?, ?)';
    q($log, [$userId, user_ref($userId), 'popia_processing', $policyVersion, $ip]);
    if ($marketing) {
        q($log, [$userId, user_ref($userId), 'marketing', $policyVersion, $ip]);
    }

    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    if (($e->errorInfo[1] ?? 0) === 1062) {
        fail('An account with that email already exists.', 409);
    }
    error_log((string)$e);
    fail('Could not create account. Please try again.', 500);
}

json_out([
    'success' => true,
    'userId' => $userId,
    'role' => $role,
    'requiresVerification' => false,
], 201);
