<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_once __DIR__ . '/../lib/seo/seo_fields.php';
require_login();
migrate();

$id = isset($_GET['id']) ? (string) $_GET['id'] : '';
$existing = $id !== '' ? get_category($id) : null;
$errors = [];

$category = [
    'id' => $existing['id'] ?? '',
    'icon' => $existing['icon'] ?? '',
    'name' => $existing['name'] ?? '',
    'desc' => $existing['desc'] ?? '',
    'headline' => $existing['headline'] ?? '',
    'intro' => $existing['intro'] ?? '',
    'buyers' => $existing['buyers'] ?? '',
    'image' => $existing['image'] ?? '',
    'useCases' => format_blocks($existing['useCases'] ?? [], 'title', 'text'),
    'faqs' => format_blocks($existing['faqs'] ?? [], 'q', 'a'),
    'metaTitle' => $existing['metaTitle'] ?? '',
    'metaDescription' => $existing['metaDescription'] ?? '',
    'metaKeywords' => $existing['metaKeywords'] ?? '',
    'canonical' => $existing['canonical'] ?? '',
    'ogImage' => $existing['ogImage'] ?? '',
    'noindex' => !empty($existing['noindex']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepImage = $category['image'];
    $category['name'] = post('name');
    $category['id'] = post('id') !== '' ? slugify(post('id')) : slugify(post('name'));
    $category['icon'] = post('icon');
    $category['desc'] = post('desc');
    $category['headline'] = post('headline');
    $category['intro'] = post('intro');
    $category['buyers'] = post('buyers');
    $category['useCases'] = post('useCases');
    $category['faqs'] = post('faqs');
    $category['metaTitle'] = post('metaTitle');
    $category['metaDescription'] = post('metaDescription');
    $category['metaKeywords'] = post('metaKeywords');
    $category['canonical'] = post('canonical');
    $category['ogImage'] = post('ogImage');
    $category['noindex'] = isset($_POST['noindex']);
    $now = gmdate('c');

    try {
        $category['image'] = (string) (save_uploaded_image('image', $keepImage !== '' ? $keepImage : null) ?? '');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }

    if ($category['name'] === '' || $category['id'] === '') {
        $errors[] = 'Name and ID are required.';
    }

    if (!$errors) {
        $useCases = json_encode(parse_blocks($category['useCases'], 'title', 'text'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $faqs = json_encode(parse_blocks($category['faqs'], 'q', 'a'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            if ($existing) {
                if ($category['id'] !== $existing['id']) {
                    db()->prepare('UPDATE products SET category = ? WHERE category = ?')->execute([$category['id'], $existing['id']]);
                    $stmt = db()->prepare(
                        'UPDATE categories SET id=?, icon=?, name=?, `desc`=?, headline=?, intro=?, buyers=?, image=?, use_cases=?, faqs=?,
                         meta_title=?, meta_description=?, meta_keywords=?, canonical=?, og_image=?, noindex=?, updated_at=? WHERE id=?'
                    );
                    $stmt->execute([
                        $category['id'], $category['icon'], $category['name'], $category['desc'], $category['headline'],
                        $category['intro'], $category['buyers'], $category['image'], $useCases, $faqs,
                        $category['metaTitle'], $category['metaDescription'], $category['metaKeywords'],
                        $category['canonical'], $category['ogImage'], $category['noindex'] ? 1 : 0, $now, $existing['id']
                    ]);
                } else {
                    $stmt = db()->prepare(
                        'UPDATE categories SET icon=?, name=?, `desc`=?, headline=?, intro=?, buyers=?, image=?, use_cases=?, faqs=?,
                         meta_title=?, meta_description=?, meta_keywords=?, canonical=?, og_image=?, noindex=?, updated_at=? WHERE id=?'
                    );
                    $stmt->execute([
                        $category['icon'], $category['name'], $category['desc'], $category['headline'],
                        $category['intro'], $category['buyers'], $category['image'], $useCases, $faqs,
                        $category['metaTitle'], $category['metaDescription'], $category['metaKeywords'],
                        $category['canonical'], $category['ogImage'], $category['noindex'] ? 1 : 0, $now, $existing['id']
                    ]);
                }
                flash('ok', 'Category updated.');
            } else {
                $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM categories')->fetchColumn();
                $stmt = db()->prepare(
                    'INSERT INTO categories (id, icon, name, `desc`, headline, intro, buyers, image, use_cases, faqs, sort_order,
                     meta_title, meta_description, meta_keywords, canonical, og_image, noindex, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $category['id'], $category['icon'], $category['name'], $category['desc'], $category['headline'],
                    $category['intro'], $category['buyers'], $category['image'], $useCases, $faqs, $sort,
                    $category['metaTitle'], $category['metaDescription'], $category['metaKeywords'],
                    $category['canonical'], $category['ogImage'], $category['noindex'] ? 1 : 0, $now
                ]);
                flash('ok', 'Category created.');
            }
            export_site_config_js();
            redirect(admin_url('categories/index.php'));
        } catch (PDOException $e) {
            $errors[] = (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate'))
                ? 'Category ID already exists.'
                : 'Could not save category.';
        }
    }
}

admin_header($existing ? 'Edit Category' : 'Add Category', 'categories', ['Categories' => 'categories/index.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Name
      <input type="text" name="name" required value="<?= e($category['name']) ?>">
    </label>
    <label>ID
      <input type="text" name="id" value="<?= e($category['id']) ?>" data-preview-base="/products/">
    </label>
    <label>Icon key
      <input type="text" name="icon" value="<?= e($category['icon']) ?>">
    </label>
    <label class="full">Short description
      <textarea name="desc" rows="2"><?= e($category['desc']) ?></textarea>
    </label>
    <label class="full">Card background image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Shown behind this category on the homepage. Recommended 1600 × 900 px (16:9). Leave empty to keep the current image.</span>
    </label>
    <?php if ($category['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($category['image'])) ?>" alt="Current category background">
        <span class="hint">Current background. Choose a new file to replace it.</span>
      </div>
    <?php endif; ?>
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
  <?php
  render_seo_panel($category, [
      'kind' => 'category',
      'previewBase' => 'products/',
      'slug' => $category['id'],
      'fallbackTitle' => $category['name'],
      'fallbackDescription' => $category['intro'] !== '' ? $category['intro'] : $category['desc'],
  ]);
  ?>

  <div class="form-actions">
    <button class="btn" type="submit">Save Category</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('categories/index.php')) ?>">Cancel</a>
    <?php if ($existing): ?>
      <a class="btn btn-secondary" href="<?= e(url_for(category_slug_path($category['id']))) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
