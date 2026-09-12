<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$clients = setting_list('clients');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $clients = lines_to_array(post('clients'));
    save_setting('clients', $clients);
    export_site_config_js();
    flash('ok', 'Client list saved.');
    redirect('clients.php');
}

admin_header('Clients', 'clients');
?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <p class="muted">Shown in the homepage marquee. One company name per line.</p>
  <label class="full">Client names
    <textarea name="clients" rows="12"><?= e(array_to_lines($clients)) ?></textarea>
  </label>
  <div class="form-actions">
    <button class="btn" type="submit">Save Clients</button>
  </div>
</form>
<?php admin_footer(); ?>
