<?php
require __DIR__ . '/../lib/bootstrap.php';
$method = require_method('GET', 'POST');
$user = require_auth(['jobseeker']);
$pdo  = db();

if ($method === 'GET') {
    $p = q('SELECT user_id, first_name, last_name, gender, date_of_birth, citizenship,
                   (id_number_encrypted IS NOT NULL) AS has_id_number, drivers_license,
                   province_id, city, suburb, bio, years_experience, availability,
                   (profile_photo_path IS NOT NULL) AS has_photo, (cv_path IS NOT NULL) AS has_cv,
                   avg_rating, rating_count
            FROM worker_profiles WHERE user_id = ?', [$user['id']])->fetch();
    if ($p) { foreach (['has_id_number', 'has_photo', 'has_cv'] as $k) $p[$k] = (bool)$p[$k]; }
    $skills = q('SELECT category_id FROM worker_skills WHERE user_id = ?', [$user['id']])->fetchAll(PDO::FETCH_COLUMN);
    json_out(['profile' => $p ?: null, 'skill_ids' => array_map('intval', $skills)]);
}

$b = body();
$fields = [];

foreach (['first_name' => 80, 'last_name' => 80, 'drivers_license' => 40, 'city' => 80, 'suburb' => 80, 'bio' => 600] as $col => $max) {
    if (!array_key_exists($col, $b)) continue;
    $v = trim((string)$b[$col]);
    if (mb_strlen($v) > $max) fail("$col is too long (max $max)");
    if ($v === '' && in_array($col, ['first_name', 'last_name'], true)) fail("$col is required");
    $fields[$col] = $v === '' ? null : $v;
}

$enums = [
    'gender'       => ['Female', 'Male', 'Other', 'Prefer not to say'],
    'citizenship'  => ['South African', 'Non-SA with work permit', 'Non-SA, no work permit'],
    'availability' => ['Immediately', 'Within 2 weeks', 'Temporary/casual only', 'Not available'],
];
foreach ($enums as $col => $allowed) {
    if (!array_key_exists($col, $b)) continue;
    if ($b[$col] === '' || $b[$col] === null) { $fields[$col] = null; continue; }
    if (!in_array($b[$col], $allowed, true)) fail("Invalid $col");
    $fields[$col] = $b[$col];
}

if (array_key_exists('date_of_birth', $b)) {
    $d = (string)$b['date_of_birth'];
    if ($d === '') { $fields['date_of_birth'] = null; }
    else {
        if (!valid_date($d) || $d > date('Y-m-d')) fail('Invalid date of birth');
        $fields['date_of_birth'] = $d;
    }
}
if (array_key_exists('province_id', $b)) {
    if ($b['province_id'] === null || $b['province_id'] === '') { $fields['province_id'] = null; }
    else {
        $pid = (int)$b['province_id'];
        if (!q('SELECT 1 FROM provinces WHERE id = ?', [$pid])->fetch()) fail('Invalid province');
        $fields['province_id'] = $pid;
    }
}
if (array_key_exists('years_experience', $b)) {
    if ($b['years_experience'] === null || $b['years_experience'] === '') { $fields['years_experience'] = null; }
    else {
        $y = (int)$b['years_experience'];
        if ($y < 0 || $y > 60) fail('Invalid years of experience');
        $fields['years_experience'] = $y;
    }
}

$existing = q('SELECT first_name, last_name, gender, date_of_birth, citizenship, province_id, city,
                      years_experience, availability, (id_number_encrypted IS NOT NULL) AS has_id
               FROM worker_profiles WHERE user_id = ?', [$user['id']])->fetch();

// ---- required fields: whatever is sent (or already saved) must not be empty ----
$required = [
    'first_name'       => 'first name',
    'last_name'        => 'last name',
    'gender'           => 'gender',
    'date_of_birth'    => 'date of birth',
    'citizenship'      => 'citizenship',
    'province_id'      => 'province',
    'city'             => 'city / town',
    'years_experience' => 'years of experience',
    'availability'     => 'availability',
];
$missing = [];
foreach ($required as $col => $label) {
    $value = array_key_exists($col, $fields) ? $fields[$col] : ($existing[$col] ?? null);
    if ($value === null || $value === '') $missing[] = $label;
}
$hasIdAlready = $existing && !empty($existing['has_id']);
if (trim((string)($b['id_number'] ?? '')) === '' && !$hasIdAlready) $missing[] = 'ID / passport number';
if ($missing) fail('Please fill in: ' . implode(', ', $missing) . '.', 422);

foreach (['first_name' => 'First name', 'last_name' => 'Last name'] as $col => $label) {
    if (!empty($fields[$col]) && mb_strlen($fields[$col]) < 2) fail("$label must be at least 2 characters.", 422);
    if (!empty($fields[$col]) && !preg_match('/^[\p{L}][\p{L}\s\'’.\-]*$/u', $fields[$col])) fail("$label contains invalid characters.", 422);
}
if (!empty($fields['date_of_birth'])) {
    $age = (new DateTime($fields['date_of_birth']))->diff(new DateTime('today'))->y;
    if ($age < 16) fail('You must be at least 16 years old.', 422);
    if ($age > 100) fail('Invalid date of birth.', 422);
}

if (isset($b['id_number']) && trim((string)$b['id_number']) !== '') {
    $id = preg_replace('/\s+/', '', (string)$b['id_number']);
    $citizenship = $fields['citizenship'] ?? ($existing['citizenship'] ?? null);
    if ($citizenship === 'South African') {
        if (!preg_match('/^\d{13}$/', $id)) fail('South African ID numbers have 13 digits');
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $n = (int)$id[$i];
            if ($i % 2 === 1) { $n *= 2; if ($n > 9) $n -= 9; }
            $sum += $n;
        }
        if ($sum % 10 !== 0) fail('That ID number is not valid');
    } elseif (!preg_match('/^[A-Za-z0-9]{6,20}$/', $id)) {
        fail('Invalid passport/permit number');
    }
    try {
        $fields['id_number_encrypted'] = sl_encrypt($id);
        $fields['id_number_hash']      = id_hash(strtoupper($id));
    } catch (RuntimeException $e) {
        error_log((string)$e);
        fail('Server is not configured to store ID numbers securely (set ENCRYPTION_KEY and HASH_PEPPER).', 500);
    }
}

$skillIds = null;
if (array_key_exists('skill_ids', $b)) {
    if (!is_array($b['skill_ids'])) fail('skill_ids must be a list');
    $skillIds = array_values(array_unique(array_map('intval', $b['skill_ids'])));
    if (count($skillIds) > 10) fail('Please choose at most 10 skills');
    if ($skillIds) {
        $in = implode(',', array_fill(0, count($skillIds), '?'));
        if ((int)q("SELECT COUNT(*) FROM job_categories WHERE id IN ($in)", $skillIds)->fetchColumn() !== count($skillIds)) {
            fail('Unknown skill selected');
        }
    }
}

try {
    $pdo->beginTransaction();
    if (!$existing) {
        if (empty($fields['first_name']) || empty($fields['last_name'])) { $pdo->rollBack(); fail('First and last name are required'); }
        q('INSERT INTO worker_profiles (user_id, first_name, last_name) VALUES (?,?,?)',
          [$user['id'], $fields['first_name'], $fields['last_name']]);
    }
    if ($fields) {
        $setParts = [];
        foreach ($fields as $column => $value) {
            $setParts[] = "$column = ?";
        }
        $set = implode(', ', $setParts);
        q("UPDATE worker_profiles SET $set WHERE user_id = ?", array_merge(array_values($fields), [$user['id']]));
    }
    if ($skillIds !== null) {
        q('DELETE FROM worker_skills WHERE user_id = ?', [$user['id']]);
        foreach ($skillIds as $sid) q('INSERT INTO worker_skills (user_id, category_id) VALUES (?,?)', [$user['id'], $sid]);
    }
    $pdo->commit();
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (($e->errorInfo[1] ?? 0) === 1062) fail('That ID number is already registered', 409);
    throw $e;
}
json_out(['ok' => true]);
