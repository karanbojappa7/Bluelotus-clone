<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$seo = setting('seo', []);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $seo = [
        'domain' => rtrim(post('domain'), '/'),
        'defaultTitle' => post('defaultTitle'),
        'defaultDescription' => post('defaultDescription'),
        'keywords' => post('keywords'),
    ];
    save_setting('seo', $seo);
    export_site_config_js();
    flash('ok', 'SEO settings saved.');
    redirect('seo.php');
}

admin_header('SEO', 'seo');
?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Site domain
      <input type="url" name="domain" required value="<?= e($seo['domain'] ?? '') ?>">
    </label>
    <label class="full">Default title suffix / home title
      <input type="text" name="defaultTitle" value="<?= e($seo['defaultTitle'] ?? '') ?>">
    </label>
    <label class="full">Default meta description
      <textarea name="defaultDescription" rows="4"><?= e($seo['defaultDescription'] ?? '') ?></textarea>
    </label>
    <label class="full">Keywords
      <textarea name="keywords" rows="3"><?= e($seo['keywords'] ?? '') ?></textarea>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save SEO</button>
  </div>
</form>
<?php admin_footer(); ?>
