<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$existing = $index >= 0 ? setting_get_item('blog', $index) : null;
$item = [
    'slug' => $existing['slug'] ?? '',
    'title' => $existing['title'] ?? '',
    'date' => $existing['date'] ?? date('Y-m-d'),
    'excerpt' => $existing['excerpt'] ?? '',
    'image' => $existing['image'] ?? '',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item = [
        'title' => post('title'),
        'slug' => post('slug') !== '' ? slugify(post('slug')) : slugify(post('title')),
        'date' => post('date'),
        'excerpt' => post('excerpt'),
        'image' => $item['image'],
    ];
    try {
        $item['image'] = (string) (save_uploaded_image('image', $item['image'] !== '' ? $item['image'] : null) ?? '');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if ($item['title'] === '' || $item['slug'] === '') {
        $error = $error !== '' ? $error : 'Title and slug are required.';
    }
    if ($error === '') {
        setting_save_item('blog', $existing ? $index : null, $item);
        export_site_config_js();
        flash('ok', 'Blog post saved.');
        redirect('blog.php');
    }
}

admin_header($existing ? 'Edit Blog Post' : 'Add Blog Post', 'blog');
?>
<?php if ($error): ?>
  <div class="flash flash--error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Title
      <input type="text" name="title" required value="<?= e($item['title']) ?>">
    </label>
    <label>Slug
      <input type="text" name="slug" value="<?= e($item['slug']) ?>">
    </label>
    <label>Date
      <input type="date" name="date" value="<?= e($item['date']) ?>">
    </label>
    <label class="full">Excerpt
      <textarea name="excerpt" rows="4"><?= e($item['excerpt']) ?></textarea>
    </label>
    <label class="full">Image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Choose an image from your computer. Leave empty to keep the current photo.</span>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="../<?= e($item['image']) ?>" alt="Current image">
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Post</button>
    <a class="btn btn-secondary" href="blog.php">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
