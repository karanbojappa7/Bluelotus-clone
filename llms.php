<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$blurbs = [
    'road-traffic-safety' => 'Traffic control, visibility and pedestrian protection equipment.',
    'fire-safety' => 'Fire protection equipment, detection systems and emergency safety solutions.',
    'industrial-safety' => 'PPE and safety equipment for workers and workplaces.',
    'safety-wearable' => 'Industrial and wearable PPE, including hi-vis apparel and monitored kit.',
    'warehouse-safety' => 'Safety products for warehouses and logistics.',
    'construction-safety' => 'Safety equipment for construction sites.',
    'logistic-safety' => 'Load securing, yard safety and fleet protection.',
    'household-safety' => 'Smoke detectors, first-aid and residential safety products.',
    'water-safety' => 'Life buoys, pool alarms and flood protection.',
    'tree-safety' => 'Arborist rigging, canopy protection and site tree-guarding gear.',
];

$lines = [];
$lines[] = '# Bluelotus Infrasafety';
$lines[] = '> Bluelotus Infrasafety is an Indian supplier of industrial safety equipment, PPE and workplace safety solutions for road, fire, warehouse, construction and industrial needs. The company offers end-to-end support: workplace safety assessment, product recommendation, supply, installation and ongoing maintenance, with pan-India delivery.';
$lines[] = '';
$lines[] = 'Key facts:';
$lines[] = '- Serves businesses across India (B2B), with bulk quotes available.';
$lines[] = '- Product areas: road and traffic safety, fire safety, industrial safety and PPE, industrial and medical PPE kits, warehouse safety, construction safety, fall protection, water safety.';
$lines[] = '- Services: safety audit, installation, maintenance, OEM-backed after-sales support.';
$lines[] = '';
$lines[] = '## Main Pages';
$pages = [
    ['Home', '', 'Overview of safety solutions, services and why to choose Bluelotus Infrasafety.'],
    ['About', 'about', 'Company story, approach, and leadership.'],
    ['Products', 'products', 'Full catalog of industrial safety equipment and PPE.'],
    ['FAQ', 'faq', 'Answers to common questions about industrial safety equipment.'],
    ['Blog', 'blog', 'Industrial safety resources, guides and insights.'],
    ['Careers', 'careers', 'Open roles and how to apply.'],
    ['Contact / Bulk Quote', 'contact', 'Request a bulk quote or contact the sales team.'],
];
foreach ($pages as [$label, $path, $blurb]) {
    $lines[] = '- [' . $label . '](' . seo_url($path) . '): ' . $blurb;
}
$lines[] = '';
$lines[] = '## Product Categories';
foreach (all_categories() as $cat) {
    $id = (string) ($cat['id'] ?? '');
    $name = trim((string) ($cat['name'] ?? ''));
    if ($id === '' || $name === '') {
        continue;
    }
    $blurb = $blurbs[$id] ?? 'Certified safety products in this range.';
    $lines[] = '- [' . $name . '](' . seo_url('products/' . rawurlencode($id)) . '): ' . $blurb;
}
$lines[] = '';
$lines[] = '## Blog articles';
$count = 0;
foreach (setting_list('blog') as $post) {
    $slug = trim((string) ($post['slug'] ?? ''));
    $title = trim((string) ($post['title'] ?? ''));
    if ($slug === '' || $title === '') {
        continue;
    }
    $lines[] = '- [' . $title . '](' . seo_url('blog/' . rawurlencode($slug)) . ')';
    $count++;
    if ($count >= 6) {
        break;
    }
}

header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=3600');
echo implode("\n", $lines) . "\n";
