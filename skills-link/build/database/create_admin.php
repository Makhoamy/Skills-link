<?php
/**
 * Admin accounts are NOT created through the sign-up page. Add them here instead:
 *
 *   php database/create_admin.php admin@example.com            (asks for the password)
 *   php database/create_admin.php admin@example.com "Pa55word!x"   (password on the command line)
 *
 * The password follows the same rules as everyone else's (see api/lib/password_policy.php).
 * Admins are marked as verified straight away, so they do not need an email code.
 */
if (php_sapi_name() !== 'cli') {
    exit('Run this from the command line.');
}

require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/lib/password_policy.php';

$email = trim($argv[1] ?? '');
$password = $argv[2] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("Usage: php create_admin.php admin@example.com [password]\n");
}

if ($password === '') {
    // ask for it so it does not end up in the shell history
    $hide = DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    echo 'Password: ';
    if ($hide) shell_exec('stty -echo');
    $password = rtrim((string)fgets(STDIN), "\r\n");
    if ($hide) { shell_exec('stty echo'); echo "\n"; }
}

if ($err = password_error($password, $email)) {
    exit($err . "\n");
}

$pdo = db();

$check = $pdo->prepare('SELECT id FROM users WHERE email = :email');
$check->execute(['email' => $email]);
if ($check->fetch()) {
    exit("A user with that email already exists.\n");
}

$stmt = $pdo->prepare(
    "INSERT INTO users (email, password_hash, role, is_active, is_verified, popia_consent_at)
     VALUES (:email, :hash, 'admin', 1, 1, NOW())"
);
$stmt->execute([
    'email' => $email,
    'hash' => password_hash($password, PASSWORD_BCRYPT),
]);

echo "Admin account created for $email\n";
