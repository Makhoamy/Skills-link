<?php
require __DIR__ . '/../lib/bootstrap.php';
$method = require_method('GET', 'POST');
$user = require_auth(['jobseeker', 'recruiter']);

if ($method === 'GET') {
    $marketing = (bool)q('SELECT marketing_consent FROM users WHERE id = ?', [$user['id']])->fetchColumn();
    $equity = $user['role'] === 'jobseeker'
        ? q('SELECT ethnicity, has_disability, disability_detail, consented_at FROM worker_equity_info WHERE user_id = ?', [$user['id']])->fetch()
        : false;
    json_out(['marketing' => $marketing, 'equity' => $equity ?: null]);
}

$b = body();
$type  = (string)($b['type'] ?? '');
$given = filter_var($b['given'] ?? false, FILTER_VALIDATE_BOOLEAN);

if ($type === 'marketing') {
    q('UPDATE users SET marketing_consent = ? WHERE id = ?', [$given ? 1 : 0, $user['id']]);
    log_consent($user['id'], 'marketing', $given);
    json_out(['ok' => true]);
}

if ($type === 'equity_info') {
    if ($user['role'] !== 'jobseeker') fail('Forbidden', 403);
    if (!q('SELECT 1 FROM worker_profiles WHERE user_id = ?', [$user['id']])->fetch()) fail('Please complete your profile first', 422);

    if (!$given) {
        q('DELETE FROM worker_equity_info WHERE user_id = ?', [$user['id']]);
        log_consent($user['id'], 'equity_info', false);
        json_out(['ok' => true]);
    }
    $eth = trim((string)($b['ethnicity'] ?? ''));
    $dis = (string)($b['has_disability'] ?? '');
    $det = trim((string)($b['disability_detail'] ?? ''));
    if (mb_strlen($eth) > 40 || mb_strlen($det) > 255) fail('Value too long');
    if ($dis !== '' && !in_array($dis, ['Yes', 'No', 'Prefer not to say'], true)) fail('Invalid disability value');

    $already = q('SELECT 1 FROM worker_equity_info WHERE user_id = ?', [$user['id']])->fetch();
    q('INSERT INTO worker_equity_info (user_id, ethnicity, has_disability, disability_detail, consented_at)
       VALUES (?,?,?,?,NOW())
       ON DUPLICATE KEY UPDATE ethnicity = VALUES(ethnicity), has_disability = VALUES(has_disability),
                               disability_detail = VALUES(disability_detail)',
      [$user['id'], $eth ?: null, $dis ?: null, $det ?: null]);
    if (!$already) log_consent($user['id'], 'equity_info', true);
    json_out(['ok' => true]);
}
fail('Invalid consent type');
