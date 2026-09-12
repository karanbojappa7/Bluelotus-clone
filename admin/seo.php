<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$seo = setting('seo', []);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $keepImage = (string) ($seo['ogImage'] ?? '');
    $seo = [
        'domain' => rtrim(post('domain'), '/'),
        'defaultTitle' => post('defaultTitle'),
        'defaultDescription' => post('defaultDescription'),
        'keywords' => post('keywords'),
        'twitterHandle' => ltrim(post('twitterHandle'), '@'),
        'googleVerification' => post('googleVerification'),
        'bingVerification' => post('bingVerification'),
        'ogImage' => $keepImage,
    ];

    if ($seo['domain'] === '' || !filter_var($seo['domain'], FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a full site domain including https://.';
    }
    if ($seo['defaultDescription'] === '') {
        $errors[] = 'Default meta description is required — it is the fallback for every page.';
    }

    if (!$errors) {
        try {
            $seo['ogImage'] = (string) (save_uploaded_image('ogImage', $keepImage !== '' ? $keepImage : null) ?? '');
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (!$errors) {
        save_setting('seo', $seo);
        export_site_config_js();
        flash('ok', 'SEO settings saved.');
        redirect('seo.php');
    }
}

admin_header('SEO', 'seo');
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <p class="hint" style="margin:0 0 1rem">
    These are the site-wide fallbacks used on every page that doesn't set its own title, description,
    or image — Products, Categories, and Blog posts each override these individually on their own edit screen.
  </p>
  <div class="form-grid">
    <label class="full">Site domain
      <input type="url" name="domain" required value="<?= e($seo['domain'] ?? '') ?>" placeholder="https://www.example.com">
      <span class="hint">No trailing slash. Used to build every canonical URL, sitemap entry, and share link.</span>
    </label>
    <label class="full">Default title suffix / home title
      <input type="text" name="defaultTitle" value="<?= e($seo['defaultTitle'] ?? '') ?>" data-maxlen="60">
    </label>
    <label class="full">Default meta description
      <textarea name="defaultDescription" rows="3" required data-maxlen="158"><?= e($seo['defaultDescription'] ?? '') ?></textarea>
    </label>
    <label class="full">Site-wide keywords
      <textarea name="keywords" rows="2"><?= e($seo['keywords'] ?? '') ?></textarea>
      <span class="hint">Comma separated. Low ranking value on its own — pages with their own focus keywords override this.</span>
    </label>
    <label class="full">Default social share image
      <input type="file" name="ogImage" accept="image/jpeg,image/png,image/webp">
      <span class="hint">Shown when a page without its own image is shared on WhatsApp, LinkedIn, or Facebook. Recommended 1200×630.</span>
    </label>
    <?php if (!empty($seo['ogImage'])): ?>
      <div class="full file-preview">
        <img src="../<?= e($seo['ogImage']) ?>" alt="Current default share image" loading="lazy">
      </div>
    <?php endif; ?>
    <label>Twitter / X handle
      <input type="text" name="twitterHandle" value="<?= e($seo['twitterHandle'] ?? '') ?>" placeholder="yourhandle">
      <span class="hint">Without the @. Adds a twitter:site tag so shares are attributed to your account.</span>
    </label>
  </div>

  <h2 style="font-size:1rem;margin:1.5rem 0 0.75rem">Search engine verification</h2>
  <p class="hint" style="margin:-0.35rem 0 0.9rem">
    Paste just the content value from each provider's HTML-tag verification method — not the whole tag.
  </p>
  <div class="form-grid">
    <label>Google Search Console
      <input type="text" name="googleVerification" value="<?= e($seo['googleVerification'] ?? '') ?>" placeholder="e.g. AbCdEf123...">
    </label>
    <label>Bing Webmaster Tools
      <input type="text" name="bingVerification" value="<?= e($seo['bingVerification'] ?? '') ?>" placeholder="e.g. 1A2B3C...">
    </label>
  </div>

  <div class="form-actions">
    <button class="btn" type="submit">Save SEO</button>
    <a class="btn btn-secondary" href="<?= e(url_for('sitemap.xml')) ?>" target="_blank" rel="noopener">View Sitemap &#8599;</a>
  </div>
</form>
<?php admin_footer(); ?>
