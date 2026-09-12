<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/seo_fields.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = $id ? get_product_row($id) : null;
if ($id && !$row) {
    flash('error', 'That product no longer exists.');
    redirect('products.php');
}
$categories = all_categories();
if (!$categories) {
    flash('error', 'Create a category before adding products.');
    redirect('categories.php');
}

$errors = [];
$notice = '';
$currentImages = json_list($row['images'] ?? '[]');
$product = [
    'slug' => $row['slug'] ?? '',
    'category' => $row['category'] ?? ($categories[0]['id'] ?? ''),
    'name' => $row['name'] ?? '',
    'short' => $row['short'] ?? '',
    'description' => $row['description'] ?? '',
    'tags' => array_to_lines(json_list($row['tags'] ?? '[]')),
    'features' => array_to_lines(json_list($row['features'] ?? '[]')),
    'images' => $currentImages,
    'metaTitle' => $row['meta_title'] ?? '',
    'metaDescription' => $row['meta_description'] ?? '',
    'metaKeywords' => $row['meta_keywords'] ?? '',
    'canonical' => $row['canonical'] ?? '',
    'ogImage' => $row['og_image'] ?? '',
    'noindex' => (int) ($row['noindex'] ?? 0) === 1,
    'brand' => $row['brand'] ?? '',
    'sku' => $row['sku'] ?? '',
    'gtin' => $row['gtin'] ?? '',
    'mpn' => $row['mpn'] ?? '',
    'condition' => $row['item_condition'] ?? '',
    'availability' => $row['availability'] ?? '',
    'price' => $row['price'] ?? '',
    'currency' => $row['currency'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $product['name'] = post('name');
    $product['slug'] = post('slug') !== '' ? slugify(post('slug')) : slugify(post('name'));
    $product['category'] = post('category');
    $product['short'] = post('short');
    $product['description'] = post('description');
    $product['tags'] = post('tags');
    $product['features'] = post('features');

    foreach (['metaTitle', 'metaDescription', 'metaKeywords', 'canonical', 'ogImage',
              'brand', 'sku', 'gtin', 'mpn', 'condition', 'availability', 'price', 'currency'] as $key) {
        $product[$key] = post($key);
    }
    $product['noindex'] = isset($_POST['noindex']);

    $kept = isset($_POST['keep_images']) && is_array($_POST['keep_images'])
        ? array_values(array_filter(array_map('strval', $_POST['keep_images'])))
        : [];
    $product['images'] = $kept;

    $validCategories = array_column($categories, 'id');

    if ($product['name'] === '') {
        $errors['name'] = 'Give the product a name.';
    }
    if ($product['slug'] === '') {
        $errors['slug'] = 'Add a slug, or a name we can build one from.';
    }
    if (!in_array($product['category'], $validCategories, true)) {
        $errors['category'] = 'Pick a category from the list.';
    }
    if ($product['short'] === '') {
        $errors['short'] = 'A short summary is shown on every product card.';
    }
    if ($product['canonical'] !== '' && !filter_var($product['canonical'], FILTER_VALIDATE_URL)) {
        $errors['canonical'] = 'Enter a full URL including https://, or leave it blank.';
    }
    if ($product['price'] !== '' && !is_numeric($product['price'])) {
        $errors['price'] = 'Price must be a number, or blank.';
    }

    $images = null;
    if (!$errors) {
        try {
            $merged = array_merge($kept, save_uploaded_images('image_files'));
            $product['images'] = $merged;
            $images = json_encode($merged, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            $errors['image_files'] = $e->getMessage();
        }
    }

    if (!$errors) {
        $tags = json_encode(lines_to_array($product['tags']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $features = json_encode(lines_to_array($product['features']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $now = gmdate('c');
        $seoArgs = [
            $product['metaTitle'], $product['metaDescription'], $product['metaKeywords'], $product['canonical'],
            $product['ogImage'], $product['noindex'] ? 1 : 0, $product['brand'], $product['sku'], $product['gtin'],
            $product['mpn'], $product['condition'], $product['availability'], $product['price'], $product['currency'],
            $now,
        ];

        try {
            if ($row) {
                $stmt = db()->prepare(
                    'UPDATE products SET slug=?, category=?, name=?, short=?, description=?, tags=?, features=?, images=?,
                     meta_title=?, meta_description=?, meta_keywords=?, canonical=?, og_image=?, noindex=?,
                     brand=?, sku=?, gtin=?, mpn=?, item_condition=?, availability=?, price=?, currency=?, updated_at=?
                     WHERE id=?'
                );
                $stmt->execute(array_merge(
                    [$product['slug'], $product['category'], $product['name'], $product['short'], $product['description'], $tags, $features, $images],
                    $seoArgs,
                    [$id]
                ));
                flash('ok', 'Saved “' . $product['name'] . '”.');
            } else {
                $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM products')->fetchColumn();
                $stmt = db()->prepare(
                    'INSERT INTO products (slug, category, name, short, description, tags, features, images, sort_order,
                     meta_title, meta_description, meta_keywords, canonical, og_image, noindex,
                     brand, sku, gtin, mpn, item_condition, availability, price, currency, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute(array_merge(
                    [$product['slug'], $product['category'], $product['name'], $product['short'], $product['description'], $tags, $features, $images, $sort],
                    $seoArgs
                ));
                flash('ok', 'Created “' . $product['name'] . '”.');
            }
            export_site_config_js();
            redirect('products.php');
        } catch (PDOException $e) {
            $duplicate = str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate');
            if ($duplicate) {
                $errors['slug'] = 'That slug is already used by another product.';
            } else {
                $notice = 'Could not save the product. Please try again.';
            }
        }
    }
}

admin_header($row ? 'Edit Product' : 'Add Product', 'products', ['Products' => 'products.php']);
?>
<?php if ($errors || $notice): ?>
  <div class="flash flash--error" role="alert">
    <span><?= $notice !== '' ? e($notice) : 'Please fix the highlighted ' . (count($errors) === 1 ? 'field' : 'fields') . ' below.' ?></span>
  </div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="<?= field_class($errors, 'name', true) ?>">Name
      <input type="text" name="name" required value="<?= e($product['name']) ?>" autofocus>
      <?= field_msg($errors, 'name') ?>
    </label>
    <label class="<?= field_class($errors, 'slug') ?>">Slug
      <input type="text" name="slug" value="<?= e($product['slug']) ?>" data-preview-base="/product/">
      <span class="hint">URL key, e.g. spring-post. Filled from the name if left blank.</span>
      <?= field_msg($errors, 'slug') ?>
    </label>
    <label class="<?= field_class($errors, 'category') ?>">Category
      <select name="category" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat['id']) ?>" <?= $product['category'] === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <?= field_msg($errors, 'category') ?>
    </label>
    <label class="<?= field_class($errors, 'short', true) ?>">Short summary
      <textarea name="short" rows="2" data-maxlen="160"><?= e($product['short']) ?></textarea>
      <span class="hint">Shown on product cards and used as the search description fallback.</span>
      <?= field_msg($errors, 'short') ?>
    </label>
    <label class="full">Full description
      <textarea name="description" rows="5"><?= e($product['description']) ?></textarea>
    </label>
    <label>Tags <span class="hint">(one per line)</span>
      <textarea name="tags" rows="5"><?= e($product['tags']) ?></textarea>
    </label>
    <label>Features <span class="hint">(one per line)</span>
      <textarea name="features" rows="5"><?= e($product['features']) ?></textarea>
    </label>
    <label class="<?= field_class($errors, 'image_files', true) ?>">Add images
      <input type="file" name="image_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
      <span class="hint">JPG, PNG, WebP, or GIF up to 5 MB each. The first image is the card thumbnail.</span>
      <?= field_msg($errors, 'image_files') ?>
    </label>
    <?php if ($product['images']): ?>
      <div class="full">
        <span class="hint">Current images &mdash; uncheck one to remove it on save.</span>
        <div class="file-preview" style="margin-top:0.5rem">
          <?php foreach ($product['images'] as $src): ?>
            <label class="keep-shot">
              <img src="../<?= e($src) ?>" alt="" loading="lazy">
              <span><input type="checkbox" name="keep_images[]" value="<?= e($src) ?>" checked> Keep</span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php
  render_seo_panel($product, [
      'kind' => 'product',
      'previewBase' => 'product/',
      'slug' => $product['slug'],
      'fallbackTitle' => $product['name'],
      'fallbackDescription' => $product['short'],
      'images' => $product['images'],
  ]);
  ?>
  <?= field_msg($errors, 'canonical') ?>
  <?= field_msg($errors, 'price') ?>

  <div class="form-actions">
    <button class="btn" type="submit"><?= $row ? 'Save Changes' : 'Create Product' ?></button>
    <a class="btn btn-secondary" href="products.php">Cancel</a>
    <?php if ($row): ?>
      <a class="btn btn-secondary" href="../product/<?= e(rawurlencode($product['slug'])) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
