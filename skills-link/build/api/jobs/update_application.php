<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');
$user = require_auth(['recruiter', 'jobseeker']);
$b = body();
$appId  = (int)($b['application_id'] ?? 0);
$status = (string)($b['status'] ?? '');

$app = q('SELECT a.id, a.status, a.worker_id, j.recruiter_id
          FROM job_applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ?', [$appId])->fetch();
if (!$app) fail('Application not found', 404);
if ($app['status'] === 'withdrawn') fail('This application was withdrawn', 409);

if ($user['role'] === 'recruiter') {
    if ((int)$app['recruiter_id'] !== $user['id']) fail('Application not found', 404);
    if (!in_array($status, ['shortlisted', 'rejected', 'hired'], true)) fail('Invalid status');
} else {
    if ((int)$app['worker_id'] !== $user['id']) fail('Application not found', 404);
    if ($status !== 'withdrawn') fail('Invalid status');
    if (!in_array($app['status'], ['applied', 'shortlisted'], true)) fail('This application can no longer be withdrawn', 409);
}

q('UPDATE job_applications SET status = ? WHERE id = ?', [$status, $appId]);
json_out(['ok' => true, 'status' => $status]);
