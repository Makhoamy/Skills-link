<?php
// The signed-in recruiter's own jobs, with how many people applied to each.
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
$user = require_auth(['recruiter']);

// The applicant counts follow the same rules as jobs/applicants.php so the numbers match the list.
$rows = q("SELECT j.id, j.title, j.city, j.status, j.employment_type, j.pay_amount, j.pay_period, j.created_at,
                  COUNT(a.id) AS applicant_count,
                  COALESCE(SUM(a.status = 'applied'), 0) AS new_count
           FROM jobs j
           LEFT JOIN (SELECT ja.id, ja.job_id, ja.status
                      FROM job_applications ja
                      JOIN users u ON u.id = ja.worker_id AND u.deleted_at IS NULL AND u.is_active = 1) a ON a.job_id = j.id
           WHERE j.recruiter_id = ?
           GROUP BY j.id
           ORDER BY j.created_at DESC
           LIMIT 200", [$user['id']])->fetchAll();

foreach ($rows as &$r) {
    $r['id'] = (int)$r['id'];
    $r['applicant_count'] = (int)$r['applicant_count'];
    $r['new_count'] = (int)$r['new_count'];
}

json_out(['items' => $rows]);
