<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth_middleware.php';

$user = require_auth();
require_role($user, 'recruiter');

$in = json_input();
$title       = trim($in['title'] ?? '');
$description = trim($in['description'] ?? '');
$categoryId  = (int)($in['categoryId'] ?? 0);
$provinceId  = (int)($in['provinceId'] ?? 0);
$city        = trim($in['city'] ?? '');
$employmentType = $in['employmentType'] ?? '';
$payAmount   = (float)($in['payAmount'] ?? 0);
$payPeriod   = $in['payPeriod'] ?? '';

$validTypes = ['full_time', 'part_time', 'temporary', 'once_off'];
$validPeriods = ['hourly', 'daily', 'weekly', 'monthly'];

if ($title === '' || $description === '' || !$categoryId || !$provinceId || $city === '') {
    fail('Title, description, category, province and city are all required.');
}
if (!in_array($employmentType, $validTypes, true)) {
    fail('Invalid employment type.');
}
if (!in_array($payPeriod, $validPeriods, true) || $payAmount <= 0) {
    fail('Invalid pay amount/period.');
}

$pdo = db();

$hasProfile = $pdo->prepare('SELECT 1 FROM recruiter_profiles WHERE user_id = :id');
$hasProfile->execute(['id' => $user['id']]);
if (!$hasProfile->fetch()) {
    fail('Please complete your company profile before posting a job.', 422);
}

$rate = $pdo->query('SELECT hourly_rate_zar FROM nmw_rates WHERE effective_from <= CURDATE() ORDER BY effective_from DESC LIMIT 1')->fetchColumn();

if ($payPeriod === 'hourly') {
    $hourlyEquivalent = $payAmount;
} elseif ($payPeriod === 'daily') {
    $hourlyEquivalent = $payAmount / 9;
} elseif ($payPeriod === 'weekly') {
    $hourlyEquivalent = $payAmount / 45;
} else {
    $hourlyEquivalent = $payAmount / 195;
}

$belowNmw = $hourlyEquivalent < (float)$rate ? 1 : 0;

$stmt = $pdo->prepare(
    'INSERT INTO jobs (recruiter_id, category_id, title, description, province_id, city,
                        employment_type, pay_amount, pay_period, below_nmw_flag, status)
     VALUES (:rid, :cat, :title, :desc, :prov, :city, :etype, :pay, :period, :flag, "open")'
);
$stmt->execute([
    'rid'    => $user['id'],
    'cat'    => $categoryId,
    'title'  => $title,
    'desc'   => $description,
    'prov'   => $provinceId,
    'city'   => $city,
    'etype'  => $employmentType,
    'pay'    => $payAmount,
    'period' => $payPeriod,
    'flag'   => $belowNmw,
]);

respond([
    'success'      => true,
    'jobId'        => (int)$pdo->lastInsertId(),
    'belowNmwFlag' => (bool)$belowNmw,
    'message'      => $belowNmw
        ? 'Job posted, but the pay rate you entered is below the current National Minimum Wage. Please review before it goes live.'
        : 'Job posted.',
], 201);
