<?php

require __DIR__ . '/../lib/bootstrap.php';
require __DIR__ . '/../lib/backup.php';

q("UPDATE work_adverts SET status = 'expired' WHERE status = 'active' AND expires_at IS NOT NULL AND expires_at < CURDATE()");
q("UPDATE jobs SET status = 'closed' WHERE status = 'open' AND closes_at IS NOT NULL AND closes_at < CURDATE()");
q('DELETE FROM auth_tokens WHERE expires_at < NOW()');

$days = max(1, (int)(getenv('PURGE_AFTER_DAYS') ?: 30));
$ids = q('SELECT id FROM users WHERE deleted_at IS NOT NULL AND deleted_at < (NOW() - INTERVAL ? DAY)', [$days])->fetchAll(PDO::FETCH_COLUMN);
$dir = upload_dir();
$purged = 0;

foreach ($ids as $uid) {
    $uid = (int)$uid;
    $files = array_merge(
        q('SELECT cv_path, profile_photo_path FROM worker_profiles WHERE user_id = ?', [$uid])->fetch() ?: [],
        q('SELECT registration_doc_path FROM recruiter_profiles WHERE user_id = ?', [$uid])->fetch() ?: []
    );
    $affected = q('SELECT DISTINCT reviewee_user_id FROM reviews WHERE reviewer_user_id = ?', [$uid])->fetchAll(PDO::FETCH_COLUMN);

    q('DELETE FROM users WHERE id = ?', [$uid]);
    foreach ($files as $f) { if ($f) @unlink($dir . '/' . basename((string)$f)); }

    foreach ($affected as $rid) {
        foreach (['worker_profiles', 'recruiter_profiles'] as $t) {
            q("UPDATE $t p SET avg_rating = COALESCE((SELECT AVG(rating) FROM reviews WHERE reviewee_user_id = p.user_id), 0),
                               rating_count = (SELECT COUNT(*) FROM reviews WHERE reviewee_user_id = p.user_id)
               WHERE p.user_id = ?", [(int)$rid]);
        }
    }
    $purged++;
}
echo date('c') . " maintenance done, purged $purged account(s)\n";

// nightly backup (database + uploads); only the newest BACKUP_KEEP automatic ones are kept
try {
    $b = backup_create('auto');
    $removed = backup_prune_auto((int)(getenv('BACKUP_KEEP') ?: 14));
    echo date('c') . " backup {$b['name']} ({$b['size']} bytes), removed $removed old automatic backup(s)\n";
} catch (Throwable $e) {
    error_log('[backup] ' . $e->getMessage());
    echo date('c') . " BACKUP FAILED: " . $e->getMessage() . "\n";
}
