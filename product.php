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
$phone = $contact['phones'][0]['number'] ?? '';
$email = $contact['emails'][0]['address'] ?? '';
$whatsapp = preg_replace('/[^\d]/', '', (string) ($contact['whatsapp'] ?? ''));
$productUrl = seo_url(product_slug_path((string) $product['slug']));
$waMessage = 'Hello, I would like to enquire about *' . $product['name'] . "*.\n\n" . $product['short'] . "\n\nProduct link: " . $productUrl;

page_head(seo_overrides($product) + [
    'title' => $product['metaTitle'] !== '' ? $product['metaTitle'] : $product['name'],
    'description' => $product['metaDescription'] !== '' ? $product['metaDescription'] : $product['short'],
    'keywords' => product_keywords($product),
    'canonical' => product_slug_path((string) $product['slug']),
    'image' => $images[0],
    'type' => 'product',
    'breadcrumbs' => $crumbs,
    'schema' => [schema_product($product, $category)],
]);
?>
<section class="page-header page-header--slim">
  <div class="container">
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section section--product">
  <div class="container product-layout">
    <div class="product-gallery">
      <div class="product-stage">
        <img id="prodMainImage" src="<?= e(url_for($images[0])) ?>" alt="<?= e($product['name']) ?>" width="900" height="700" data-fallback>
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
      <span class="eyebrow"><?= e($categoryName) ?></span>
      <h1 class="product-name"><?= e($product['name']) ?></h1>
      <p class="product-lead"><?= e($product['short']) ?></p>
      <?php if ($product['description'] !== ''): ?>
        <p class="product-body"><?= nl2br(e($product['description'])) ?></p>
      <?php endif; ?>
      <div class="product-meta">
        <div>
          <span class="meta-label">Category</span>
          <a class="meta-pill" href="<?= e(category_path((string) $product['category'])) ?>"><?= e($categoryName) ?></a>
        </div>
        <?php if ($product['tags']): ?>
        <div>
          <span class="meta-label">Tags</span>
          <div class="tag-row">
            <?php foreach ($product['tags'] as $tag): ?>
              <span class="tag"><?= e($tag) ?></span>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($product['features']): ?>
      <div class="product-spec">
        <span class="meta-label">Key Features</span>
        <ul class="feature-checks">
          <?php foreach ($product['features'] as $feature): ?>
            <li><?= e($feature) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
      <div class="product-actions">
        <?php if ($whatsapp !== ''): ?>
        <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= e(rawurlencode($waMessage)) ?>" class="btn btn-wa" id="prodWhatsapp" target="_blank" rel="noopener">
          <svg width="18" height="18" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-whatsapp"></use></svg> Enquire on WhatsApp
        </a>
        <?php endif; ?>
        <a href="<?= e(category_path((string) $product['category'])) ?>" class="btn btn-outline">Back to <?= e($categoryName) ?></a>
      </div>
      <div class="product-share">
        <span class="meta-label">Share this product</span>
        <div class="share-row" id="prodShare" data-share-url="<?= e($productUrl) ?>" data-share-title="<?= e($product['name'] . ' | ' . brand_name()) ?>" data-share-text="<?= e($product['short']) ?>"></div>
      </div>
      <div class="product-help">
        <div>
          <strong>Need a bulk quote?</strong>
          <span>
            Call <a href="tel:<?= e(preg_replace('/[^\d+]/', '', (string) $phone)) ?>"><?= e($phone) ?></a>
            or email <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a>
          </span>
        </div>
        <a href="<?= e(url_for('contact#quote-form')) ?>" class="btn btn-primary btn-sm">Request Bulk Quote</a>
      </div>
    </div>
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
<?php page_foot(['scripts' => ['product.js']]); ?>
