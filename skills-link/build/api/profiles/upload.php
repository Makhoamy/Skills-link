<?php

require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');
$user = require_auth(['jobseeker', 'recruiter']);

$pdfImg = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];
$img    = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
$rules = [
    'cv'               => ['role' => 'jobseeker', 'table' => 'worker_profiles',    'col' => 'cv_path',                'mimes' => $pdfImg],
    'photo'            => ['role' => 'jobseeker', 'table' => 'worker_profiles',    'col' => 'profile_photo_path',     'mimes' => $img],
    'registration_doc' => ['role' => 'recruiter', 'table' => 'recruiter_profiles', 'col' => 'registration_doc_path', 'mimes' => $pdfImg],
];
$kind = (string)($_POST['kind'] ?? '');
if (!isset($rules[$kind])) fail('Invalid upload type');
$rule = $rules[$kind];
if ($user['role'] !== $rule['role']) fail('Forbidden', 403);

$f = $_FILES['file'] ?? null;
if (!$f) fail('No file received', 422);
if ($f['error'] !== UPLOAD_ERR_OK) {
    $msgs = [
        UPLOAD_ERR_INI_SIZE   => 'That file is too big for the server (max 5 MB).',
        UPLOAD_ERR_FORM_SIZE  => 'That file is too big (max 5 MB).',
        UPLOAD_ERR_PARTIAL    => 'The upload was interrupted. Please try again.',
        UPLOAD_ERR_NO_FILE    => 'No file received.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server upload folder is missing.',
        UPLOAD_ERR_CANT_WRITE => 'Server could not write the file.',
    ];
    fail($msgs[$f['error']] ?? 'Upload failed.', $f['error'] === UPLOAD_ERR_INI_SIZE ? 413 : 422);
}
if (!is_uploaded_file($f['tmp_name'])) fail('No file received', 422);
if ($f['size'] > 5 * 1024 * 1024) fail('File is too large (max 5 MB)', 413);
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
if (!isset($rule['mimes'][$mime])) fail('File type not allowed. Please use ' . implode(', ', array_map('strtoupper', array_values(array_unique($rule['mimes'])))) . '.', 415);

// The profile row must exist before we accept a file for it.
$old = q("SELECT {$rule['col']} FROM {$rule['table']} WHERE user_id = ?", [$user['id']])->fetchColumn();
if ($old === false) fail('Please save your profile first', 422);

$dir  = upload_dir();
$name = bin2hex(random_bytes(16)) . '.' . $rule['mimes'][$mime];
if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) fail('Could not save file', 500);

$extra = $kind === 'registration_doc' ? ", verification_status = 'pending', verified_at = NULL" : '';
q("UPDATE {$rule['table']} SET {$rule['col']} = ?$extra WHERE user_id = ?", [$name, $user['id']]);
if ($old) @unlink("$dir/" . basename((string)$old));

json_out(['ok' => true]);
