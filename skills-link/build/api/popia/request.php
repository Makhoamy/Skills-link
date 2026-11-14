<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

$user = require_auth(true); // data-subject rights must not depend on email verification
$in = json_input();

$type = $in['requestType'] ?? '';
$notes = trim($in['notes'] ?? '');

if (!in_array($type, ['access', 'correction', 'deletion', 'objection'], true)) {
    fail('requestType must be one of: access, correction, deletion, objection.');
}

$pdo = db();
$stmt = $pdo->prepare(
    'INSERT INTO data_subject_requests (user_id, request_type, notes) VALUES (:uid, :type, :notes)'
);
$stmt->execute(['uid' => $user['id'], 'type' => $type, 'notes' => $notes ?: null]);

if ($type === 'deletion') {
    $pdo->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => $user['id']]);
}

respond(['success' => true, 'message' => 'Your request has been logged and will be actioned.'], 201);
