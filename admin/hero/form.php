<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$list = setting_list('hero') ?: default_hero_slides();
$existing = $index >= 0 && isset($list[$index]) ? $list[$index] : null;
$errors = [];
$item = [
    'eyebrow' => $existing['eyebrow'] ?? '',
    'title' => $existing['title'] ?? '',
    'highlight' => $existing['highlight'] ?? '',
    'subtitle' => $existing['subtitle'] ?? '',
    'image' => $existing['image'] ?? '',
    'navLabel' => $existing['navLabel'] ?? '',
    'buttonLabel' => $existing['buttonLabel'] ?? 'Learn More',
    'buttonUrl' => $existing['buttonUrl'] ?? 'contact',
    'button2Label' => $existing['button2Label'] ?? '',
    'button2Url' => $existing['button2Url'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepImage = $item['image'];
    $item = [
        'eyebrow' => post('eyebrow'),
        'title' => post('title'),
        'highlight' => post('highlight'),
        'subtitle' => post('subtitle'),
        'image' => $keepImage,
        'navLabel' => post('navLabel'),
        'buttonLabel' => post('buttonLabel'),
        'buttonUrl' => post('buttonUrl'),
        'button2Label' => post('button2Label'),
        'button2Url' => post('button2Url'),
    ];
    try {
        $item['image'] = (string) (save_uploaded_image('image', $keepImage !== '' ? $keepImage : null) ?? '');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
    if ($item['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if ($item['image'] === '') {
        $errors[] = 'Upload a background image for this slide.';
    }
    if ($item['navLabel'] === '') {
        $item['navLabel'] = $item['eyebrow'] !== '' ? $item['eyebrow'] : 'Slide';
    }
    if (!$errors) {
        $list = setting_list('hero') ?: default_hero_slides();
        if ($existing) {
            $list[$index] = $item;
        } else {
            $list[] = $item;
        }
        save_setting('hero', array_values($list));
        export_site_config_js();
        flash('ok', 'Banner slide saved.');
        redirect(admin_url('hero/index.php'));
    }
}

admin_header($existing ? 'Edit Banner Slide' : 'Add Banner Slide', 'hero', ['Homepage Banner' => 'hero/index.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <p class="muted">Shown in the full-screen homepage carousel. The nav label is the tab at the bottom of the banner.</p>
  <div class="form-grid">
    <label>Eyebrow
      <input type="text" name="eyebrow" value="<?= e($item['eyebrow']) ?>" placeholder="Fire Safety">
    </label>
    <label>Nav label
      <input type="text" name="navLabel" value="<?= e($item['navLabel']) ?>" placeholder="Fire">
      <span class="hint">Short tab name under the banner (e.g. Protect, Fire, Road)</span>
    </label>
    <label class="full">Title
      <input type="text" name="title" required value="<?= e($item['title']) ?>">
    </label>
    <label class="full">Highlighted phrase
      <input type="text" name="highlight" value="<?= e($item['highlight']) ?>" placeholder="every worksite,">
      <span class="hint">Optional. This phrase in the title is italicized.</span>
    </label>
    <label class="full">Subtitle
      <textarea name="subtitle" rows="3"><?= e($item['subtitle']) ?></textarea>
    </label>
    <label class="full">Background image
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Wide landscape photo works best. Leave empty to keep the current image.</span>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['image'])) ?>" alt="Current banner image">
      </div>
    <?php endif; ?>
    <label>Button label
      <input type="text" name="buttonLabel" value="<?= e($item['buttonLabel']) ?>">
    </label>
    <label>Button link
      <input type="text" name="buttonUrl" value="<?= e($item['buttonUrl']) ?>" placeholder="contact">
      <span class="hint">Page path such as contact, products, faq, or products/fire-safety. Full https:// URLs also work.</span>
    </label>
    <label>Second button label
      <input type="text" name="button2Label" value="<?= e($item['button2Label']) ?>" placeholder="Optional">
    </label>
    <label>Second button link
      <input type="text" name="button2Url" value="<?= e($item['button2Url']) ?>" placeholder="products">
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Slide</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('hero/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
