<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$ready = db_ready();
$groups = [];

if ($ready) {
    $general = site_faqs();
    if ($general) {
        $groups[] = ['eyebrow' => 'General', 'title' => 'Working with us', 'faqs' => $general];
    }
    foreach (all_categories() as $category) {
        $items = [];
        foreach (($category['faqs'] ?? []) as $faq) {
            $q = trim((string) ($faq['q'] ?? ''));
            $a = trim((string) ($faq['a'] ?? ''));
            if ($q !== '' && $a !== '') {
                $items[] = ['q' => $q, 'a' => $a];
            }
        }
        if ($items) {
            $groups[] = [
                'eyebrow' => 'Category',
                'title' => $category['name'],
                'href' => category_path($category['id']),
                'faqs' => $items,
            ];
        }
    }
}

$schemaFaqs = [];
foreach ($groups as $group) {
    foreach ($group['faqs'] as $faq) {
        $schemaFaqs[] = $faq;
    }
}

$crumbs = ['Home' => '/', 'FAQ' => null];

page_head([
    'title' => 'Frequently Asked Questions',
    'description' => 'Answers on supply coverage, installation, bulk orders, certification, quotes, and emergency support from ' . brand_name() . '.',
    'canonical' => 'faq',
    'breadcrumbs' => $crumbs,
    'schema' => array_values(array_filter([schema_faq($schemaFaqs)])),
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light">Help Center</span>
    <h1 class="page-title">Frequently asked questions.</h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if ($groups): ?>
      <div class="faq-page">
        <div class="faq-groups">
          <?php foreach ($groups as $group): ?>
            <div class="faq-group">
              <span class="eyebrow"><?= e($group['eyebrow']) ?></span>
              <h2 class="section-title mt-2">
                <?php if (!empty($group['href'])): ?>
                  <a href="<?= e($group['href']) ?>"><?= e($group['title']) ?></a>
                <?php else: ?>
                  <?= e($group['title']) ?>
                <?php endif; ?>
              </h2>
              <div class="faq-list mt-4">
                <?php foreach ($group['faqs'] as $faq): ?>
                  <details class="faq-item">
                    <summary><?= e($faq['q']) ?></summary>
                    <p><?= e($faq['a']) ?></p>
                  </details>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <?= render_empty_state(
          'No questions published yet.',
          "Ask us anything — we'll answer directly and add it here.",
          [['label' => 'Ask our team', 'href' => 'contact', 'primary' => true]]
      ) ?>
    <?php endif; ?>
  </div>
</section>

<section class="section section--dim">
  <div class="container">
    <div class="cta-banner">
      <div class="cta-banner-inner">
        <span class="eyebrow eyebrow--light eyebrow--center">Still have a question?</span>
        <h2 class="section-title">Talk to our team</h2>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Contact Us</a>
      </div>
    </div>
  </div>
</section>
<?php page_foot(); ?>
