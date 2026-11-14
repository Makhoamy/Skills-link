<?php
require __DIR__ . '/../lib/bootstrap.php';
require_method('GET');
json_out(['items' => q('SELECT id, name FROM provinces ORDER BY name')->fetchAll()]);
