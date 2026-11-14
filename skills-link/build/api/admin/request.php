<?php
require __DIR__ . '/../lib/bootstrap.php';
$method = require_method('GET', 'POST');
$admin = require_auth(['admin']);

if ($method === 'GET') {
    $status = (string)($_GET['status'] ?? '');
    $where = in_array($status, ['pending', 'in_progress', 'completed', 'rejected'], true) ? 'WHERE r.status = ?' : '';
    $rows = q("SELECT r.id, r.user_id, r.request_type, r.status, r.notes, r.requested_at, r.resolved_at, u.email
               FROM data_subject_requests r LEFT JOIN users u ON u.id = r.user_id $where
               ORDER BY FIELD(r.status,'pending','in_progress','completed','rejected'), r.requested_at DESC LIMIT 200",
              $where ? [$status] : [])->fetchAll();
    json_out(['items' => $rows]);
}

$b = body();
$id = (int)($b['id'] ?? 0);
$status = (string)($b['status'] ?? '');
$notes = isset($b['notes']) ? trim((string)$b['notes']) : null;
if (!in_array($status, ['in_progress', 'completed', 'rejected'], true)) fail('Invalid status');

$req = q('SELECT id, user_id, request_type, status FROM data_subject_requests WHERE id = ?', [$id])->fetch();
if (!$req) fail('Request not found', 404);
if (in_array($req['status'], ['completed', 'rejected'], true)) fail('Request already closed', 409);

$pdo = db();
$pdo->beginTransaction();
q('UPDATE data_subject_requests SET status = ?, notes = COALESCE(?, notes),
          resolved_at = IF(? IN (\'completed\',\'rejected\'), NOW(), NULL) WHERE id = ?',
  [$status, $notes, $status, $id]);

if ($status === 'completed' && $req['request_type'] === 'deletion' && $req['user_id']) {
    $uid = (int)$req['user_id'];
    q('UPDATE users SET is_active = 0, deleted_at = COALESCE(deleted_at, NOW()) WHERE id = ?', [$uid]);
    q('DELETE FROM auth_tokens WHERE user_id = ?', [$uid]);
    q("UPDATE work_adverts SET status = 'paused' WHERE worker_id = ?", [$uid]);
    q("UPDATE jobs SET status = 'closed' WHERE recruiter_id = ? AND status = 'open'", [$uid]);
}
$pdo->commit();
json_out(['ok' => true]);
