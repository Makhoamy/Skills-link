<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

$user = require_auth();
$in = json_input();

$jobId      = isset($in['jobId']) ? (int)$in['jobId'] : null;
$revieweeId = (int)($in['revieweeUserId'] ?? 0);
$rating     = (int)($in['rating'] ?? 0);
$comment    = trim($in['comment'] ?? '');

if (!$revieweeId || $rating < 1 || $rating > 5) {
    fail('A valid revieweeUserId and rating (1-5) are required.');
}
if ($revieweeId === (int)$user['id']) {
    fail('You cannot review yourself.');
}

$pdo = db();

// only people who worked together on a job can review each other
if (!$jobId) {
    fail('A job is required to leave a review.');
}

$job = $pdo->prepare('SELECT recruiter_id FROM jobs WHERE id = :id');
$job->execute(['id' => $jobId]);
$recruiterId = $job->fetchColumn();
if (!$recruiterId) {
    fail('Job not found.', 404);
}

if ($user['role'] === 'jobseeker') {
    if ($revieweeId !== (int)$recruiterId) {
        fail('You can only review the employer of this job.', 403);
    }
    $hired = $pdo->prepare("SELECT 1 FROM job_applications WHERE job_id = :job AND worker_id = :w AND status = 'hired'");
    $hired->execute(['job' => $jobId, 'w' => $user['id']]);
    if (!$hired->fetch()) {
        fail('You can only review an employer after being hired for the job.', 403);
    }
} elseif ($user['role'] === 'recruiter' && (int)$recruiterId === (int)$user['id']) {
    $hired = $pdo->prepare("SELECT 1 FROM job_applications WHERE job_id = :job AND worker_id = :w AND status = 'hired'");
    $hired->execute(['job' => $jobId, 'w' => $revieweeId]);
    if (!$hired->fetch()) {
        fail('You can only review someone you hired for this job.', 403);
    }
} else {
    fail('You cannot review for this job.', 403);
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare(
        'INSERT INTO reviews (job_id, reviewer_user_id, reviewee_user_id, rating, comment)
         VALUES (:job, :rev, :revee, :rating, :comment)'
    );
    $stmt->execute([
        'job' => $jobId,
        'rev' => $user['id'],
        'revee' => $revieweeId,
        'rating' => $rating,
        'comment' => $comment ?: null,
    ]);

    $revieweeRole = $pdo->prepare('SELECT role FROM users WHERE id = :id');
    $revieweeRole->execute(['id' => $revieweeId]);
    $role = $revieweeRole->fetchColumn();

    $table = $role === 'recruiter' ? 'recruiter_profiles' : 'worker_profiles';
    $agg = $pdo->prepare('SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM reviews WHERE reviewee_user_id = :id');
    $agg->execute(['id' => $revieweeId]);
    $row = $agg->fetch();

    $upd = $pdo->prepare("UPDATE {$table} SET avg_rating = :avg, rating_count = :cnt WHERE user_id = :id");
    $upd->execute(['avg' => round((float)$row['avg_r'], 2), 'cnt' => (int)$row['cnt'], 'id' => $revieweeId]);

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    if (str_contains($e->getMessage(), 'uq_one_review_per_job')) {
        fail('You have already reviewed this person for this job.', 409);
    }
    fail('Could not save review.', 500);
}

respond(['success' => true], 201);
