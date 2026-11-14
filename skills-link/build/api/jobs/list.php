<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/db.php';

$keyword    = trim($_GET['keyword'] ?? '');
$provinceId = (int)($_GET['provinceId'] ?? 0);
$categoryId = (int)($_GET['categoryId'] ?? 0);
$page       = max(1, (int)($_GET['page'] ?? 1));
$perPage    = 20;
$offset     = ($page - 1) * $perPage;

$where = ["j.status = 'open'"];
$params = [];

if ($keyword !== '') {
    $where[] = 'MATCH(j.title, j.description) AGAINST (:kw IN NATURAL LANGUAGE MODE)';
    $params['kw'] = $keyword;
}
if ($provinceId) {
    $where[] = 'j.province_id = :prov';
    $params['prov'] = $provinceId;
}
if ($categoryId) {
    $where[] = 'j.category_id = :cat';
    $params['cat'] = $categoryId;
}

$whereSql = implode(' AND ', $where);

$pdo = db();
$sql = "SELECT j.id, j.title, j.city, j.employment_type, j.pay_amount, j.pay_period,
               j.below_nmw_flag, j.created_at,
               jc.name AS category_name, p.name AS province_name,
               rp.company_name, rp.avg_rating AS recruiter_rating
        FROM jobs j
        JOIN job_categories jc ON jc.id = j.category_id
        JOIN provinces p ON p.id = j.province_id
        JOIN recruiter_profiles rp ON rp.user_id = j.recruiter_id
        WHERE {$whereSql}
        ORDER BY j.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue('offset', $offset, PDO::PARAM_INT);
$stmt->execute();

respond(['success' => true, 'jobs' => $stmt->fetchAll(), 'page' => $page]);
