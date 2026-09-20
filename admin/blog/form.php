<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_once __DIR__ . '/../lib/seo/seo_fields.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$existing = $index >= 0 ? setting_get_item('blog', $index) : null;
if ($index >= 0 && !$existing) {
    flash('error', 'That blog post no longer exists.');
    redirect(admin_url('blog/index.php'));
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
    'relatedPosts' => string_list($existing['relatedPosts'] ?? []),
    'relatedProducts' => string_list($existing['relatedProducts'] ?? []),
    'relatedCategories' => string_list($existing['relatedCategories'] ?? []),
];
$otherPosts = [];
foreach (setting_list('blog') as $i => $other) {
    if ($existing && $i === $index) {
        continue;
    }
    if (($other['slug'] ?? '') !== '') {
        $otherPosts[] = $other;
    }
}
$linkProducts = all_products();
$linkCategories = all_categories();
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
        'relatedPosts' => post_slug_list('relatedPosts'),
        'relatedProducts' => post_slug_list('relatedProducts'),
        'relatedCategories' => post_slug_list('relatedCategories'),
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
        redirect(admin_url('blog/index.php'));
    }
}

admin_header($existing ? 'Edit Blog Post' : 'Add Blog Post', 'blog', ['Blog' => 'blog/index.php']);
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
      <textarea name="body" id="blogBody" rows="14"><?= e($item['body']) ?></textarea>
      <span class="hint">One paragraph per block, separated by a blank line. A line ending in a colon becomes a subheading. Link with <code>[visible text](/products/fire-safety)</code> or use Insert below.</span>
      <?= field_msg($errors, 'body') ?>
    </label>
    <div class="full link-insert">
      <label>Insert internal link
        <select id="blogLinkTarget">
          <option value="">Choose a page, category, product, or post</option>
          <optgroup label="Pages">
            <option value="page:products" data-label="products">All products</option>
            <option value="page:about" data-label="About us">About us</option>
            <option value="page:contact" data-label="contact">Contact</option>
            <option value="page:faq" data-label="FAQs">FAQs</option>
            <option value="page:blog" data-label="blog">Blog</option>
          </optgroup>
          <?php if ($linkCategories): ?>
            <optgroup label="Categories">
              <?php foreach ($linkCategories as $cat): ?>
                <option value="category:<?= e($cat['id']) ?>" data-label="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <?php if ($linkProducts): ?>
            <optgroup label="Products">
              <?php foreach ($linkProducts as $product): ?>
                <option value="product:<?= e($product['slug']) ?>" data-label="<?= e($product['name']) ?>"><?= e($product['name']) ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
          <?php if ($otherPosts): ?>
            <optgroup label="Other posts">
              <?php foreach ($otherPosts as $other): ?>
                <option value="blog:<?= e($other['slug']) ?>" data-label="<?= e($other['title'] ?? '') ?>"><?= e($other['title'] ?? '') ?></option>
              <?php endforeach; ?>
            </optgroup>
          <?php endif; ?>
        </select>
      </label>
      <button type="button" class="btn btn-secondary" data-insert-blog-link>Insert at cursor</button>
    </div>
    <fieldset class="full link-panel">
      <legend>Related links</legend>
      <p class="hint">Shown beside the article. First mentions of product, category, and post names in the body are also linked automatically.</p>
      <div class="link-panel-grid">
        <div>
          <strong>Related posts</strong>
          <div class="link-checks">
            <?php if (!$otherPosts): ?>
              <span class="muted">Add another post first.</span>
            <?php endif; ?>
            <?php foreach ($otherPosts as $other): ?>
              <label>
                <input type="checkbox" name="relatedPosts[]" value="<?= e($other['slug']) ?>" <?= in_array($other['slug'], $item['relatedPosts'], true) ? 'checked' : '' ?>>
                <?= e($other['title'] ?? $other['slug']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <strong>Related categories</strong>
          <div class="link-checks">
            <?php foreach ($linkCategories as $cat): ?>
              <label>
                <input type="checkbox" name="relatedCategories[]" value="<?= e($cat['id']) ?>" <?= in_array($cat['id'], $item['relatedCategories'], true) ? 'checked' : '' ?>>
                <?= e($cat['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div>
          <strong>Related products</strong>
          <div class="link-checks">
            <?php foreach ($linkProducts as $product): ?>
              <label>
                <input type="checkbox" name="relatedProducts[]" value="<?= e($product['slug']) ?>" <?= in_array($product['slug'], $item['relatedProducts'], true) ? 'checked' : '' ?>>
                <?= e($product['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </fieldset>
    <label class="<?= field_class($errors, 'image', true) ?>">Cover image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Recommended 1600 × 900 px (16:9). Leave empty to keep the current photo. Used as the social share image.</span>
      <?= field_msg($errors, 'image') ?>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['image'])) ?>" alt="Current cover image">
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
    <a class="btn btn-secondary" href="<?= e(admin_url('blog/index.php')) ?>">Cancel</a>
    <?php if ($existing && $item['slug'] !== ''): ?>
      <a class="btn btn-secondary" href="<?= e(url_for(post_slug_path($item['slug']))) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
