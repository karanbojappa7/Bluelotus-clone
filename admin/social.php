<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$social = setting('social', []);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $social = [
        'facebook' => post('facebook'),
        'linkedin' => post('linkedin'),
        'instagram' => post('instagram'),
        'youtube' => post('youtube'),
    ];
    save_setting('social', $social);
    export_site_config_js();
    flash('ok', 'Social links saved.');
    redirect('social.php');
}

admin_header('Social Links', 'social');
?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Facebook
      <input type="url" name="facebook" value="<?= e($social['facebook'] ?? '') ?>">
    </label>
    <label class="full">LinkedIn
      <input type="url" name="linkedin" value="<?= e($social['linkedin'] ?? '') ?>">
    </label>
    <label class="full">Instagram
      <input type="url" name="instagram" value="<?= e($social['instagram'] ?? '') ?>">
    </label>
    <label class="full">YouTube
      <input type="url" name="youtube" value="<?= e($social['youtube'] ?? '') ?>">
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Social Links</button>
  </div>
</form>
<?php admin_footer(); ?>
