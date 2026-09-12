<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

header('Content-Type: text/plain; charset=UTF-8');

$host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
$isProduction = $host !== '' && !str_contains($host, 'localhost') && !str_starts_with($host, '127.');

if (!$isProduction) {
    echo "User-agent: *\n";
    echo "Disallow: /\n";
    exit;
}
?>
User-agent: *
Allow: /

Disallow: /admin/
Disallow: /config/
Disallow: /assets/js/
Disallow: /*?q=
Disallow: /*&page=

Sitemap: <?= seo_url('sitemap.xml') ?>
