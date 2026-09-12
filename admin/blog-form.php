<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/seo_fields.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$existing = $index >= 0 ? setting_get_item('blog', $index) : null;
if ($index >= 0 && !$existing) {
    flash('error', 'That blog post no longer exists.');
    redirect('blog.php');
}

$item = [
    'slug' => $existing['slug'] ?? '',
    'title' => $existing['title'] ?? '',
    'date' => $existing['date'] ?? date('Y-m-d'),
    'excerpt' => $existing['excerpt'] ?? '',
    'body' => $existing['body'] ?? '',
    'image' => $existing['image'] ?? '',
    'metaTitle' => $existing['metaTitle'] ?? '',
    'metaDescription' => $existing['metaDescription'] ?? '',
    'metaKeywords' => $existing['metaKeywords'] ?? '',
    'canonical' => $existing['canonical'] ?? '',
    'ogImage' => $existing['ogImage'] ?? '',
    'noindex' => !empty($existing['noindex']),
    'author' => $existing['author'] ?? '',
    'schemaType' => $existing['schemaType'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepImage = $item['image'];
    $item = [
        'title' => post('title'),
        'slug' => post('slug') !== '' ? slugify(post('slug')) : slugify(post('title')),
        'date' => post('date'),
        'excerpt' => post('excerpt'),
        'body' => (string) ($_POST['body'] ?? ''),
        'image' => $keepImage,
        'metaTitle' => post('metaTitle'),
        'metaDescription' => post('metaDescription'),
        'metaKeywords' => post('metaKeywords'),
        'canonical' => post('canonical'),
        'ogImage' => post('ogImage'),
        'noindex' => isset($_POST['noindex']),
        'author' => post('author'),
        'schemaType' => post('schemaType'),
    ];

    try {
        $item['image'] = (string) (save_uploaded_image('image', $keepImage !== '' ? $keepImage : null) ?? '');
    } catch (Throwable $e) {
        $errors['image'] = $e->getMessage();
    }

    if ($item['title'] === '') {
        $errors['title'] = 'Give the post a title.';
    }
    if ($item['slug'] === '') {
        $errors['slug'] = 'Add a slug, or a title we can build one from.';
    }
    if ($item['excerpt'] === '') {
        $errors['excerpt'] = 'The excerpt is used on cards and in search results.';
    }
    if (trim($item['body']) === '') {
        $errors['body'] = 'Add the article body — posts without content rank poorly and read as duplicates.';
    }
    if ($item['date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $item['date'])) {
        $errors['date'] = 'Use a valid date.';
    }
    if ($item['canonical'] !== '' && !filter_var($item['canonical'], FILTER_VALIDATE_URL)) {
        $errors['canonical'] = 'Enter a full URL including https://, or leave it blank.';
    }

    foreach (setting_list('blog') as $i => $other) {
        if ($i !== $index && ($other['slug'] ?? '') === $item['slug']) {
            $errors['slug'] = 'Another post already uses that slug.';
            break;
        }
    }

    if (!$errors) {
        setting_save_item('blog', $existing ? $index : null, $item);
        export_site_config_js();
        flash('ok', 'Saved “' . $item['title'] . '”.');
        redirect('blog.php');
    }
}

admin_header($existing ? 'Edit Blog Post' : 'Add Blog Post', 'blog', ['Blog' => 'blog.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert">
    <span>Please fix the highlighted <?= count($errors) === 1 ? 'field' : 'fields' ?> below.</span>
  </div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="<?= field_class($errors, 'title', true) ?>">Title
      <input type="text" name="title" required value="<?= e($item['title']) ?>" autofocus>
      <?= field_msg($errors, 'title') ?>
    </label>
    <label class="<?= field_class($errors, 'slug') ?>">Slug
      <input type="text" name="slug" value="<?= e($item['slug']) ?>" data-preview-base="/blog/">
      <?= field_msg($errors, 'slug') ?>
    </label>
    <label class="<?= field_class($errors, 'date') ?>">Date
      <input type="date" name="date" value="<?= e($item['date']) ?>">
      <?= field_msg($errors, 'date') ?>
    </label>
    <label class="<?= field_class($errors, 'excerpt', true) ?>">Excerpt
      <textarea name="excerpt" rows="3" data-maxlen="200"><?= e($item['excerpt']) ?></textarea>
      <span class="hint">Shown on blog cards and used as the meta description fallback.</span>
      <?= field_msg($errors, 'excerpt') ?>
    </label>
    <label class="<?= field_class($errors, 'body', true) ?>">Article body
      <textarea name="body" rows="14"><?= e($item['body']) ?></textarea>
      <span class="hint">One paragraph per block, separated by a blank line. A line ending in a colon becomes a subheading.</span>
      <?= field_msg($errors, 'body') ?>
    </label>
    <label class="<?= field_class($errors, 'image', true) ?>">Cover image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Leave empty to keep the current photo. Used as the social share image.</span>
      <?= field_msg($errors, 'image') ?>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="../<?= e($item['image']) ?>" alt="Current cover image">
      </div>
    <?php endif; ?>
  </div>

  <?php
  render_seo_panel($item, [
      'kind' => 'article',
      'previewBase' => 'blog/',
      'slug' => $item['slug'],
      'fallbackTitle' => $item['title'],
      'fallbackDescription' => $item['excerpt'],
      'images' => $item['image'] !== '' ? [$item['image']] : [],
  ]);
  ?>
  <?= field_msg($errors, 'canonical') ?>

  <div class="form-actions">
    <button class="btn" type="submit"><?= $existing ? 'Save Changes' : 'Create Post' ?></button>
    <a class="btn btn-secondary" href="blog.php">Cancel</a>
    <?php if ($existing && $item['slug'] !== ''): ?>
      <a class="btn btn-secondary" href="../blog/<?= e(rawurlencode($item['slug'])) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
