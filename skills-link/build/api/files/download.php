<?php

require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
$user = require_auth();

$map = [
    'cv'               => ['worker_profiles',    'cv_path'],
    'photo'            => ['worker_profiles',    'profile_photo_path'],
    'registration_doc' => ['recruiter_profiles', 'registration_doc_path'],
];
$kind = (string)($_GET['kind'] ?? '');
if (!isset($map[$kind])) fail('Invalid file type');
$ownerId = (int)($_GET['user_id'] ?? $user['id']);
[$table, $col] = $map[$kind];

$allowed = $ownerId === $user['id'] || $user['role'] === 'admin';
if (!$allowed && $kind === 'photo') $allowed = true;
if (!$allowed && $kind === 'cv' && $user['role'] === 'recruiter') {
    $allowed = (bool)q('SELECT 1 FROM job_applications a JOIN jobs j ON j.id = a.job_id
                        WHERE a.worker_id = ? AND j.recruiter_id = ? AND a.status <> \'withdrawn\' LIMIT 1',
                       [$ownerId, $user['id']])->fetch();
}
if (!$allowed) fail('Not found', 404);

$file = q("SELECT t.$col FROM $table t JOIN users u ON u.id = t.user_id
           WHERE t.user_id = ? AND u.deleted_at IS NULL", [$ownerId])->fetchColumn();
if (!$file) fail('Not found', 404);

$path = upload_dir() . '/' . basename((string)$file);
if (!is_file($path)) fail('Not found', 404);

$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . $kind . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
header('Cache-Control: private, no-store');
readfile($path);
exit;
