<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$id = isset($_GET['id']) ? (string) $_GET['id'] : '';
$existing = $id !== '' ? get_category($id) : null;
$errors = [];

function encode_pairs(string $text, array $keys): array
{
    $blocks = preg_split("/\n\s*\n/", trim($text)) ?: [];
    $out = [];
    foreach ($blocks as $block) {
        $lines = lines_to_array($block);
        if (count($lines) < 2) {
            continue;
        }
        $item = [];
        $item[$keys[0]] = array_shift($lines);
        $item[$keys[1]] = implode(' ', $lines);
        $out[] = $item;
    }
    return $out;
}

function decode_pairs(array $items, array $keys): string
{
    $blocks = [];
    foreach ($items as $item) {
        $blocks[] = ($item[$keys[0]] ?? '') . "\n" . ($item[$keys[1]] ?? '');
    }
    return implode("\n\n", $blocks);
}

$category = [
    'id' => $existing['id'] ?? '',
    'icon' => $existing['icon'] ?? '',
    'name' => $existing['name'] ?? '',
    'desc' => $existing['desc'] ?? '',
    'headline' => $existing['headline'] ?? '',
    'intro' => $existing['intro'] ?? '',
    'buyers' => $existing['buyers'] ?? '',
    'useCases' => decode_pairs($existing['useCases'] ?? [], ['title', 'text']),
    'faqs' => decode_pairs($existing['faqs'] ?? [], ['q', 'a']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $category['name'] = post('name');
    $category['id'] = post('id') !== '' ? slugify(post('id')) : slugify(post('name'));
    $category['icon'] = post('icon');
    $category['desc'] = post('desc');
    $category['headline'] = post('headline');
    $category['intro'] = post('intro');
    $category['buyers'] = post('buyers');
    $category['useCases'] = post('useCases');
    $category['faqs'] = post('faqs');

    if ($category['name'] === '' || $category['id'] === '') {
        $errors[] = 'Name and ID are required.';
    }

    if (!$errors) {
        $useCases = json_encode(encode_pairs($category['useCases'], ['title', 'text']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $faqs = json_encode(encode_pairs($category['faqs'], ['q', 'a']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            if ($existing) {
                if ($category['id'] !== $existing['id']) {
                    db()->prepare('UPDATE products SET category = ? WHERE category = ?')->execute([$category['id'], $existing['id']]);
                    $stmt = db()->prepare(
                        'UPDATE categories SET id=?, icon=?, name=?, `desc`=?, headline=?, intro=?, buyers=?, use_cases=?, faqs=? WHERE id=?'
                    );
                    $stmt->execute([
                        $category['id'], $category['icon'], $category['name'], $category['desc'], $category['headline'],
                        $category['intro'], $category['buyers'], $useCases, $faqs, $existing['id']
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE categories SET icon=?, name=?, `desc`=?, headline=?, intro=?, buyers=?, use_cases=?, faqs=? WHERE id=?'
                    );
                    $stmt->execute([
                        $category['icon'], $category['name'], $category['desc'], $category['headline'],
                        $category['intro'], $category['buyers'], $useCases, $faqs, $existing['id']
                    ]);
                }
                flash('ok', 'Category updated.');
            } else {
                $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM categories')->fetchColumn();
                $stmt = db()->prepare(
                    'INSERT INTO categories (id, icon, name, `desc`, headline, intro, buyers, use_cases, faqs, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $category['id'], $category['icon'], $category['name'], $category['desc'], $category['headline'],
                    $category['intro'], $category['buyers'], $useCases, $faqs, $sort
                ]);
                flash('ok', 'Category created.');
            }
            export_site_config_js();
            redirect('categories.php');
        } catch (PDOException $e) {
            $errors[] = (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate'))
                ? 'Category ID already exists.'
                : 'Could not save category.';
        }
    }
}

admin_header($existing ? 'Edit Category' : 'Add Category', 'categories');
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Name
      <input type="text" name="name" required value="<?= e($category['name']) ?>">
    </label>
    <label>ID
      <input type="text" name="id" value="<?= e($category['id']) ?>">
    </label>
    <label>Icon key
      <input type="text" name="icon" value="<?= e($category['icon']) ?>">
    </label>
    <label class="full">Short description
      <textarea name="desc" rows="2"><?= e($category['desc']) ?></textarea>
    </label>
    <label class="full">Headline
      <input type="text" name="headline" value="<?= e($category['headline']) ?>">
    </label>
    <label class="full">Intro
      <textarea name="intro" rows="4"><?= e($category['intro']) ?></textarea>
    </label>
    <label class="full">Buyers copy
      <textarea name="buyers" rows="4"><?= e($category['buyers']) ?></textarea>
    </label>
    <label class="full">Use cases <span class="hint">Blank line between items. Line 1 = title, following lines = text</span>
      <textarea name="useCases" rows="8"><?= e($category['useCases']) ?></textarea>
    </label>
    <label class="full">FAQs <span class="hint">Blank line between items. Line 1 = question, following lines = answer</span>
      <textarea name="faqs" rows="8"><?= e($category['faqs']) ?></textarea>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Category</button>
    <a class="btn btn-secondary" href="categories.php">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
