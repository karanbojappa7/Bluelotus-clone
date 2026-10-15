<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$list = all_clients();
$existing = $index >= 0 ? ($list[$index] ?? null) : null;
if ($index >= 0 && !$existing) {
    flash('error', 'That client no longer exists.');
    redirect(admin_url('clients.php'));
}

$item = [
    'name' => $existing['name'] ?? '',
    'logo' => $existing['logo'] ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepLogo = (string) ($item['logo'] ?? '');
    $item = [
        'name' => post('name'),
        'logo' => $keepLogo,
    ];
    try {
        $item['logo'] = (string) (save_uploaded_image('logo', $keepLogo !== '' ? $keepLogo : null) ?? '');
    } catch (Throwable $e) {
        $errors['logo'] = $e->getMessage();
    }
    if ($item['name'] === '') {
        $errors['name'] = 'Add the company name.';
    }
    if (!$errors) {
        $next = all_clients();
        if ($existing) {
            $next[$index] = $item;
        } else {
            $next[] = $item;
        }
        save_setting('clients', array_values($next));
        export_site_config_js();
        flash('ok', 'Saved “' . $item['name'] . '”.');
        redirect(admin_url('clients.php'));
    }
}

admin_header($existing ? 'Edit Client' : 'Add Client', 'clients', ['Clients' => 'clients.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert">Please fix the highlighted fields below.</div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <p class="muted">Shown in the homepage “Trusted By” strip as the logo beside the company name.</p>
  <div class="form-grid">
    <label class="<?= field_class($errors, 'name', true) ?>">Company name
      <input type="text" name="name" required value="<?= e($item['name']) ?>" autofocus>
      <?= field_msg($errors, 'name') ?>
    </label>
    <label class="<?= field_class($errors, 'logo', true) ?>">Logo
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">PNG with a transparent background works best. Recommended 240 × 160 px.</span>
      <?= field_msg($errors, 'logo') ?>
    </label>
    <?php if ($item['logo'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['logo'])) ?>" alt="Current logo">
        <span class="hint">Current logo. Choose a new file to replace it.</span>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Client</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('clients.php')) ?>">Back</a>
  </div>
</form>
<?php admin_footer(); ?>
