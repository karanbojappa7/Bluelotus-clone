<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$map = (string) ($_GET['map'] ?? 'index');
serve_sitemap($map);
