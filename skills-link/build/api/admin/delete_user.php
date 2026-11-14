<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');
$admin = require_auth(['admin']);

$b = body();
$uid = (int)($b['user_id'] ?? 0);
if ($uid <= 0) fail('user_id is required');
if ($uid === $admin['id']) fail('You cannot delete your own account.');

$user = q('SELECT id FROM users WHERE id = ?', [$uid])->fetch();
if (!$user) fail('User not found', 404);

// get the file names first because the rows are gone after the delete
$files = array_merge(
    q('SELECT cv_path, profile_photo_path FROM worker_profiles WHERE user_id = ?', [$uid])->fetch() ?: [],
    q('SELECT registration_doc_path FROM recruiter_profiles WHERE user_id = ?', [$uid])->fetch() ?: []
);
$affected = q('SELECT DISTINCT reviewee_user_id FROM reviews WHERE reviewer_user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN);

// the foreign keys delete their profile, jobs, applications, tokens and login history too
q('DELETE FROM users WHERE id = ?', [$uid]);

$dir = getenv('UPLOAD_DIR') ?: '';
if ($dir !== '') {
    foreach ($files as $f) {
        if ($f) @unlink(rtrim($dir, '/') . '/' . basename((string)$f));
    }
}

// ratings of people they reviewed need to be worked out again
foreach ($affected as $rid) {
    foreach (['worker_profiles', 'recruiter_profiles'] as $t) {
        q("UPDATE $t p SET avg_rating = COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_user_id = p.user_id), 0),
                           rating_count = (SELECT COUNT(*) FROM reviews WHERE reviewee_user_id = p.user_id)
           WHERE p.user_id = ?", [(int)$rid]);
    }
}

json_out(['ok' => true]);
