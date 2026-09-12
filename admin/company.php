<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$company = setting('company', []);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $company = [
        'name' => post('name'),
        'shortName' => post('shortName'),
        'tagline' => post('tagline'),
        'legalName' => post('legalName'),
        'foundedYear' => (int) post('foundedYear'),
        'logo' => $company['logo'] ?? '',
        'logoMark' => $company['logoMark'] ?? '',
    ];
    try {
        $company['logo'] = (string) (save_uploaded_image('logo', $company['logo'] !== '' ? $company['logo'] : null) ?? '');
        $company['logoMark'] = (string) (save_uploaded_image('logoMark', $company['logoMark'] !== '' ? $company['logoMark'] : null) ?? '');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if ($error === '') {
        save_setting('company', $company);
        export_site_config_js();
        flash('ok', 'Company details saved.');
        redirect('company.php');
    }
}

admin_header('Company', 'company');
?>
<?php if ($error): ?>
  <div class="flash flash--error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Company name
      <input type="text" name="name" required value="<?= e($company['name'] ?? '') ?>">
    </label>
    <label>Short name
      <input type="text" name="shortName" value="<?= e($company['shortName'] ?? '') ?>">
    </label>
    <label class="full">Tagline
      <input type="text" name="tagline" value="<?= e($company['tagline'] ?? '') ?>">
    </label>
    <label>Legal name
      <input type="text" name="legalName" value="<?= e($company['legalName'] ?? '') ?>">
    </label>
    <label>Founded year
      <input type="number" name="foundedYear" value="<?= e((string) ($company['foundedYear'] ?? '')) ?>">
    </label>
    <label>Logo
      <input type="file" name="logo" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Choose a logo image from your computer.</span>
    </label>
    <label>Logo mark
      <input type="file" name="logoMark" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Small square mark used in the header.</span>
    </label>
    <?php if (!empty($company['logo']) || !empty($company['logoMark'])): ?>
      <div class="full file-preview">
        <?php if (!empty($company['logo'])): ?>
          <div class="keep-shot">
            <img src="../<?= e($company['logo']) ?>" alt="Logo">
            <span class="hint">Current logo</span>
          </div>
        <?php endif; ?>
        <?php if (!empty($company['logoMark'])): ?>
          <div class="keep-shot">
            <img src="../<?= e($company['logoMark']) ?>" alt="Logo mark">
            <span class="hint">Current mark</span>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Company</button>
  </div>
</form>
<?php admin_footer(); ?>
