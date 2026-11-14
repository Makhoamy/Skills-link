<?php
require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/mailer.php';
require_method('POST');

$email = trim((string)(body()['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fail('Please enter a valid email address.');
}

$user = q('SELECT id, email FROM users WHERE email = ? AND is_active = 1 AND deleted_at IS NULL', [$email])->fetch();

$link = '';

if ($user) {
    $userId = (int)$user['id'];

    // only needed when real emails are sent, so nobody can spam an inbox
    $recent = false;
    if (mail_is_set_up()) {
        $recent = q('SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > (NOW() - INTERVAL 1 MINUTE)', [$userId])->fetch();
    }

    if (!$recent) {
        q('DELETE FROM password_resets WHERE user_id = ?', [$userId]);

        $token = bin2hex(random_bytes(32));
        q('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, (NOW() + INTERVAL 1 HOUR))',
          [$userId, hash('sha256', $token)]);

        $site = rtrim(getenv('ALLOWED_ORIGIN') ?: 'http://localhost:4200', '/');
        $link = $site . '/reset-password?token=' . $token;

        $text = "Hi,\n\n"
            . "We got a request to reset the password for your Skills Link account.\n"
            . "Click the link below to choose a new password. The link works for 1 hour.\n\n"
            . $link . "\n\n"
            . "If you did not ask for this, you can ignore this email and your password will stay the same.\n\n"
            . "Skills Link";

        send_mail($user['email'], 'Reset your Skills Link password', $text);
    }
}

$response = [
    'success' => true,
    'message' => 'If an account exists with this email, a password reset link has been sent.',
];

// no email settings yet, so show the link on the page instead
if ($link != '' && !mail_is_set_up()) {
    $response['resetLink'] = $link;
}

json_out($response);
