<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$analytics = setting('analytics', []);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $analytics = [
        'gaId' => trim(post('gaId')),
        'gtmId' => trim(post('gtmId')),
        'metaPixelId' => trim(post('metaPixelId')),
        'adsId' => strtoupper(trim(post('adsId'))),
        'adsLabel' => trim(post('adsLabel')),
        'customHead' => (string) ($_POST['customHead'] ?? ''),
    ];

    if ($analytics['gaId'] !== '' && !preg_match('/^G-[A-Z0-9]{4,}$/', $analytics['gaId'])) {
        $errors['gaId'] = 'Should look like G-XXXXXXXXXX, or leave it blank.';
    }
    if ($analytics['gtmId'] !== '' && !preg_match('/^GTM-[A-Z0-9]{4,}$/', $analytics['gtmId'])) {
        $errors['gtmId'] = 'Should look like GTM-XXXXXXX, or leave it blank.';
    }
    if ($analytics['metaPixelId'] !== '' && !preg_match('/^\d{6,}$/', $analytics['metaPixelId'])) {
        $errors['metaPixelId'] = 'Should be a numeric Pixel ID, or leave it blank.';
    }
    if ($analytics['adsId'] !== '' && !preg_match('/^AW-\d{6,}$/', $analytics['adsId'])) {
        $errors['adsId'] = 'Should look like AW-123456789, or leave it blank.';
    }
    if ($analytics['adsLabel'] !== '' && !preg_match('/^[A-Za-z0-9_-]{4,}$/', $analytics['adsLabel'])) {
        $errors['adsLabel'] = 'Paste the conversion label from Google Ads, or leave it blank.';
    }

    if (!$errors) {
        save_setting('analytics', $analytics);
        flash('ok', 'Analytics settings saved.');
        redirect(admin_url('analytics.php'));
    }
}

admin_header('Analytics', 'analytics');
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert"><span>Please fix the highlighted fields below.</span></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <p class="hint" style="margin:0 0 1rem">
    Any ID entered here is injected on every public page automatically — no template edits needed.
    Leave a field blank to skip that integration.
  </p>
  <div class="form-grid">
    <label class="<?= field_class($errors, 'gaId') ?>">Google Analytics 4 Measurement ID
      <input type="text" name="gaId" value="<?= e($analytics['gaId'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
      <?= field_msg($errors, 'gaId') ?>
    </label>
    <label class="<?= field_class($errors, 'gtmId') ?>">Google Tag Manager Container ID
      <input type="text" name="gtmId" value="<?= e($analytics['gtmId'] ?? '') ?>" placeholder="GTM-XXXXXXX">
      <span class="hint">Adds both the head script and the required body noscript tag.</span>
      <?= field_msg($errors, 'gtmId') ?>
    </label>
    <label class="<?= field_class($errors, 'metaPixelId') ?>">Meta (Facebook) Pixel ID
      <input type="text" name="metaPixelId" value="<?= e($analytics['metaPixelId'] ?? '') ?>" placeholder="123456789012345">
      <?= field_msg($errors, 'metaPixelId') ?>
    </label>
    <label class="<?= field_class($errors, 'adsId') ?>">Google Ads Conversion ID
      <input type="text" name="adsId" value="<?= e($analytics['adsId'] ?? '') ?>" placeholder="AW-123456789">
      <span class="hint">Loads with the quote popup so a successful lead can fire as a Google Ads conversion.</span>
      <?= field_msg($errors, 'adsId') ?>
    </label>
    <label class="<?= field_class($errors, 'adsLabel') ?>">Google Ads Conversion Label
      <input type="text" name="adsLabel" value="<?= e($analytics['adsLabel'] ?? '') ?>" placeholder="AbCDeFghIjkLmNoP">
      <span class="hint">From Google Ads &rarr; Goals &rarr; Conversions. Needed together with the Conversion ID.</span>
      <?= field_msg($errors, 'adsLabel') ?>
    </label>
    <label class="full">Custom tracking code <span class="hint">(optional)</span>
      <textarea name="customHead" rows="8" spellcheck="false"><?= e($analytics['customHead'] ?? '') ?></textarea>
      <span class="hint">
        Paste any other tracking snippet here (Microsoft Clarity, Hotjar, LinkedIn Insight, etc.) &mdash;
        the full <code>&lt;script&gt;</code> tag included. It is inserted before <code>&lt;/head&gt;</code>
        on every page exactly as pasted, so only add code you trust.
      </span>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Analytics</button>
  </div>
</form>
<?php admin_footer(); ?>
