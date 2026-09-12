<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

header('Content-Type: application/xml; charset=UTF-8');
header('X-Robots-Tag: noindex');

$ready = db_ready();
$today = gmdate('Y-m-d');

function sitemap_entry(string $path, string $changefreq, string $priority, string $lastmod = '', array $images = []): string
{
    $xml = '  <url>' . "\n";
    $xml .= '    <loc>' . htmlspecialchars(seo_url(ltrim($path, '/')), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>' . "\n";
    if ($lastmod !== '') {
        $xml .= '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1, 'UTF-8') . '</lastmod>' . "\n";
    }
    $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
    $xml .= '    <priority>' . $priority . '</priority>' . "\n";
    foreach (array_slice(array_filter($images), 0, 20) as $image) {
        $xml .= '    <image:image><image:loc>'
            . htmlspecialchars(seo_image((string) $image), ENT_XML1 | ENT_QUOTES, 'UTF-8')
            . '</image:loc></image:image>' . "\n";
    }
    return $xml . '  </url>' . "\n";
}

function sitemap_date(string $value): string
{
    $value = substr(trim($value), 0, 10);
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
}

$out = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$out .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
    . 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

$out .= sitemap_entry('', 'weekly', '1.0', $today, ['assets/img/hero.jpg']);
$out .= sitemap_entry('about', 'monthly', '0.7');
$out .= sitemap_entry('products', 'weekly', '0.9', $today);
$out .= sitemap_entry('faq', 'monthly', '0.75');
$out .= sitemap_entry('blog', 'weekly', '0.7', $today);
$out .= sitemap_entry('contact', 'monthly', '0.6');
$out .= sitemap_entry('privacy-policy', 'yearly', '0.2');

if ($ready) {
    $collections = [
        [
            'rows' => all_categories(),
            'freq' => 'weekly',
            'priority' => '0.85',
            'path' => static fn ($r) => category_slug_path((string) $r['id']),
            'date' => static fn ($r) => (string) ($r['updatedAt'] ?? ''),
            'images' => static fn ($r) => [],
        ],
        [
            'rows' => all_products(),
            'freq' => 'monthly',
            'priority' => '0.8',
            'path' => static fn ($r) => product_slug_path((string) $r['slug']),
            'date' => static fn ($r) => (string) ($r['updatedAt'] ?? ''),
            'images' => static fn ($r) => (array) ($r['images'] ?? []),
        ],
        [
            'rows' => setting_list('blog'),
            'freq' => 'monthly',
            'priority' => '0.6',
            'path' => static fn ($r) => post_slug_path((string) $r['slug']),
            'date' => static fn ($r) => (string) ($r['date'] ?? ''),
            'images' => static fn ($r) => !empty($r['image']) ? [(string) $r['image']] : [],
        ],
    ];

    foreach ($collections as $group) {
        foreach ($group['rows'] as $row) {
            if (!empty($row['noindex']) || (empty($row['slug']) && empty($row['id']))) {
                continue;
            }
            $out .= sitemap_entry(
                ($group['path'])($row),
                $group['freq'],
                $group['priority'],
                sitemap_date(($group['date'])($row)),
                ($group['images'])($row)
            );
        }
    }
}

$out .= '</urlset>' . "\n";
echo $out;
