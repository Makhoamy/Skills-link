<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
json_out(['items' => q('SELECT id, group_name, name FROM job_categories ORDER BY group_name, sort_order, name')->fetchAll()]);
