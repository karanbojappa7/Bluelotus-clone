<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$leadersHtml = db_ready() ? render_leadership_cards() : '';
$leadership = db_ready() ? all_leadership() : [];
$about = page_copy('about');
$crumbs = ['Home' => '/', 'About Us' => null];

$team = [];
foreach ($leadership as $person) {
    if (($person['name'] ?? '') === '') {
        continue;
    }
    $team[] = array_filter([
        '@type' => 'Person',
        'name' => (string) $person['name'],
        'jobTitle' => (string) ($person['designation'] ?? ''),
        'description' => (string) ($person['background'] ?? ''),
        'image' => $person['image'] !== '' ? seo_image((string) $person['image']) : null,
        'sameAs' => $person['linkedin'] !== '' ? [(string) $person['linkedin']] : null,
    ]);
}

$aboutPage = array_filter([
    '@type' => 'AboutPage',
    '@id' => seo_url('about') . '#webpage',
    'url' => seo_url('about'),
    'name' => 'About Us',
    'isPartOf' => ['@id' => seo_url('#website')],
    'about' => ['@id' => seo_url('#organization')],
    'mainEntity' => ['@id' => seo_url('#organization')],
]);

page_head([
    'title' => 'About Us',
    'description' => 'Bluelotus Infrasafety has equipped over 4,200 sites across India with certified fire, road, and industrial safety systems since 2011.',
    'canonical' => 'about',
    'breadcrumbs' => $crumbs,
    'schema' => array_values(array_filter([$aboutPage, $team ? ['@type' => 'ItemList', 'name' => 'Leadership', 'itemListElement' => $team] : null])),
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light"><?= e(page_text($about, 'eyebrow')) ?></span>
    <h1 class="page-title"><?= e(page_text($about, 'title')) ?></h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-2 split">
      <div data-reveal>
        <span class="eyebrow"><?= e(page_text($about, 'storyEyebrow')) ?></span>
        <h2 class="section-title"><?= e(page_text($about, 'storyTitle')) ?></h2>
        <p class="section-sub mt-3"><?= e(page_text($about, 'storyBody1')) ?></p>
        <p class="section-sub mt-3"><?= e(page_text($about, 'storyBody2')) ?></p>
      </div>
      <div data-reveal>
        <img src="<?= e(url_for((string) ($about['storyImage'] !== '' ? $about['storyImage'] : 'assets/img/about.jpg'))) ?>" alt="Bluelotus safety engineers on an industrial site" class="media-frame media-frame--tall" width="900" height="420" loading="lazy" data-fallback>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow"><?= e(page_text($about, 'approachEyebrow')) ?></span>
        <h2 class="section-title"><?= e(page_text($about, 'approachTitle')) ?></h2>
      </div>
    </div>
    <div class="grid grid-3">
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-consult"></use></svg></div>
        <h3><?= e(page_text($about, 'tile1Title')) ?></h3>
        <p><?= e(page_text($about, 'tile1Body')) ?></p>
      </div>
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-shield"></use></svg></div>
        <h3><?= e(page_text($about, 'tile2Title')) ?></h3>
        <p><?= e(page_text($about, 'tile2Body')) ?></p>
      </div>
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-award"></use></svg></div>
        <h3><?= e(page_text($about, 'tile3Title')) ?></h3>
        <p><?= e(page_text($about, 'tile3Body')) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="section section--leaders">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow"><?= e(page_text($about, 'teamEyebrow')) ?></span>
      <h2 class="section-title"><?= e(page_text($about, 'teamTitle')) ?></h2>
      <p class="section-sub"><?= e(page_text($about, 'teamIntro')) ?></p>
    </div>
    <div class="leader-grid"><?= $leadersHtml ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-banner" data-reveal>
      <div class="cta-banner-inner">
        <span class="eyebrow eyebrow--light eyebrow--center"><?= e(page_text($about, 'ctaEyebrow')) ?></span>
        <h2 class="section-title"><?= e(page_text($about, 'ctaTitle')) ?></h2>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4"><?= e(page_text($about, 'ctaButton')) ?></a>
      </div>
    </div>
  </div>
</section>
<?php page_foot(); ?>
