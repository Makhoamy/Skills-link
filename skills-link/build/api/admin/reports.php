<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
require_auth(['admin']);

/*
 * One endpoint, four reports (the login report is admin/dashboard.php):
 *   ?type=users         registrations per month
 *   ?type=jobs          jobs per category
 *   ?type=applications  applications per job
 *   ?type=provinces     snapshot by province
 * Optional ?from=YYYY-MM-DD&to=YYYY-MM-DD limits users / jobs / applications.
 * Every report answers { title, description, columns[], rows[], summary[] } so the page can draw any of them.
 */

$type = (string)($_GET['type'] ?? '');
$from = (string)($_GET['from'] ?? '');
$to = (string)($_GET['to'] ?? '');
if ($from !== '' && !valid_date($from)) fail('Invalid "from" date');
if ($to !== '' && !valid_date($to)) fail('Invalid "to" date');
if ($from !== '' && $to !== '' && $from > $to) fail('"From" date must be before "To" date');

/** SQL + params limiting a timestamp column to the chosen dates (inclusive). */
function date_filter($col, $from, $to)
{
    $sql = '';
    $params = [];
    if ($from !== '') { $sql .= " AND $col >= ?"; $params[] = $from . ' 00:00:00'; }
    if ($to !== '')   { $sql .= " AND $col <= ?"; $params[] = $to . ' 23:59:59'; }
    return [$sql, $params];
}

function ints(array $rows, array $keys)
{
    foreach ($rows as &$r) {
        foreach ($keys as $k) $r[$k] = (int)$r[$k];
    }
    return $rows;
}

function sum(array $rows, $key)
{
    return array_sum(array_map('intval', array_column($rows, $key)));
}

function pct($part, $whole)
{
    return $whole > 0 ? round($part * 100 / $whole, 1) . '%' : '0%';
}

if ($type === 'users') {
    [$f, $p] = date_filter('created_at', $from, $to);
    $rows = q("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
                      SUM(role = 'jobseeker') AS jobseekers,
                      SUM(role = 'recruiter') AS recruiters,
                      SUM(role = 'admin') AS admins,
                      COUNT(*) AS total,
                      SUM(is_verified = 1) AS verified
               FROM users WHERE deleted_at IS NULL $f
               GROUP BY month ORDER BY month DESC", $p)->fetchAll();
    $rows = ints($rows, ['jobseekers', 'recruiters', 'admins', 'total', 'verified']);
    $total = sum($rows, 'total');
    json_out([
        'title' => 'User Registration Report',
        'description' => 'New accounts per month, split by role, with how many confirmed their email.',
        'columns' => [
            ['key' => 'month', 'label' => 'Month'],
            ['key' => 'jobseekers', 'label' => 'Jobseekers'],
            ['key' => 'recruiters', 'label' => 'Recruiters'],
            ['key' => 'admins', 'label' => 'Admins'],
            ['key' => 'total', 'label' => 'Total'],
            ['key' => 'verified', 'label' => 'Email Verified'],
        ],
        'rows' => $rows,
        'summary' => [
            ['label' => 'Total registrations', 'value' => $total],
            ['label' => 'Jobseekers', 'value' => sum($rows, 'jobseekers')],
            ['label' => 'Recruiters', 'value' => sum($rows, 'recruiters')],
            ['label' => 'Email verified', 'value' => pct(sum($rows, 'verified'), $total)],
        ],
    ]);
}

if ($type === 'jobs') {
    [$f, $p] = date_filter('j.created_at', $from, $to);
    $rows = q("SELECT c.group_name AS category_group, c.name AS category,
                      COUNT(*) AS total,
                      SUM(j.status = 'open') AS open_jobs,
                      SUM(j.status = 'closed') AS closed_jobs,
                      SUM(j.status = 'filled') AS filled_jobs,
                      SUM(j.below_nmw_flag = 1) AS below_nmw
               FROM jobs j JOIN job_categories c ON c.id = j.category_id
               WHERE j.status <> 'draft' $f
               GROUP BY c.id, c.group_name, c.name
               ORDER BY total DESC, c.name", $p)->fetchAll();
    $rows = ints($rows, ['total', 'open_jobs', 'closed_jobs', 'filled_jobs', 'below_nmw']);
    $total = sum($rows, 'total');
    json_out([
        'title' => 'Job Postings Report',
        'description' => 'Posted jobs per category, how many are still open, closed or filled, and how many pay below the national minimum wage.',
        'columns' => [
            ['key' => 'category_group', 'label' => 'Group'],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'total', 'label' => 'Jobs Posted'],
            ['key' => 'open_jobs', 'label' => 'Open'],
            ['key' => 'closed_jobs', 'label' => 'Closed'],
            ['key' => 'filled_jobs', 'label' => 'Filled'],
            ['key' => 'below_nmw', 'label' => 'Below Min. Wage'],
        ],
        'rows' => $rows,
        'summary' => [
            ['label' => 'Jobs posted', 'value' => $total],
            ['label' => 'Currently open', 'value' => sum($rows, 'open_jobs')],
            ['label' => 'Filled', 'value' => pct(sum($rows, 'filled_jobs'), $total)],
            ['label' => 'Flagged below min. wage', 'value' => sum($rows, 'below_nmw')],
        ],
    ]);
}

if ($type === 'applications') {
    [$f, $p] = date_filter('j.created_at', $from, $to);
    $rows = q("SELECT j.id, j.title, r.company_name AS company, j.status AS job_status,
                      COUNT(a.id) AS applications,
                      COALESCE(SUM(a.status = 'shortlisted'), 0) AS shortlisted,
                      COALESCE(SUM(a.status = 'hired'), 0) AS hired,
                      COALESCE(SUM(a.status = 'rejected'), 0) AS rejected
               FROM jobs j
               JOIN recruiter_profiles r ON r.user_id = j.recruiter_id
               LEFT JOIN job_applications a ON a.job_id = j.id
               WHERE j.status <> 'draft' $f
               GROUP BY j.id, j.title, r.company_name, j.status
               ORDER BY applications DESC, j.id DESC
               LIMIT 200", $p)->fetchAll();
    $rows = ints($rows, ['id', 'applications', 'shortlisted', 'hired', 'rejected']);
    $apps = sum($rows, 'applications');
    json_out([
        'title' => 'Applications Report',
        'description' => 'Applications received per job and what happened to them (top 200 jobs by applications).',
        'columns' => [
            ['key' => 'title', 'label' => 'Job'],
            ['key' => 'company', 'label' => 'Company'],
            ['key' => 'job_status', 'label' => 'Job Status'],
            ['key' => 'applications', 'label' => 'Applications'],
            ['key' => 'shortlisted', 'label' => 'Shortlisted'],
            ['key' => 'hired', 'label' => 'Hired'],
            ['key' => 'rejected', 'label' => 'Rejected'],
        ],
        'rows' => $rows,
        'summary' => [
            ['label' => 'Applications', 'value' => $apps],
            ['label' => 'Average per job', 'value' => count($rows) ? round($apps / count($rows), 1) : 0],
            ['label' => 'Hired', 'value' => sum($rows, 'hired')],
            ['label' => 'Hire rate', 'value' => pct(sum($rows, 'hired'), $apps)],
        ],
    ]);
}

if ($type === 'provinces') {
    // A snapshot of today, so the date filter does not apply.
    $rows = q("SELECT p.name AS province,
                 (SELECT COUNT(*) FROM worker_profiles w JOIN users u ON u.id = w.user_id AND u.deleted_at IS NULL
                   WHERE w.province_id = p.id) AS workers,
                 (SELECT COUNT(*) FROM recruiter_profiles r JOIN users u ON u.id = r.user_id AND u.deleted_at IS NULL
                   WHERE r.province_id = p.id) AS recruiters,
                 (SELECT COUNT(*) FROM jobs j WHERE j.province_id = p.id AND j.status <> 'draft') AS jobs_posted,
                 (SELECT COUNT(*) FROM jobs j WHERE j.province_id = p.id AND j.status = 'open') AS open_jobs,
                 (SELECT COUNT(*) FROM work_adverts a WHERE a.province_id = p.id AND a.status = 'active') AS active_adverts
               FROM provinces p ORDER BY p.name")->fetchAll();
    $rows = ints($rows, ['workers', 'recruiters', 'jobs_posted', 'open_jobs', 'active_adverts']);
    json_out([
        'title' => 'Province Report',
        'description' => 'Where workers, recruiters, jobs and adverts are, as of today.',
        'columns' => [
            ['key' => 'province', 'label' => 'Province'],
            ['key' => 'workers', 'label' => 'Workers'],
            ['key' => 'recruiters', 'label' => 'Recruiters'],
            ['key' => 'jobs_posted', 'label' => 'Jobs Posted'],
            ['key' => 'open_jobs', 'label' => 'Open Jobs'],
            ['key' => 'active_adverts', 'label' => 'Active Adverts'],
        ],
        'rows' => $rows,
        'summary' => [
            ['label' => 'Workers', 'value' => sum($rows, 'workers')],
            ['label' => 'Recruiters', 'value' => sum($rows, 'recruiters')],
            ['label' => 'Open jobs', 'value' => sum($rows, 'open_jobs')],
            ['label' => 'Active adverts', 'value' => sum($rows, 'active_adverts')],
        ],
    ]);
}

fail('Unknown report type');
