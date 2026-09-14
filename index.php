<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

page_head([
    'fullTitle' => brand_name() . ' | ' . (string) (seo_settings()['defaultTitle'] ?? 'Safety Equipment Supplier'),
    'description' => (string) (seo_settings()['defaultDescription'] ?? ''),
    'canonical' => '',
    'breadcrumbs' => ['Home' => null],
    'bodyAttr' => 'data-blog-limit="4" data-products-limit="12"',
]);
?>
<?php
$heroSlides = hero_slides();
?>
<section class="hero-carousel" id="heroCarousel">
  <div class="hero-carousel-pin">
    <div class="hero-slides">
      <?php foreach ($heroSlides as $i => $slide): ?>
      <article class="hero-slide<?= $i === 0 ? ' is-active' : '' ?>" data-hero-index="<?= $i ?>">
        <div class="hero-slide-media" style="background-image:url('<?= e(hero_image_url($slide)) ?>')"></div>
        <div class="container hero-slide-copy">
          <?php if (trim((string) ($slide['eyebrow'] ?? '')) !== ''): ?>
            <span class="eyebrow eyebrow--light"><?= e((string) $slide['eyebrow']) ?></span>
          <?php endif; ?>
          <h1 class="hero-title"><?= hero_title_html($slide) ?></h1>
          <?php if (trim((string) ($slide['subtitle'] ?? '')) !== ''): ?>
            <p class="hero-sub"><?= e((string) $slide['subtitle']) ?></p>
          <?php endif; ?>
          <?php
          $btn1 = trim((string) ($slide['buttonLabel'] ?? ''));
          $btn2 = trim((string) ($slide['button2Label'] ?? ''));
          ?>
          <?php if ($btn1 !== '' || $btn2 !== ''): ?>
          <!-- <div class="hero-actions">
            <?php if ($btn1 !== ''): ?>
              <a href="<?= e(hero_link((string) ($slide['buttonUrl'] ?? ''), 'contact')) ?>" class="btn btn-primary"><?= e($btn1) ?> <svg width="16" height="16" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg#icon-arrow')) ?>"></use></svg></a>
            <?php endif; ?>
            <?php if ($btn2 !== ''): ?>
              <a href="<?= e(hero_link((string) ($slide['button2Url'] ?? ''), 'products')) ?>" class="btn btn-ghost"><?= e($btn2) ?></a>
            <?php endif; ?>
          </div> -->
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php if (count($heroSlides) > 1): ?>
    <nav class="hero-carousel-nav" aria-label="Hero topics">
      <?php foreach ($heroSlides as $i => $slide): ?>
        <button type="button" class="<?= $i === 0 ? 'is-active' : '' ?>" data-hero-goto="<?= $i ?>"><span><?php
          $nav = trim((string) ($slide['navLabel'] ?? ''));
          if ($nav === '') {
              $nav = trim((string) ($slide['eyebrow'] ?? ''));
          }
          echo e($nav !== '' ? $nav : ('Slide ' . ($i + 1)));
        ?></span></button>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <button type="button" class="hero-scroll-cue" data-hero-next>Scroll</button>
  </div>
</section>

<?php
$homeStats = setting_list('stats');
$statCount = count($homeStats);
$statCols = min(max($statCount, 1), 4);
?>
<?php if ($statCount): ?>
<section class="hero-stats hero-stats--after">
  <div class="container">
    <div class="stats-grid" data-stat-count="<?= (int) $statCount ?>" style="--stat-count: <?= (int) $statCols ?>">
      <?php foreach ($homeStats as $stat): ?>
        <?php
        $statValue = (string) ($stat['value'] ?? '');
        $statSuffix = (string) ($stat['suffix'] ?? '');
        ?>
        <div class="stat-plate">
          <div class="value" data-count="<?= e($statValue) ?>" data-suffix="<?= e($statSuffix) ?>">0<?= e($statSuffix) ?></div>
          <div class="label"><?= e((string) ($stat['label'] ?? '')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">What We Supply</span>
        <h2 class="section-title">Ten categories, one accountable vendor</h2>
      </div>
      <p class="section-sub" data-reveal>From roadside barricading to warehouse rack guards, every category is stocked, certified, and backed by our own installation crews.</p>
    </div>
    <div class="grid grid-3" data-render="categories"></div>
    <div class="text-center mt-5" data-reveal>
      <a href="<?= e(url_for('products')) ?>" class="btn btn-outline">View All Products <svg width="16" height="16" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg#icon-arrow')) ?>"></use></svg></a>
    </div>
  </div>
</section>

<section class="section" id="services">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">What We Do</span>
        <h2 class="section-title">Supply is the start. Service is the contract.</h2>
      </div>
      <p class="section-sub" data-reveal>Consultancy, installation, and maintenance from the same crew that specified the equipment — so accountability does not stop at delivery.</p>
    </div>
    <div data-reveal>
      <div class="service-tabs" data-render="service-tabs"></div>
      <div data-render="service-panels"></div>
    </div>
  </div>
</section>

<section class="section section--dim">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">Product Catalog</span>
        <h2 class="section-title">Equipment stocked for live sites</h2>
      </div>
      <a href="<?= e(url_for('products')) ?>" class="btn btn-outline" data-reveal>Browse Full Range</a>
    </div>
    <div class="product-grid" data-render="products"></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-2 split">
      <div data-reveal>
        <span class="eyebrow">Why Bluelotus</span>
        <h2 class="section-title">Built for accountability, not just supply</h2>
        <p class="section-sub">We hold the relationship end to end — audit, spec, install, and maintain — so nothing falls through the gap between vendors.</p>
        <div class="cluster mt-4">
          <span class="badge"><svg width="14" height="14" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg#icon-shield')) ?>"></use></svg> ISO Certified</span>
          <span class="badge"><svg width="14" height="14" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg#icon-award')) ?>"></use></svg> OEM Backed</span>
          <span class="badge"><svg width="14" height="14" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg#icon-truck')) ?>"></use></svg> Pan-India Delivery</span>
        </div>
      </div>
      <div data-reveal>
        <div class="feature-row"><span class="feature-num">01</span><div><h4>Professional</h4><p>An in-house team of certified safety engineers handles design, execution, commissioning, and maintenance across every category we sell.</p></div></div>
        <div class="feature-row"><span class="feature-num">02</span><div><h4>Secure</h4><p>Every product is sourced against regulatory standards first, curated for reliability, not just price point.</p></div></div>
        <div class="feature-row"><span class="feature-num">03</span><div><h4>Guaranteed</h4><p>We only integrate products with confirmed OEM after-sales support, so warranty and servicing are never in question.</p></div></div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">FAQ</span>
        <h2 class="section-title">Answers before you call</h2>
      </div>
      <a href="<?= e(url_for('faq')) ?>" class="btn btn-outline" data-reveal>View All FAQs</a>
    </div>
    <div class="faq-list" data-render="faqs" data-reveal></div>
  </div>
</section>

<section class="section section--ink">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow eyebrow--light">Client Feedback</span>
        <h2 class="section-title">Trusted on sites across Odisha</h2>
      </div>
    </div>
    <div class="grid grid-3" data-render="testimonials"></div>
  </div>
</section>

<section class="section--tight">
  <div class="container">
    <p class="eyebrow eyebrow--center mb-4">Trusted By Companies Across India</p>
  </div>
  <div class="client-marquee">
    <div class="client-track" data-render="clients"></div>
  </div>
</section>

<section class="section section--dim">
  <div class="container">
    <div class="section-head">
      <div data-reveal>
        <span class="eyebrow">From the Blog</span>
        <h2 class="section-title">Field notes on safety standards</h2>
      </div>
      <a href="<?= e(url_for('blog')) ?>" class="btn btn-outline" data-reveal>All Articles</a>
    </div>
    <div class="grid grid-4" data-render="blog"></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-banner" data-reveal>
      <div class="cta-banner-inner">
        <span class="eyebrow eyebrow--light eyebrow--center">Get Started</span>
        <h2 class="section-title">Ready to safety-proof your site?</h2>
        <p>Tell us your site type and headcount — we'll come back with a scoped safety plan within 48 hours.</p>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Talk to Our Team</a>
      </div>
    </div>
  </div>
</section>
<?php page_foot(); ?>
