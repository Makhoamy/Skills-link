<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
$user = require_auth();
$id = $user['id'];

$export = [
    'exported_at' => date('c'),
    'account'     => q('SELECT id, email, role, phone, is_verified, popia_consent_at, popia_policy_version,
                               marketing_consent, created_at FROM users WHERE id = ?', [$id])->fetch(),
    'consent_history' => q('SELECT consent_type, consent_given, policy_version, INET6_NTOA(ip_address) AS ip_address, created_at
                            FROM consent_log WHERE user_id = ? ORDER BY created_at', [$id])->fetchAll(),
    'data_requests'   => q('SELECT request_type, status, requested_at, resolved_at FROM data_subject_requests WHERE user_id = ?', [$id])->fetchAll(),
    'reviews_given'   => q('SELECT job_id, reviewee_user_id, rating, comment, created_at FROM reviews WHERE reviewer_user_id = ?', [$id])->fetchAll(),
    'reviews_received'=> q('SELECT job_id, rating, comment, created_at FROM reviews WHERE reviewee_user_id = ?', [$id])->fetchAll(),
];

if ($user['role'] === 'jobseeker') {
    $export['worker_profile'] = q('SELECT first_name, last_name, gender, date_of_birth, citizenship,
                                          (id_number_encrypted IS NOT NULL) AS id_number_on_file, drivers_license,
                                          province_id, city, suburb, bio, years_experience, availability,
                                          (cv_path IS NOT NULL) AS cv_on_file, (profile_photo_path IS NOT NULL) AS photo_on_file,
                                          avg_rating, rating_count, created_at
                                   FROM worker_profiles WHERE user_id = ?', [$id])->fetch() ?: null;
    $export['skills'] = q('SELECT c.name FROM worker_skills s JOIN job_categories c ON c.id = s.category_id WHERE s.user_id = ?', [$id])->fetchAll(PDO::FETCH_COLUMN);
    $export['equity_info'] = q('SELECT ethnicity, has_disability, disability_detail, consented_at FROM worker_equity_info WHERE user_id = ?', [$id])->fetch() ?: null;
    $export['applications'] = q('SELECT job_id, status, cover_note, applied_at FROM job_applications WHERE worker_id = ?', [$id])->fetchAll();
    $export['adverts'] = q('SELECT title, description, city, rate_amount, rate_period, contact_phone, status, created_at FROM work_adverts WHERE worker_id = ?', [$id])->fetchAll();
} elseif ($user['role'] === 'recruiter') {
    $export['recruiter_profile'] = q('SELECT company_name, registration_number, company_description, address, city, province_id,
                                             zip_code, website, contact_first_name, contact_last_name, contact_position,
                                             verification_status, created_at FROM recruiter_profiles WHERE user_id = ?', [$id])->fetch() ?: null;
    $export['jobs'] = q('SELECT id, title, description, city, pay_amount, pay_period, status, created_at FROM jobs WHERE recruiter_id = ?', [$id])->fetchAll();
}

header('Content-Disposition: attachment; filename="skills-link-my-data.json"');
json_out($export);
