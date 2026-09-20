<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$existing = $index >= 0 ? setting_get_item('testimonials', $index) : null;
$item = [
    'name' => $existing['name'] ?? '',
    'role' => $existing['role'] ?? '',
    'quote' => $existing['quote'] ?? '',
    'image' => $existing['image'] ?? '',
    'logo' => $existing['logo'] ?? '',
    'logoBackground' => sanitize_hex_color((string) ($existing['logoBackground'] ?? ''), '#ffffff'),
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepImage = (string) ($item['image'] ?? '');
    $keepLogo = (string) ($item['logo'] ?? '');
    $item = [
        'name' => post('name'),
        'role' => post('role'),
        'quote' => post('quote'),
        'image' => $keepImage,
        'logo' => $keepLogo,
        'logoBackground' => sanitize_hex_color(post('logoBackground'), '#ffffff'),
    ];
    try {
        $item['image'] = (string) (save_uploaded_image('image', $keepImage !== '' ? $keepImage : null) ?? '');
        $item['logo'] = (string) (save_uploaded_image('logo', $keepLogo !== '' ? $keepLogo : null) ?? '');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if ($item['name'] === '' || $item['quote'] === '') {
        $error = $error !== '' ? $error : 'Name and quote are required.';
    }
    if ($error === '') {
        setting_save_item('testimonials', $existing ? $index : null, $item);
        export_site_config_js();
        flash('ok', 'Testimonial saved.');
        redirect(admin_url('testimonials/index.php'));
    }
}

admin_header($existing ? 'Edit Testimonial' : 'Add Testimonial', 'testimonials', ['Testimonials' => 'testimonials/index.php']);
?>
<?php if ($error): ?>
  <div class="flash flash--error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Name
      <input type="text" name="name" required value="<?= e($item['name']) ?>">
    </label>
    <label>Role / company
      <input type="text" name="role" value="<?= e($item['role']) ?>">
    </label>
    <label class="full">Quote
      <textarea name="quote" rows="5" required><?= e($item['quote']) ?></textarea>
    </label>
    <label class="full">Photo
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Shown at the top of the homepage card. Recommended 800 × 500 px.</span>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['image'])) ?>" alt="Current photo">
        <span class="hint">Current photo. Choose a new file to replace it.</span>
      </div>
    <?php endif; ?>
    <label>Company logo
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">PNG with a transparent background works best. Recommended 240 × 160 px.</span>
    </label>
    <label>Logo background
      <input type="color" name="logoBackground" value="<?= e($item['logoBackground']) ?>">
      <span class="hint">Plate colour behind the logo on the dark homepage cards.</span>
    </label>
    <?php if ($item['logo'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['logo'])) ?>" alt="Current logo" style="object-fit:contain;background:<?= e($item['logoBackground']) ?>">
        <span class="hint">Current logo. Choose a new file to replace it.</span>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('testimonials/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
