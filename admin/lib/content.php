<?php
declare(strict_types=1);

function setting(string $key, $default = null)
{
    if (!db_ready()) {
        return $default;
    }
    $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $default;
    }
    $decoded = json_decode($row['value'], true);
    return $decoded === null ? $default : $decoded;
}

function save_setting(string $key, $value): void
{
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = db()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
    $stmt->execute([$key, $json, $json]);
}

function setting_list(string $key): array
{
    $value = setting($key, []);
    return is_array($value) ? array_values($value) : [];
}

function setting_get_item(string $key, int $index): ?array
{
    $list = setting_list($key);
    if (!isset($list[$index]) || !is_array($list[$index])) {
        return null;
    }
    return $list[$index];
}

function setting_save_item(string $key, ?int $index, array $item): int
{
    $list = setting_list($key);
    if ($index === null || $index < 0 || $index >= count($list)) {
        $list[] = $item;
        $index = count($list) - 1;
    } else {
        $list[$index] = $item;
    }
    save_setting($key, $list);
    return $index;
}

function setting_delete_item(string $key, int $index): void
{
    $list = setting_list($key);
    if (!isset($list[$index])) {
        return;
    }
    array_splice($list, $index, 1);
    save_setting($key, array_values($list));
}

function parse_labeled_lines(string $text): array
{
    $out = [];
    foreach (lines_to_array($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $out[] = $parts;
        }
    }
    return $out;
}

function map_category(array $row): array
{
    return [
        'id' => $row['id'],
        'icon' => $row['icon'],
        'name' => $row['name'],
        'desc' => $row['desc'],
        'headline' => $row['headline'],
        'intro' => $row['intro'],
        'buyers' => $row['buyers'],
        'useCases' => json_list($row['use_cases']),
        'faqs' => json_list($row['faqs']),
    ];
}

function map_product(array $row): array
{
    return [
        'slug' => $row['slug'],
        'category' => $row['category'],
        'name' => $row['name'],
        'short' => $row['short'],
        'description' => $row['description'],
        'tags' => json_list($row['tags']),
        'features' => json_list($row['features']),
        'images' => json_list($row['images']),
    ];
}

function map_service(array $row): array
{
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'icon' => $row['icon'],
        'summary' => $row['summary'],
        'points' => json_list($row['points']),
    ];
}

function all_categories(): array
{
    $rows = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
    return array_map('map_category', $rows);
}

function get_category(string $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_category($row) : null;
}

function all_products_admin(?string $category = null): array
{
    if ($category) {
        $stmt = db()->prepare('SELECT * FROM products WHERE category = ? ORDER BY sort_order ASC, name ASC');
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }
    return db()->query('SELECT * FROM products ORDER BY sort_order ASC, name ASC')->fetchAll();
}

function all_products(?string $category = null): array
{
    return array_map('map_product', all_products_admin($category));
}

function get_product_by_slug(string $slug): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? map_product($row) : null;
}

function get_product_row(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function all_services(): array
{
    $rows = db()->query('SELECT * FROM services ORDER BY sort_order ASC, name ASC')->fetchAll();
    return array_map('map_service', $rows);
}

function map_leader(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => $row['name'] ?? '',
        'designation' => $row['designation'] ?? ($row['role'] ?? ''),
        'experience' => $row['experience'] ?? '',
        'expertise' => $row['expertise'] ?? '',
        'background' => $row['background'] ?? ($row['bio'] ?? ''),
        'linkedin' => $row['linkedin'] ?? '',
        'image' => $row['image'] ?? '',
    ];
}

function all_leadership(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    $rows = db()->query('SELECT * FROM leadership ORDER BY sort_order ASC, id ASC')->fetchAll();
    return array_map('map_leader', $rows);
}

function get_leadership_row(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM leadership WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_leader($row) : null;
}

function leader_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'BL';
}

function render_leadership_cards(): string
{
    $html = '';
    foreach (all_leadership() as $person) {
        $photo = $person['image'] !== ''
            ? '<img src="' . e($person['image']) . '" alt="' . e($person['name']) . '" width="480" height="360" loading="lazy">'
            : '<span class="leader-fallback" aria-hidden="true">' . e(leader_initials($person['name'])) . '</span>';
        $linkedin = $person['linkedin'] !== ''
            ? '<a class="leader-in" href="' . e($person['linkedin']) . '" target="_blank" rel="noopener" aria-label="LinkedIn profile for ' . e($person['name']) . '"><svg width="18" height="18" aria-hidden="true"><use href="assets/img/sprite.svg#icon-linkedin"></use></svg></a>'
            : '';
        $experience = $person['experience'] !== ''
            ? '<div class="leader-meta"><span>Experience</span><p>' . e($person['experience']) . '</p></div>'
            : '';
        $expertise = $person['expertise'] !== ''
            ? '<div class="leader-meta"><span>Area of expertise</span><p>' . e($person['expertise']) . '</p></div>'
            : '';
        $html .= '<article class="leader-card" data-reveal>'
            . '<div class="leader-photo">' . $photo . '</div>'
            . '<div class="leader-body">'
            . '<h3>' . e($person['name']) . '</h3>'
            . '<p class="leader-role">' . e($person['designation']) . '</p>'
            . $experience
            . $expertise
            . '<p class="leader-background">' . e($person['background']) . '</p>'
            . $linkedin
            . '</div></article>';
    }
    return $html;
}

function get_service(string $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_service($row) : null;
}

function build_site_config(): array
{
    if (!db_ready()) {
        $seed = json_decode((string) file_get_contents(CMS_SEED), true);
        return is_array($seed) ? $seed : [];
    }

    return [
        'company' => setting('company', []),
        'contact' => setting('contact', []),
        'social' => setting('social', []),
        'seo' => setting('seo', []),
        'stats' => setting('stats', []),
        'leadership' => all_leadership(),
        'categories' => all_categories(),
        'products' => all_products(),
        'services' => all_services(),
        'testimonials' => setting('testimonials', []),
        'blog' => setting('blog', []),
        'clients' => setting('clients', []),
    ];
}

function export_site_config_js(): void
{
    $config = build_site_config();
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $js = "window.SITE_CONFIG = {$json};\n";
    $target = CMS_ROOT . '/config/site.config.js';
    file_put_contents($target, $js);
}

function counts(): array
{
    return [
        'products' => (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'services' => (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn(),
        'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'blog' => count(setting_list('blog')),
        'testimonials' => count(setting_list('testimonials')),
        'leadership' => count(all_leadership()),
        'clients' => count(setting_list('clients')),
        'stats' => count(setting_list('stats')),
    ];
}
