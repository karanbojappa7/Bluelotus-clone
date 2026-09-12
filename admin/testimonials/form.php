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
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item = [
        'name' => post('name'),
        'role' => post('role'),
        'quote' => post('quote'),
    ];
    if ($item['name'] === '' || $item['quote'] === '') {
        flash('error', 'Name and quote are required.');
        redirect(admin_url('testimonials/form.php') . ($index >= 0 ? '?index=' . $index : ''));
    }
    setting_save_item('testimonials', $existing ? $index : null, $item);
    export_site_config_js();
    flash('ok', 'Testimonial saved.');
    redirect(admin_url('testimonials/index.php'));
}

admin_header($existing ? 'Edit Testimonial' : 'Add Testimonial', 'testimonials', ['Testimonials' => 'testimonials/index.php']);
?>
<form method="post" class="form-panel">
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
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('testimonials/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
