<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = $id ? get_product_row($id) : null;
$categories = all_categories();
$errors = [];

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
    $kept = isset($_POST['keep_images']) && is_array($_POST['keep_images'])
        ? array_values(array_filter(array_map('strval', $_POST['keep_images'])))
        : [];

    if ($product['name'] === '') {
        $errors[] = 'Name is required.';
    }
    if ($product['category'] === '') {
        $errors[] = 'Category is required.';
    }
    if ($product['slug'] === '') {
        $errors[] = 'Slug is required.';
    }

    if (!$errors) {
        $tags = json_encode(lines_to_array($product['tags']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $features = json_encode(lines_to_array($product['features']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            $images = json_encode(array_merge($kept, save_uploaded_images('image_files')), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        try {
            if ($row) {
                $stmt = db()->prepare(
                    'UPDATE products SET slug=?, category=?, name=?, short=?, description=?, tags=?, features=?, images=? WHERE id=?'
                );
                $stmt->execute([
                    $product['slug'], $product['category'], $product['name'], $product['short'], $product['description'],
                    $tags, $features, $images, $id
                ]);
                flash('ok', 'Product updated.');
            } else {
                $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM products')->fetchColumn();
                $stmt = db()->prepare(
                    'INSERT INTO products (slug, category, name, short, description, tags, features, images, sort_order)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $product['slug'], $product['category'], $product['name'], $product['short'], $product['description'],
                    $tags, $features, $images, $sort
                ]);
                flash('ok', 'Product created.');
            }
            export_site_config_js();
            redirect('products.php');
        } catch (PDOException $e) {
            $errors[] = (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate'))
                ? 'Slug already exists.'
                : 'Could not save product.';
        }
    }
}

admin_header($row ? 'Edit Product' : 'Add Product', 'products');
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Name
      <input type="text" name="name" required value="<?= e($product['name']) ?>">
    </label>
    <label>Slug
      <input type="text" name="slug" value="<?= e($product['slug']) ?>">
      <span class="hint">URL key, e.g. spring-post</span>
    </label>
    <label>Category
      <select name="category" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat['id']) ?>" <?= $product['category'] === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label class="full">Short summary
      <textarea name="short" rows="2"><?= e($product['short']) ?></textarea>
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
    <label class="full">Images
      <input type="file" name="image_files[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple>
      <span class="hint">Choose one or more images from your computer. Uncheck a photo below to remove it.</span>
    </label>
    <?php if ($product['images']): ?>
      <div class="full file-preview">
        <?php foreach ($product['images'] as $src): ?>
          <label class="keep-shot">
            <img src="../<?= e($src) ?>" alt="">
            <span><input type="checkbox" name="keep_images[]" value="<?= e($src) ?>" checked> Keep</span>
          </label>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Product</button>
    <a class="btn btn-secondary" href="products.php">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
