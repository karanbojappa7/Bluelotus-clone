<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        CMS_DB_HOST,
        CMS_DB_PORT,
        CMS_DB_NAME
    );

    $pdo = new PDO($dsn, CMS_DB_USER, CMS_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}

function db_ready(): bool
{
    try {
        return (bool) db()->query("SHOW TABLES LIKE 'users'")->fetch();
    } catch (Throwable $e) {
        return false;
    }
}

function migrate(): void
{
    $pdo = db();
    $statements = [
        'CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at VARCHAR(40) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(64) NOT NULL PRIMARY KEY,
            value LONGTEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS categories (
            id VARCHAR(64) NOT NULL PRIMARY KEY,
            icon VARCHAR(64) NOT NULL DEFAULT "",
            name VARCHAR(191) NOT NULL,
            `desc` TEXT NOT NULL,
            headline VARCHAR(255) NOT NULL DEFAULT "",
            intro TEXT NOT NULL,
            buyers TEXT NOT NULL,
            use_cases TEXT NOT NULL,
            faqs TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS products (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(191) NOT NULL UNIQUE,
            category VARCHAR(64) NOT NULL,
            name VARCHAR(191) NOT NULL,
            short TEXT NOT NULL,
            description TEXT NOT NULL,
            tags TEXT NOT NULL,
            features TEXT NOT NULL,
            images TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            CONSTRAINT fk_products_category FOREIGN KEY (category) REFERENCES categories(id) ON UPDATE CASCADE ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS services (
            id VARCHAR(64) NOT NULL PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            icon VARCHAR(64) NOT NULL DEFAULT "",
            summary TEXT NOT NULL,
            points TEXT NOT NULL,
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
        'CREATE TABLE IF NOT EXISTS leadership (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            designation VARCHAR(191) NOT NULL DEFAULT "",
            experience VARCHAR(191) NOT NULL DEFAULT "",
            expertise VARCHAR(255) NOT NULL DEFAULT "",
            background TEXT NOT NULL,
            linkedin VARCHAR(255) NOT NULL DEFAULT "",
            image VARCHAR(255) NOT NULL DEFAULT "",
            sort_order INT NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
    ];

    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }

    foreach (['products', 'categories'] as $table) {
        ensure_column($table, 'meta_title', 'VARCHAR(191) NOT NULL DEFAULT ""');
        ensure_column($table, 'meta_description', 'VARCHAR(320) NOT NULL DEFAULT ""');
        ensure_column($table, 'meta_keywords', 'VARCHAR(320) NOT NULL DEFAULT ""');
        ensure_column($table, 'canonical', 'VARCHAR(255) NOT NULL DEFAULT ""');
        ensure_column($table, 'og_image', 'VARCHAR(255) NOT NULL DEFAULT ""');
        ensure_column($table, 'noindex', 'TINYINT(1) NOT NULL DEFAULT 0');
        ensure_column($table, 'updated_at', 'VARCHAR(40) NOT NULL DEFAULT ""');
    }

    ensure_column('products', 'brand', 'VARCHAR(120) NOT NULL DEFAULT ""');
    ensure_column('products', 'sku', 'VARCHAR(120) NOT NULL DEFAULT ""');
    ensure_column('products', 'gtin', 'VARCHAR(60) NOT NULL DEFAULT ""');
    ensure_column('products', 'mpn', 'VARCHAR(60) NOT NULL DEFAULT ""');
    ensure_column('products', 'item_condition', 'VARCHAR(40) NOT NULL DEFAULT ""');
    ensure_column('products', 'availability', 'VARCHAR(40) NOT NULL DEFAULT ""');
    ensure_column('products', 'price', 'VARCHAR(40) NOT NULL DEFAULT ""');
    ensure_column('products', 'currency', 'VARCHAR(10) NOT NULL DEFAULT ""');

    migrate_leadership_from_settings();
}

function ensure_column(string $table, string $column, string $definition): void
{
    static $cache = [];
    $key = $table . '.' . $column;
    if (isset($cache[$key])) {
        return;
    }
    $cache[$key] = true;
    try {
        $stmt = db()->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE ?');
        $stmt->execute([$column]);
        if ($stmt->fetch()) {
            return;
        }
        db()->exec('ALTER TABLE `' . $table . '` ADD COLUMN `' . $column . '` ' . $definition);
    } catch (Throwable $e) {
    }
}

function migrate_leadership_from_settings(): void
{
    $pdo = db();
    $count = (int) $pdo->query('SELECT COUNT(*) FROM leadership')->fetchColumn();
    if ($count > 0) {
        return;
    }
    $legacy = setting('leadership', []);
    if (!is_array($legacy) || !$legacy) {
        return;
    }
    $stmt = $pdo->prepare(
        'INSERT INTO leadership (name, designation, experience, expertise, background, linkedin, image, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach (array_values($legacy) as $i => $row) {
        if (!is_array($row)) {
            continue;
        }
        $stmt->execute([
            $row['name'] ?? '',
            $row['designation'] ?? ($row['role'] ?? ''),
            $row['experience'] ?? '',
            $row['expertise'] ?? '',
            $row['background'] ?? ($row['bio'] ?? ''),
            $row['linkedin'] ?? '',
            $row['image'] ?? '',
            $i,
        ]);
    }
    db()->prepare('DELETE FROM settings WHERE `key` = ?')->execute(['leadership']);
}

function seed_from_json(): void
{
    if (!is_file(CMS_SEED)) {
        throw new RuntimeException('Missing seed file: admin/data/seed.json');
    }

    $raw = file_get_contents(CMS_SEED);
    $seed = json_decode((string) $raw, true);
    if (!is_array($seed)) {
        throw new RuntimeException('Invalid seed.json');
    }

    migrate();
    $pdo = db();
    $pdo->beginTransaction();

    $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($userCount === 0) {
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)');
        $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), gmdate('c')]);
    }

    $settingKeys = ['company', 'contact', 'social', 'seo', 'stats', 'testimonials', 'blog', 'clients'];
    $setStmt = $pdo->prepare(
        'INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?'
    );
    foreach ($settingKeys as $key) {
        if (isset($seed[$key])) {
            $json = json_encode($seed[$key], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $setStmt->execute([$key, $json, $json]);
        }
    }

    $pdo->exec('DELETE FROM products');
    $pdo->exec('DELETE FROM services');
    $pdo->exec('DELETE FROM categories');
    $pdo->exec('DELETE FROM leadership');

    $catStmt = $pdo->prepare(
        'INSERT INTO categories (id, icon, name, `desc`, headline, intro, buyers, use_cases, faqs, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach (($seed['categories'] ?? []) as $i => $cat) {
        $catStmt->execute([
            $cat['id'],
            $cat['icon'] ?? '',
            $cat['name'] ?? '',
            $cat['desc'] ?? '',
            $cat['headline'] ?? '',
            $cat['intro'] ?? '',
            $cat['buyers'] ?? '',
            json_encode($cat['useCases'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($cat['faqs'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $i,
        ]);
    }

    $prodStmt = $pdo->prepare(
        'INSERT INTO products (slug, category, name, short, description, tags, features, images, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach (($seed['products'] ?? []) as $i => $product) {
        $prodStmt->execute([
            $product['slug'],
            $product['category'],
            $product['name'] ?? '',
            $product['short'] ?? '',
            $product['description'] ?? '',
            json_encode($product['tags'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($product['features'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            json_encode($product['images'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $i,
        ]);
    }

    $svcStmt = $pdo->prepare(
        'INSERT INTO services (id, name, icon, summary, points, sort_order) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach (($seed['services'] ?? []) as $i => $service) {
        $svcStmt->execute([
            $service['id'],
            $service['name'] ?? '',
            $service['icon'] ?? '',
            $service['summary'] ?? '',
            json_encode($service['points'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $i,
        ]);
    }

    $leadStmt = $pdo->prepare(
        'INSERT INTO leadership (name, designation, experience, expertise, background, linkedin, image, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach (($seed['leadership'] ?? []) as $i => $person) {
        $leadStmt->execute([
            $person['name'] ?? '',
            $person['designation'] ?? ($person['role'] ?? ''),
            $person['experience'] ?? '',
            $person['expertise'] ?? '',
            $person['background'] ?? ($person['bio'] ?? ''),
            $person['linkedin'] ?? '',
            $person['image'] ?? '',
            $i,
        ]);
    }

    $pdo->commit();
}
