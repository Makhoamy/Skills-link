<?php
require __DIR__ . '/../lib/bootstrap.php';
$method = require_method('GET', 'POST');
$user = require_auth(['recruiter']);

if ($method === 'GET') {
    $p = q('SELECT user_id, company_name, registration_number, company_description, address, city,
                   province_id, zip_code, website, contact_first_name, contact_last_name, contact_position,
                   verification_status, verified_at, avg_rating, rating_count,
                   (registration_doc_path IS NOT NULL) AS has_registration_doc
            FROM recruiter_profiles WHERE user_id = ?', [$user['id']])->fetch();
    if ($p) $p['has_registration_doc'] = (bool)$p['has_registration_doc'];
    json_out(['profile' => $p ?: null]);
}

$b = body();
$fields = [];
$limits = ['company_name' => 150, 'registration_number' => 50, 'company_description' => 1000, 'address' => 200,
           'city' => 80, 'zip_code' => 10, 'website' => 255, 'contact_first_name' => 80,
           'contact_last_name' => 80, 'contact_position' => 80];
foreach ($limits as $col => $max) {
    if (!array_key_exists($col, $b)) continue;
    $v = trim((string)$b[$col]);
    if (mb_strlen($v) > $max) fail("$col is too long (max $max)", 422);
    if ($v === '' && $col === 'company_name') fail('Company name is required', 422);
    if ($col === 'website' && $v !== '' && !preg_match('#^https?://#i', $v)) $v = 'https://' . $v;
    if ($col === 'website' && $v !== '' && !filter_var($v, FILTER_VALIDATE_URL)) fail('Invalid website address', 422);
    $fields[$col] = $v === '' ? null : $v;
}
if (array_key_exists('province_id', $b)) {
    if ($b['province_id'] === null || $b['province_id'] === '') { $fields['province_id'] = null; }
    else {
        $pid = (int)$b['province_id'];
        if (!q('SELECT 1 FROM provinces WHERE id = ?', [$pid])->fetch()) fail('Invalid province');
        $fields['province_id'] = $pid;
    }
}

$existing = q('SELECT registration_number, city, province_id, contact_first_name, contact_last_name
               FROM recruiter_profiles WHERE user_id = ?', [$user['id']])->fetch();

// ---- required fields: whatever is sent (or already saved) must not be empty ----
$required = ['company_name' => 'company name', 'city' => 'city', 'province_id' => 'province',
             'contact_first_name' => 'contact first name', 'contact_last_name' => 'contact last name'];
$missing = [];
foreach ($required as $col => $label) {
    $value = array_key_exists($col, $fields) ? $fields[$col] : ($existing[$col] ?? null);
    if ($value === null || $value === '') $missing[] = $label;
}
if ($missing) fail('Please fill in: ' . implode(', ', $missing) . '.', 422);
foreach (['contact_first_name' => 'Contact first name', 'contact_last_name' => 'Contact last name'] as $col => $label) {
    if (!empty($fields[$col]) && !preg_match('/^[\\p{L}][\\p{L}\\s\'’.\\-]*$/u', $fields[$col])) fail("$label contains invalid characters.", 422);
}
if (!empty($fields['zip_code']) && !preg_match('/^[A-Za-z0-9 \\-]{3,10}$/', $fields['zip_code'])) fail('Invalid postal code.', 422);
if (!$existing) {
    if (empty($fields['company_name'])) fail('Company name is required');
    q('INSERT INTO recruiter_profiles (user_id, company_name) VALUES (?,?)', [$user['id'], $fields['company_name']]);
} elseif (array_key_exists('registration_number', $fields) && $fields['registration_number'] !== $existing['registration_number']) {
    $fields['verification_status'] = 'pending';
    $fields['verified_at'] = null;
}
if ($fields) {
    $setParts = [];
    foreach ($fields as $column => $value) {
        $setParts[] = "$column = ?";
    }
    $set = implode(', ', $setParts);
    q("UPDATE recruiter_profiles SET $set WHERE user_id = ?", array_merge(array_values($fields), [$user['id']]));
}
json_out(['ok' => true]);
