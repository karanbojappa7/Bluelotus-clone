<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$slug = isset($_GET['slug']) ? trim((string) $_GET['slug']) : '';
$product = ($slug !== '' && db_ready()) ? get_product_by_slug($slug) : null;

if (!$product) {
    render_not_found([
        'title' => 'Product not found',
        'description' => 'The product you are looking for is no longer listed. Browse the full ' . brand_name() . ' catalog.',
        'canonical' => 'product/' . rawurlencode($slug),
        'heading' => "We couldn't find that product.",
        'body' => "The link may be out of date, or the item has been retired. Browse the full catalog or tell us what you need — we source well beyond what's listed.",
    ]);
}

$category = get_category((string) $product['category']);
$categoryName = $category['name'] ?? $product['category'];
$images = $product['images'] ?: ['assets/img/products.jpg'];
$related = array_values(array_filter(all_products((string) $product['category']), static fn ($p) => $p['slug'] !== $product['slug']));
$related = array_slice($related, 0, 4);

$crumbs = [
    'Home' => '/',
    'Products' => 'products',
    $categoryName => category_slug_path((string) $product['category']),
    $product['name'] => null,
];

$contact = seo_contact();
$phones = contact_phones();
$email = $contact['emails'][0]['address'] ?? '';
$whatsapp = preg_replace('/[^\d]/', '', (string) ($contact['whatsapp'] ?? ''));
$productUrl = seo_url(product_slug_path((string) $product['slug']));
$waMessage = 'Hello, I would like to enquire about *' . $product['name'] . "*.\n\n" . $product['short'] . "\n\nProduct link: " . $productUrl;
$waHref = $whatsapp !== '' ? 'https://wa.me/' . $whatsapp . '?text=' . rawurlencode($waMessage) : '';

$availLabels = [
    '' => 'In stock',
    'InStock' => 'In stock',
    'OutOfStock' => 'Out of stock',
    'PreOrder' => 'Pre-order',
    'BackOrder' => 'Back-order',
    'Discontinued' => 'Discontinued',
];
$availKey = $product['availability'] !== '' ? $product['availability'] : 'InStock';
$availLabel = $availLabels[$availKey] ?? 'In stock';
$availOk = !in_array($availKey, ['OutOfStock', 'Discontinued'], true);
$price = trim((string) $product['price']);
$currency = strtoupper(trim((string) $product['currency']) !== '' ? (string) $product['currency'] : 'INR');
$sku = trim((string) $product['sku']);
$brand = trim((string) $product['brand']);
$sprite = url_for('assets/img/sprite.svg');

page_head(seo_overrides($product) + [
    'title' => $product['metaTitle'] !== '' ? $product['metaTitle'] : $product['name'],
    'description' => $product['metaDescription'] !== '' ? $product['metaDescription'] : $product['short'],
    'keywords' => product_keywords($product),
    'canonical' => product_slug_path((string) $product['slug']),
    'image' => $images[0],
    'type' => 'product',
    'breadcrumbs' => $crumbs,
    'schema' => [schema_product($product, $category)],
    'bodyAttr' => 'class="is-product-page"',
]);
?>
<section class="section section--product">
  <div class="container">
    <div class="product-crumbs"><?= render_breadcrumbs($crumbs) ?></div>

    <div class="product-layout mt-5">
      <div class="product-gallery<?= count($images) > 1 ? ' has-thumbs' : '' ?>">
        <div class="product-stage">
          <img id="prodMainImage" src="<?= e(url_for($images[0])) ?>" alt="<?= e($product['name']) ?>" width="900" height="700" data-fallback>
          <?php if (count($images) > 1): ?>
            <span class="product-stage-count" id="prodImageCount">1 / <?= count($images) ?></span>
          <?php endif; ?>
        </div>
        <?php if (count($images) > 1): ?>
        <div class="product-thumbs" id="prodThumbs">
          <?php foreach ($images as $i => $src): ?>
            <button type="button" class="product-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-src="<?= e(url_for($src)) ?>"
                    aria-label="View image <?= $i + 1 ?> of <?= count($images) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
              <img src="<?= e(url_for($src)) ?>" alt="" width="120" height="90" loading="lazy" data-fallback>
            </button>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="product-info">
        <a class="product-cat-chip" href="<?= e(category_path((string) $product['category'])) ?>"><?= e($categoryName) ?></a>
        <h1 class="product-name"><?= e($product['name']) ?></h1>
        <?php if ($product['short'] !== ''): ?>
          <p class="product-lead"><?= e($product['short']) ?></p>
        <?php endif; ?>

        <div class="product-facts">
          <span class="product-avail<?= $availOk ? ' is-ok' : ' is-out' ?>"><?= e($availLabel) ?></span>
          <?php if ($brand !== ''): ?>
            <span class="product-fact">Brand <strong><?= e($brand) ?></strong></span>
          <?php endif; ?>
          <?php if ($sku !== ''): ?>
            <span class="product-fact">SKU <strong><?= e($sku) ?></strong></span>
          <?php endif; ?>
        </div>

        <div class="product-price-row">
          <?php if ($price !== ''): ?>
            <p class="product-price"><span><?= e($currency) ?></span> <?= e($price) ?></p>
          <?php else: ?>
            <p class="product-price product-price--request">Pricing on request</p>
          <?php endif; ?>
        </div>

        <?php if ($product['features']): ?>
        <div class="product-spec">
          <span class="meta-label">Key features</span>
          <ul class="feature-checks">
            <?php foreach ($product['features'] as $feature): ?>
              <li>
                <svg width="16" height="16" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-check"></use></svg>
                <?= e($feature) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <?php if ($product['tags']): ?>
        <div class="tag-row">
          <?php foreach ($product['tags'] as $tag): ?>
            <span class="tag"><?= e($tag) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="product-actions">
          <?php if ($waHref !== ''): ?>
          <a href="<?= e($waHref) ?>" class="btn btn-wa" id="prodWhatsapp" target="_blank" rel="noopener">
            <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-whatsapp"></use></svg>
            Enquire on WhatsApp
          </a>
          <?php endif; ?>
          <a href="<?= e(url_for('contact#quote-form')) ?>" class="btn btn-primary">Request a Quote</a>
        </div>

        <ul class="product-trust">
          <li>
            <span class="product-trust-ico" aria-hidden="true">
              <svg width="16" height="16"><use href="<?= e($sprite) ?>#icon-shield"></use></svg>
            </span>
            Certified equipment
          </li>
          <li>
            <span class="product-trust-ico" aria-hidden="true">
              <svg width="16" height="16"><use href="<?= e($sprite) ?>#icon-truck"></use></svg>
            </span>
            Bulk &amp; project supply
          </li>
          <li>
            <span class="product-trust-ico" aria-hidden="true">
              <svg width="16" height="16"><use href="<?= e($sprite) ?>#icon-clock"></use></svg>
            </span>
            Quote in 48 hours
          </li>
        </ul>

        <div class="product-share">
          <span class="meta-label">Share</span>
          <div class="share-row" id="prodShare" data-share-url="<?= e($productUrl) ?>" data-share-title="<?= e($product['name'] . ' | ' . brand_name()) ?>" data-share-text="<?= e($product['short']) ?>"></div>
        </div>
      </div>
    </div>

    <?php if ($product['description'] !== '' || $phones || $email !== ''): ?>
    <div class="product-below<?= $product['description'] === '' ? ' product-below--solo' : '' ?>">
      <?php if ($product['description'] !== ''): ?>
      <article class="product-panel">
        <span class="eyebrow">Overview</span>
        <h2 class="product-panel-title">Product details</h2>
        <div class="product-body"><?= nl2br(e($product['description'])) ?></div>
      </article>
      <?php endif; ?>
      <aside class="product-quote-card">
        <span class="eyebrow">Need a bulk quote?</span>
        <h2>Tell us the quantity and site type</h2>
        <p>We'll send volume pricing, availability, and a scoped recommendation.</p>
        <?php if ($phones || $email !== ''): ?>
        <div class="product-quote-contact">
          <?php if ($phones): ?>
          <p class="product-quote-row">
            <?= contact_icon('phone') ?>
            <span><?= render_phone_links() ?></span>
          </p>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
          <p class="product-quote-row">
            <?= contact_icon('mail') ?>
            <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
          </p>
          <?php endif; ?>
        </div>
        <?php endif; ?>
        <a href="<?= e(url_for('contact#quote-form')) ?>" class="btn btn-primary mt-4">Request Bulk Quote</a>
      </aside>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($related): ?>
<section class="section section--dim">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Related Products</span>
        <h2 class="section-title">More from <?= e($categoryName) ?></h2>
      </div>
      <a href="<?= e(category_path((string) $product['category'])) ?>" class="btn btn-outline">View Category</a>
    </div>
    <div class="product-grid">
      <?php foreach ($related as $item): ?>
        <?= render_product_card($item) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<div class="product-sticky-cta" aria-label="Product actions">
  <?php if ($waHref !== ''): ?>
  <a href="<?= e($waHref) ?>" class="btn btn-wa" target="_blank" rel="noopener">WhatsApp</a>
  <?php endif; ?>
  <a href="<?= e(url_for('contact#quote-form')) ?>" class="btn btn-primary">Request Quote</a>
</div>
<?php page_foot(['scripts' => ['product.js']]); ?>
