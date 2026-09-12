<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$leadersHtml = db_ready() ? render_leadership_cards() : '';
$leadership = db_ready() ? all_leadership() : [];
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
    <span class="eyebrow eyebrow--light">About Bluelotus</span>
    <h1 class="page-title">Safety systems built by people who install them.</h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-2 split">
      <div data-reveal>
        <span class="eyebrow">Our Story</span>
        <h2 class="section-title">Founded on-site, not in a boardroom</h2>
        <p class="section-sub mt-3">Bluelotus Infrasafety started with a single fire-safety contract in Berhampur, Odisha in <span data-config="company.foundedYear"></span>. What we learned fitting out that first plant — that most safety failures come from mismatched vendors, not bad products — still shapes how we work today.</p>
        <p class="section-sub mt-3">We now run supply, installation, and maintenance under one roof across ten safety categories, so a facilities manager deals with one accountable partner instead of six.</p>
      </div>
      <div data-reveal>
        <img src="<?= e(url_for('assets/img/about.jpg')) ?>" alt="Bluelotus safety engineers on an industrial site" class="media-frame media-frame--tall" width="900" height="420" loading="lazy" data-fallback>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">Our Approach</span>
        <h2 class="section-title">Three commitments behind every project</h2>
      </div>
    </div>
    <div class="grid grid-3">
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-consult"></use></svg></div>
        <h3>Professional</h3>
        <p>Design, execution, commissioning, and maintenance run through the same certified in-house team on every engagement.</p>
      </div>
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-shield"></use></svg></div>
        <h3>Secure</h3>
        <p>Every product line is checked against IS and ISO standards before it enters our catalog, not after a complaint.</p>
      </div>
      <div class="tile" data-reveal>
        <div class="icon-wrap"><svg width="24" height="24" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-award"></use></svg></div>
        <h3>Guaranteed</h3>
        <p>We only recommend products with confirmed OEM after-sales support, so servicing is never a dead end.</p>
      </div>
    </div>
  </div>
</section>

<section class="section section--leaders">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <span class="eyebrow">Meet the team</span>
      <h2 class="section-title">Founders &amp; Leadership</h2>
      <p class="section-sub">The people guiding every site from first audit to handover.</p>
    </div>
    <div class="leader-grid"><?= $leadersHtml ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-banner" data-reveal>
      <div class="cta-banner-inner">
        <span class="eyebrow eyebrow--light eyebrow--center">Work With Us</span>
        <h2 class="section-title">Let's talk about your site's safety plan</h2>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Contact Our Team</a>
      </div>
    </div>
  </div>
</section>
<?php page_foot(); ?>
