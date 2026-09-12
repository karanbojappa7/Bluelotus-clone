<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$faqs = site_faqs();
$errors = [];
$raw = faqs_to_text($faqs);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $raw = (string) ($_POST['faqs'] ?? '');
    $parsed = [];
    foreach (parse_blocks($raw, 'q', 'a') as $faq) {
        $parsed[] = ['q' => rtrim($faq['q'], '?') . '?', 'a' => $faq['a']];
    }

    if (!$parsed && trim($raw) !== '') {
        $errors[] = 'Nothing could be parsed. Put the question on one line and the answer on the next.';
    }

    if (!$errors) {
        save_setting('faqs', $parsed);
        export_site_config_js();
        flash('ok', count($parsed) . ' FAQ' . (count($parsed) === 1 ? '' : 's') . ' saved.');
        redirect(admin_url('faqs/index.php'));
    }
}

admin_header('FAQ', 'faqs');
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert"><span><?= e(implode(' ', $errors)) ?></span></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <p class="muted" style="margin:0 0 1rem">
    These publish to the FAQ page and are submitted to Google as <code>FAQPage</code> structured data, so they can
    appear as expandable answers directly in search results. Put the <strong>question on one line</strong>, the
    <strong>answer on the next</strong>, and leave a <strong>blank line</strong> between entries.
  </p>
  <label class="full">Questions &amp; answers
    <textarea name="faqs" rows="20" spellcheck="true"><?= e($raw) ?></textarea>
    <span class="hint">Aim for answers of 2–3 sentences. Google ignores answers that are thin or duplicated.</span>
  </label>
  <div class="form-actions">
    <button class="btn" type="submit">Save FAQs</button>
    <a class="btn btn-secondary" href="<?= e(url_for('faq')) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
  </div>
</form>
<?php admin_footer(); ?>
