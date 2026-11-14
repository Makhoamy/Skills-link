<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');
$user = require_auth(['jobseeker']);
$b = body();

$jobId = (int)($b['job_id'] ?? 0);
$note  = isset($b['cover_note']) ? trim((string)$b['cover_note']) : '';
if ($jobId <= 0) fail('job_id is required');
if (mb_strlen($note) > 500) fail('Cover note is too long (max 500 characters)');

$job = q("SELECT j.id FROM jobs j JOIN users u ON u.id = j.recruiter_id
          WHERE j.id = ? AND j.status = 'open' AND (j.closes_at IS NULL OR j.closes_at >= CURDATE())
            AND u.deleted_at IS NULL AND u.is_active = 1", [$jobId])->fetch();
if (!$job) fail('This job is no longer open', 404);

if (!q('SELECT 1 FROM worker_profiles WHERE user_id = ?', [$user['id']])->fetch()) {
    fail('Please complete your profile before applying', 422);
}

try {
    q('INSERT INTO job_applications (job_id, worker_id, cover_note) VALUES (?,?,?)',
      [$jobId, $user['id'], $note === '' ? null : $note]);
} catch (PDOException $e) {
    if (($e->errorInfo[1] ?? 0) === 1062) fail('You have already applied for this job', 409);
    throw $e;
}
json_out(['ok' => true, 'id' => (int)db()->lastInsertId()], 201);
