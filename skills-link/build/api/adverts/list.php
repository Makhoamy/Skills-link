<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
require_auth(['recruiter', 'admin']);
[$page, $per, $offset] = page_params(20);

$where  = ["a.status = 'active'", "(a.expires_at IS NULL OR a.expires_at >= CURDATE())",
           'u.deleted_at IS NULL', 'u.is_active = 1'];
$params = [];
if (!empty($_GET['province_id'])) { $where[] = 'a.province_id = ?'; $params[] = (int)$_GET['province_id']; }
if (!empty($_GET['category_id'])) { $where[] = 'a.category_id = ?'; $params[] = (int)$_GET['category_id']; }
if (!empty($_GET['city']))        { $where[] = 'a.city = ?';        $params[] = trim((string)$_GET['city']); }
if (!empty($_GET['q'])) {
    $like = '%' . addcslashes(trim((string)$_GET['q']), '%_\\') . '%';
    $where[] = '(a.title LIKE ? OR a.description LIKE ?)';
    array_push($params, $like, $like);
}
$from = 'FROM work_adverts a
         JOIN worker_profiles w ON w.user_id = a.worker_id
         JOIN users u ON u.id = w.user_id
         JOIN job_categories c ON c.id = a.category_id
         WHERE ' . implode(' AND ', $where);

$total = (int)q("SELECT COUNT(*) $from", $params)->fetchColumn();
$items = q("SELECT a.id, a.worker_id, a.title, a.description, a.city, a.province_id, a.category_id,
                   c.name AS category, a.rate_amount, a.rate_period, a.contact_phone,
                   a.created_at, w.first_name, w.avg_rating, w.rating_count
            $from ORDER BY a.created_at DESC LIMIT ? OFFSET ?", array_merge($params, [$per, $offset]))->fetchAll();

json_out(['items' => $items, 'total' => $total, 'page' => $page, 'per_page' => $per]);
