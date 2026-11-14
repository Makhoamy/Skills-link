<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    fail('Job id is required.');
}

$pdo = db();
$stmt = $pdo->prepare(
    'SELECT j.*, jc.name AS category_name, p.name AS province_name,
            rp.company_name, rp.avg_rating AS recruiter_rating, rp.rating_count AS recruiter_rating_count
     FROM jobs j
     JOIN job_categories jc ON jc.id = j.category_id
     JOIN provinces p ON p.id = j.province_id
     JOIN recruiter_profiles rp ON rp.user_id = j.recruiter_id
     WHERE j.id = :id'
);
$stmt->execute(['id' => $id]);
$job = $stmt->fetch();

if (!$job) {
    fail('Job not found.', 404);
}

$reviews = $pdo->prepare(
    'SELECT r.rating, r.comment, r.created_at, u.id AS reviewer_id
     FROM reviews r JOIN users u ON u.id = r.reviewer_user_id
     WHERE r.job_id = :jid ORDER BY r.created_at DESC LIMIT 10'
);
$reviews->execute(['jid' => $id]);

respond(['success' => true, 'job' => $job, 'reviews' => $reviews->fetchAll()]);
