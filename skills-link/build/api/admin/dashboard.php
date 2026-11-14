<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
require_auth(['admin']);

$days = (int)($_GET['days'] ?? 30);
$role = (string)($_GET['role'] ?? '');
$search = trim((string)($_GET['search'] ?? ''));

$where = ['1 = 1'];
$params = [];

if ($days > 0) {
    $where[] = 'l.loginDate >= (NOW() - INTERVAL ? DAY)';
    $params[] = $days;
}
if (in_array($role, ['jobseeker', 'recruiter', 'admin'], true)) {
    $where[] = 'u.role = ?';
    $params[] = $role;
}
if ($search !== '') {
    $where[] = 'u.email LIKE ?';
    $params[] = '%' . addcslashes($search, '%_\\') . '%';
}
$whereSql = implode(' AND ', $where);

// newest logins first, max 500 rows
$history = q("SELECT l.loginID, u.email, u.role, l.loginDate, l.ipAddress
              FROM loginHistory l
              JOIN users u ON u.id = l.userID
              WHERE $whereSql
              ORDER BY l.loginDate DESC, l.loginID DESC
              LIMIT 500", $params)->fetchAll();

for ($i = 0; $i < count($history); $i++) {
    $history[$i]['loginID'] = (int)$history[$i]['loginID'];
}

// numbers for the cards at the top of the page
$userWhere = ['1 = 1'];
$userParams = [];
if (in_array($role, ['jobseeker', 'recruiter', 'admin'], true)) {
    $userWhere[] = 'u.role = ?';
    $userParams[] = $role;
}
if ($search !== '') {
    $userWhere[] = 'u.email LIKE ?';
    $userParams[] = '%' . addcslashes($search, '%_\\') . '%';
}
$users = q('SELECT u.id, u.email, u.role, u.created_at, COUNT(l.loginID) AS loginCount, MAX(l.loginDate) AS lastLogin
            FROM users u
            LEFT JOIN loginHistory l ON l.userID = u.id
            WHERE ' . implode(' AND ', $userWhere) . '
            GROUP BY u.id
            ORDER BY u.created_at DESC
            LIMIT 500', $userParams)->fetchAll();
for ($i = 0; $i < count($users); $i++) {
    $users[$i]['id'] = (int)$users[$i]['id'];
    $users[$i]['loginCount'] = (int)$users[$i]['loginCount'];
}

$totalUsers = (int)q('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn();
$totalJobseekers = (int)q("SELECT COUNT(*) FROM users WHERE role = 'jobseeker' AND deleted_at IS NULL")->fetchColumn();
$totalRecruiters = (int)q("SELECT COUNT(*) FROM users WHERE role = 'recruiter' AND deleted_at IS NULL")->fetchColumn();
$totalSignIns = (int)q('SELECT COUNT(*) FROM loginHistory')->fetchColumn();
$loginsToday = (int)q('SELECT COUNT(*) FROM loginHistory WHERE DATE(loginDate) = CURDATE()')->fetchColumn();
$activeLast7Days = (int)q('SELECT COUNT(DISTINCT userID) FROM loginHistory WHERE loginDate >= (NOW() - INTERVAL 7 DAY)')->fetchColumn();

json_out([
    'success' => true,
    'totalUsers' => $totalUsers,
    'totalJobseekers' => $totalJobseekers,
    'totalRecruiters' => $totalRecruiters,
    'totalSignIns' => $totalSignIns,
    'loginsToday' => $loginsToday,
    'activeLast7Days' => $activeLast7Days,
    'loginHistory' => $history,
    'users' => $users,
]);
