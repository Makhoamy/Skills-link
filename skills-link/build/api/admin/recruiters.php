<?php
require __DIR__ . '/../lib/bootstrap.php';
$method = require_method('GET', 'POST');
require_auth(['admin']);

if ($method === 'GET') {
    $status = (string)($_GET['status'] ?? 'pending');
    if (!in_array($status, ['unverified', 'pending', 'verified', 'rejected'], true)) fail('Invalid status');
    $rows = q('SELECT r.user_id, r.company_name, r.registration_number, r.website, r.city, r.verification_status,
                      r.created_at, u.email, (r.registration_doc_path IS NOT NULL) AS has_registration_doc
               FROM recruiter_profiles r JOIN users u ON u.id = r.user_id
               WHERE r.verification_status = ? AND u.deleted_at IS NULL
               ORDER BY r.created_at LIMIT 200', [$status])->fetchAll();
    foreach ($rows as &$r) $r['has_registration_doc'] = (bool)$r['has_registration_doc'];
    json_out(['items' => $rows]);
}

$b = body();
$uid = (int)($b['user_id'] ?? 0);
$status = (string)($b['status'] ?? '');
if (!in_array($status, ['verified', 'rejected'], true)) fail('Invalid status');
$st = q('UPDATE recruiter_profiles SET verification_status = ?, verified_at = IF(? = \'verified\', NOW(), NULL) WHERE user_id = ?',
        [$status, $status, $uid]);
if ($st->rowCount() === 0) fail('Recruiter not found or unchanged', 404);
json_out(['ok' => true]);
