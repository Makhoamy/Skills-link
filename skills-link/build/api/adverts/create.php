<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('POST');
$user = require_auth(['jobseeker']);

$in = body();

$title       = trim((string)($in['title'] ?? ''));
$description = trim((string)($in['description'] ?? ''));
$categoryId  = (int)($in['categoryId'] ?? 0);
$provinceId  = (int)($in['provinceId'] ?? 0);
$city        = trim((string)($in['city'] ?? ''));
$phone       = trim((string)($in['contactPhone'] ?? ''));
$rateRaw     = $in['rateAmount'] ?? null;
$periodRaw   = $in['ratePeriod'] ?? null;

// --- required fields -------------------------------------------------------
$missing = [];
if ($title === '')       $missing[] = 'headline';
if ($description === '') $missing[] = 'description';
if ($categoryId <= 0)    $missing[] = 'category';
if ($provinceId <= 0)    $missing[] = 'province';
if ($city === '')        $missing[] = 'city';
if ($phone === '')       $missing[] = 'contact phone';
if ($missing) fail('Please fill in: ' . implode(', ', $missing) . '.', 422);

// --- formats / lengths ------------------------------------------------------
if (mb_strlen($title) > 120)       fail('Headline is too long (max 120 characters).', 422);
if (mb_strlen($title) < 5)         fail('Headline must be at least 5 characters.', 422);
if (mb_strlen($description) > 800) fail('Description is too long (max 800 characters).', 422);
if (mb_strlen($description) < 20)  fail('Please describe your work in at least 20 characters.', 422);
if (mb_strlen($city) > 80)         fail('City is too long (max 80 characters).', 422);
if (!preg_match('/^\+?[0-9\s\-()]{9,20}$/', $phone) || strlen(preg_replace('/\D/', '', $phone)) < 9) {
    fail('Please enter a valid contact phone number.', 422);
}

// --- optional rate: amount and period must come together --------------------
$rateAmount = null;
$ratePeriod = null;
$hasAmount  = $rateRaw !== null && $rateRaw !== '';
$hasPeriod  = $periodRaw !== null && $periodRaw !== '';
if ($hasAmount || $hasPeriod) {
    if (!$hasAmount || !$hasPeriod) fail('Enter both a rate and what it is per (hour, day, week or job), or leave both empty.', 422);
    if (!is_numeric($rateRaw) || (float)$rateRaw <= 0 || (float)$rateRaw > 99999999) fail('Rate must be a number greater than 0.', 422);
    if (!in_array($periodRaw, ['hourly', 'daily', 'weekly', 'per_job'], true)) fail('Invalid rate period.', 422);
    $rateAmount = round((float)$rateRaw, 2);
    $ratePeriod = $periodRaw;
}

// --- referenced rows must exist ----------------------------------------------
if (!q('SELECT 1 FROM job_categories WHERE id = ?', [$categoryId])->fetch()) fail('Unknown category.', 422);
if (!q('SELECT 1 FROM provinces WHERE id = ?', [$provinceId])->fetch())      fail('Unknown province.', 422);

// an advert hangs off the worker profile (foreign key) - it must exist first
if (!q('SELECT 1 FROM worker_profiles WHERE user_id = ?', [$user['id']])->fetch()) {
    fail('Please complete and save your worker profile before posting an advert.', 422);
}

// keep the number of live adverts sensible
$live = (int)q("SELECT COUNT(*) FROM work_adverts WHERE worker_id = ? AND status = 'active' AND (expires_at IS NULL OR expires_at >= CURDATE())", [$user['id']])->fetchColumn();
if ($live >= 5) fail('You already have 5 active adverts. Please wait for one to expire before posting another.', 422);

q('INSERT INTO work_adverts (worker_id, category_id, title, description, province_id, city,
                             rate_amount, rate_period, contact_phone, status, expires_at)
   VALUES (?,?,?,?,?,?,?,?,?, "active", DATE_ADD(CURDATE(), INTERVAL 60 DAY))',
  [$user['id'], $categoryId, $title, $description, $provinceId, $city, $rateAmount, $ratePeriod, $phone]);

json_out(['success' => true, 'advertId' => (int)db()->lastInsertId()], 201);
