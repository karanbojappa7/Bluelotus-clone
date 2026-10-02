<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$privacy = page_copy('privacy');
$crumbs = ['Home' => '/', 'Privacy Policy' => null];

page_head([
    'title' => page_text($privacy, 'title'),
    'description' => 'How ' . brand_name() . ' collects, uses, and protects the information you share through this website.',
    'canonical' => 'privacy-policy',
    'breadcrumbs' => $crumbs,
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light"><?= e(page_text($privacy, 'eyebrow')) ?></span>
    <h1 class="page-title"><?= e(page_text($privacy, 'title')) ?></h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container prose-wrap article-body">
    <?= render_article_body(page_text($privacy, 'body')) ?>
  </div>
</section>
<?php page_foot(['compactFooter' => true]); ?>
