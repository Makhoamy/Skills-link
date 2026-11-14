<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
$user  = require_auth(['recruiter']);
$jobId = (int)($_GET['job_id'] ?? 0);

$job = q('SELECT id, title FROM jobs WHERE id = ? AND recruiter_id = ?', [$jobId, $user['id']])->fetch();
if (!$job) fail('Job not found', 404);

$rows = q("SELECT a.id, a.status, a.cover_note, a.applied_at,
                  w.user_id AS worker_id, w.first_name, w.last_name, w.city, w.suburb,
                  w.years_experience, w.availability, w.bio, w.avg_rating, w.rating_count,
                  (w.cv_path IS NOT NULL) AS has_cv,
                  IF(a.status IN ('shortlisted','hired'), u.phone, NULL) AS phone
           FROM job_applications a
           JOIN worker_profiles w ON w.user_id = a.worker_id
           JOIN users u ON u.id = w.user_id
           WHERE a.job_id = ? AND u.deleted_at IS NULL AND u.is_active = 1
           ORDER BY FIELD(a.status,'hired','shortlisted','applied','rejected','withdrawn'), a.applied_at DESC",
          [$jobId])->fetchAll();
foreach ($rows as &$r) { $r['has_cv'] = (bool)$r['has_cv']; }

json_out(['job' => $job, 'items' => $rows]);
