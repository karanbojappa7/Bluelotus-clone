<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

http_response_code(404);

page_head([
    'title' => 'Page not found',
    'description' => 'That page does not exist. Browse the ' . brand_name() . ' safety equipment catalog or get in touch.',
    'canonical' => '404',
    'robots' => 'noindex, follow',
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light">Error 404</span>
    <h1 class="page-title">That page isn't here.</h1>
  </div>
</section>

<section class="section">
  <div class="container">
    <?= render_empty_state(
        'The link may be out of date.',
        "We reorganised our catalog URLs recently. Try the product catalog, or tell us what you were looking for and we'll point you to it.",
        browse_actions()
    ) ?>
  </div>
</section>
<?php page_foot(['skipQuoteModal' => true]); ?>
