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
<?php render_page_banner(['eyebrow' => 'Error 404', 'title' => "That page isn't here.", 'heroImage' => 'assets/img/hero.jpg'], []); ?>

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
