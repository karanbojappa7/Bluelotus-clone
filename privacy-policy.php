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
<?php render_page_banner($privacy, $crumbs); ?>

<section class="section">
  <div class="container prose-wrap article-body">
    <?= render_article_body(page_text($privacy, 'body')) ?>
  </div>
</section>
<?php page_foot(['compactFooter' => true]); ?>
