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

function setting_move_item(string $key, int $index, int $direction): void
{
    $list = setting_list($key);
    $target = $index + $direction;
    if ($index < 0 || $index >= count($list) || $target < 0 || $target >= count($list)) {
        return;
    }
    $swap = $list[$index];
    $list[$index] = $list[$target];
    $list[$target] = $swap;
    save_setting($key, array_values($list));
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

function default_hero_slides(): array
{
    return [
        [
            'eyebrow' => 'Certified Safety Solutions · Berhampur',
            'title' => 'Protection engineered for every worksite, every day.',
            'highlight' => 'every worksite,',
            'subtitle' => 'We supply, install, and maintain fire, road, industrial, and household safety systems for contractors, industries, and government agencies across India.',
            'image' => 'assets/img/hero.jpg',
            'navLabel' => 'Protect',
            'buttonLabel' => 'Request a Site Audit',
            'buttonUrl' => 'contact',
            'button2Label' => 'Browse Product Range',
            'button2Url' => 'products',
        ],
        [
            'eyebrow' => 'Fire Safety',
            'title' => 'Contain fire before it reaches people.',
            'highlight' => 'before',
            'subtitle' => 'Extinguishers, hydrants, and detection specified for Indian sites — installed and maintained by our own crews.',
            'image' => 'assets/img/products.jpg',
            'navLabel' => 'Fire',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'products/fire-safety',
            'button2Label' => '',
            'button2Url' => '',
        ],
        [
            'eyebrow' => 'Road & Traffic Safety',
            'title' => 'Keep every lane and work zone under control.',
            'highlight' => 'work zone',
            'subtitle' => 'Barricades, cones, signage, and crash protection built for highways, yards, and temporary site approaches.',
            'image' => 'assets/img/about.jpg',
            'navLabel' => 'Road',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'products/road-traffic-safety',
            'button2Label' => '',
            'button2Url' => '',
        ],
        [
            'eyebrow' => 'End-to-end Support',
            'title' => 'Audit, install, and stay accountable.',
            'highlight' => 'stay accountable.',
            'subtitle' => 'Consultancy, installation, and 24/7 maintenance — one team past the invoice, not a chain of vendors.',
            'image' => 'assets/img/blog-1.jpg',
            'navLabel' => 'Service',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'faq',
            'button2Label' => '',
            'button2Url' => '',
        ],
    ];
}

function hero_slides(): array
{
    $stored = setting_list('hero');
    if ($stored) {
        $out = [];
        foreach ($stored as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $title = trim((string) ($slide['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = $slide;
        }
        if ($out) {
            return $out;
        }
    }
    return default_hero_slides();
}

function hero_title_html(array $slide): string
{
    $title = trim((string) ($slide['title'] ?? ''));
    $highlight = trim((string) ($slide['highlight'] ?? ''));
    if ($highlight === '') {
        return e($title);
    }
    $pos = stripos($title, $highlight);
    if ($pos === false) {
        return e($title) . ' <em>' . e($highlight) . '</em>';
    }
    $len = strlen($highlight);
    return e(substr($title, 0, $pos)) . '<em>' . e(substr($title, $pos, $len)) . '</em>' . e(substr($title, $pos + $len));
}

function hero_link(string $path, string $fallback = 'contact'): string
{
    $path = trim($path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    return url_for(ltrim($path, '/'));
}

function hero_image_url(array $slide): string
{
    $image = trim((string) ($slide['image'] ?? ''));
    if ($image === '') {
        $image = 'assets/img/hero.jpg';
    }
    return url_for($image);
}

function default_faqs(): array
{
    $contact = is_array(setting('contact', [])) ? setting('contact', []) : [];
    $phone = (string) ($contact['phones'][0]['number'] ?? '');
    $quote = $phone !== ''
        ? 'Call ' . $phone . ', email us, or send the form on the contact page. We typically respond within one business day.'
        : 'Email us or send the form on the contact page. We typically respond within one business day.';

    return [
        ['q' => 'Which areas do you supply?', 'a' => 'We supply and install across India from our base in Berhampur, Odisha — including industrial sites, campuses, highways, and government projects.'],
        ['q' => 'Do you only sell products, or do you install as well?', 'a' => 'Both. We supply certified equipment and handle installation, commissioning, and maintenance with our own crews.'],
        ['q' => 'Can I place a bulk or government order?', 'a' => 'Yes. We handle wholesale and project orders with staged delivery. Share your quantity and timeline on the contact page for a scoped quote.'],
        ['q' => 'Are your products certified?', 'a' => 'Product lines are checked against applicable IS, ISO, IRC, and OEM standards before they enter the catalog.'],
        ['q' => 'How do I request a quote?', 'a' => $quote],
        ['q' => 'Do you offer after-sales and emergency support?', 'a' => 'Yes. Maintenance contracts, refills, and 24/7 breakdown response are available for installed systems.'],
    ];
}

function site_faqs(): array
{
    $stored = setting('faqs', null);
    if (!is_array($stored) || !$stored) {
        return default_faqs();
    }
    $out = [];
    foreach ($stored as $item) {
        $q = trim((string) ($item['q'] ?? ''));
        $a = trim((string) ($item['a'] ?? ''));
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => $q, 'a' => $a];
        }
    }
    return $out ?: default_faqs();
}

function faqs_to_text(array $faqs): string
{
    $blocks = [];
    foreach ($faqs as $faq) {
        $blocks[] = $faq['q'] . "\n" . $faq['a'];
    }
    return implode("\n\n", $blocks);
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
        'image' => (string) ($row['image'] ?? ''),
        'useCases' => json_list($row['use_cases']),
        'faqs' => json_list($row['faqs']),
        'metaTitle' => (string) ($row['meta_title'] ?? ''),
        'metaDescription' => (string) ($row['meta_description'] ?? ''),
        'metaKeywords' => (string) ($row['meta_keywords'] ?? ''),
        'canonical' => (string) ($row['canonical'] ?? ''),
        'ogImage' => (string) ($row['og_image'] ?? ''),
        'noindex' => (int) ($row['noindex'] ?? 0) === 1,
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
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
        'metaTitle' => (string) ($row['meta_title'] ?? ''),
        'metaDescription' => (string) ($row['meta_description'] ?? ''),
        'metaKeywords' => (string) ($row['meta_keywords'] ?? ''),
        'canonical' => (string) ($row['canonical'] ?? ''),
        'ogImage' => (string) ($row['og_image'] ?? ''),
        'noindex' => (int) ($row['noindex'] ?? 0) === 1,
        'brand' => (string) ($row['brand'] ?? ''),
        'sku' => (string) ($row['sku'] ?? ''),
        'gtin' => (string) ($row['gtin'] ?? ''),
        'mpn' => (string) ($row['mpn'] ?? ''),
        'condition' => (string) ($row['item_condition'] ?? ''),
        'availability' => (string) ($row['availability'] ?? ''),
        'price' => (string) ($row['price'] ?? ''),
        'currency' => (string) ($row['currency'] ?? ''),
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
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
    if (!db_ready()) {
        return [];
    }
    migrate();
    $rows = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
    return array_map('map_category', $rows);
}

function get_category(string $id): ?array
{
    if (!db_ready()) {
        return null;
    }
    migrate();
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_category($row) : null;
}

function all_products_admin(?string $category = null, ?string $search = null): array
{
    $where = [];
    $args = [];

    if ($category !== null && $category !== '') {
        $where[] = 'category = ?';
        $args[] = $category;
    }
    if ($search !== null && trim($search) !== '') {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
        $where[] = '(name LIKE ? OR slug LIKE ? OR short LIKE ? OR tags LIKE ?)';
        array_push($args, $like, $like, $like, $like);
    }

    $sql = 'SELECT * FROM products';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function move_product(int $id, int $direction): bool
{
    $rows = db()->query('SELECT id FROM products ORDER BY sort_order ASC, name ASC')->fetchAll();
    $ids = array_map('intval', array_column($rows, 'id'));
    $index = array_search($id, $ids, true);
    if ($index === false) {
        return false;
    }
    $target = $index + $direction;
    if ($target < 0 || $target >= count($ids)) {
        return false;
    }

    $moved = array_splice($ids, $index, 1);
    array_splice($ids, $target, 0, $moved);

    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
    foreach ($ids as $position => $rowId) {
        $stmt->execute([$position, $rowId]);
    }
    $pdo->commit();
    return true;
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
        'faqs' => site_faqs(),
        'hero' => hero_slides(),
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
        'hero' => count(setting_list('hero')),
        'enquiries' => enquiry_count(),
        'enquiriesUnread' => enquiry_unread_count(),
    ];
}

function enquiry_count(): int
{
    if (!db_ready()) {
        return 0;
    }
    migrate();
    try {
        return (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function enquiry_unread_count(): int
{
    if (!db_ready()) {
        return 0;
    }
    migrate();
    try {
        return (int) db()->query('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function recent_enquiries(int $limit = 8): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    try {
        $limit = max(1, min(50, $limit));
        return db()->query('SELECT * FROM enquiries ORDER BY created_at DESC, id DESC LIMIT ' . $limit)->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function all_enquiries(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    try {
        return db()->query('SELECT * FROM enquiries ORDER BY created_at DESC, id DESC')->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function get_enquiry(int $id): ?array
{
    if ($id < 1 || !db_ready()) {
        return null;
    }
    migrate();
    $stmt = db()->prepare('SELECT * FROM enquiries WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mark_enquiry_read(int $id): void
{
    db()->prepare('UPDATE enquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
}

function delete_enquiry(int $id): void
{
    db()->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);
}

function enquiry_ip_limited(string $ip): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM enquiries WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn() >= 8;
}

function save_enquiry(array $data): int
{
    migrate();
    $stmt = db()->prepare(
        'INSERT INTO enquiries (name, company, email, phone, category, message, ip, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())'
    );
    $stmt->execute([
        $data['name'],
        $data['company'],
        $data['email'],
        $data['phone'],
        $data['category'],
        $data['message'],
        $data['ip'],
    ]);
    return (int) db()->lastInsertId();
}
