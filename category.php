<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

const CATEGORY_PER_PAGE = 12;

$id = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$category = ($id !== '' && db_ready()) ? get_category($id) : null;

if (!$category) {
    render_not_found([
        'title' => 'Category not found',
        'description' => 'That product category is no longer listed. Browse the full ' . brand_name() . ' catalog.',
        'canonical' => 'products/' . rawurlencode($id),
        'heading' => "That category doesn't exist.",
        'body' => 'The link may be out of date. Browse the full catalog instead.',
    ]);
}

$allProducts = all_products($category['id']);
$total = count($allProducts);
$pages = max(1, (int) ceil($total / CATEGORY_PER_PAGE));
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $pages));
$offset = ($page - 1) * CATEGORY_PER_PAGE;
$products = array_slice($allProducts, $offset, CATEGORY_PER_PAGE);

$basePath = category_slug_path($category['id']);
$canonical = $page > 1 ? $basePath . '?page=' . $page : $basePath;
$others = array_values(array_filter(all_categories(), static fn ($c) => $c['id'] !== $category['id']));

$crumbs = [
    'Home' => '/',
    'Products' => 'products',
    $category['name'] => null,
];

$schema = array_values(array_filter([
    schema_item_list($products, $category['name'] . ' products'),
    schema_faq($category['faqs'] ?? []),
]));

$titleBase = $category['metaTitle'] !== '' ? $category['metaTitle'] : $category['name'];
$whatsapp = preg_replace('/[^\d]/', '', (string) (seo_contact()['whatsapp'] ?? ''));

page_head(seo_overrides($category) + [
    'title' => $page > 1 ? $titleBase . ' — Page ' . $page : $titleBase,
    'description' => $category['metaDescription'] !== '' ? $category['metaDescription'] : ($category['intro'] ?: $category['desc']),
    'canonical' => $canonical,
    'breadcrumbs' => $crumbs,
    'schema' => $schema,
    'prev' => $page > 1 ? ($page - 1 > 1 ? $basePath . '?page=' . ($page - 1) : $basePath) : null,
    'next' => $page < $pages ? $basePath . '?page=' . ($page + 1) : null,
]);
?>
<section class="page-header"<?= $category['image'] !== '' ? ' style="--page-header-image:url(\'' . e(url_for($category['image'])) . '\')"' : '' ?>>
  <div class="container">
    <span class="eyebrow eyebrow--light">Product Category</span>
    <h1 class="page-title"><?= e($category['name']) ?></h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container catalog-layout">
    <article class="catalog-copy">
      <h2 class="section-title"><?= e($category['headline'] ?: $category['name']) ?></h2>
      <p class="catalog-lead mt-3"><?= e($category['intro'] ?: $category['desc']) ?></p>
      <?php if ($category['buyers'] !== ''): ?>
      <div class="buyer-panel mt-5">
        <span class="eyebrow">Who We Serve</span>
        <h3 class="buyer-title mt-2">Three buyer types across India</h3>
        <p class="mt-2"><?= e($category['buyers']) ?></p>
      </div>
      <?php endif; ?>
      <?php if ($category['useCases']): ?>
      <div class="mt-5">
        <span class="eyebrow">Solutions by Use Case</span>
        <h3 class="section-title section-title--sm mt-2">Where this equipment performs</h3>
        <div class="use-case-grid mt-4">
          <?php foreach ($category['useCases'] as $i => $use): ?>
            <div class="use-case">
              <span class="feature-num"><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
              <div>
                <h3><?= e((string) ($use['title'] ?? '')) ?></h3>
                <p><?= e((string) ($use['text'] ?? '')) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </article>
    <aside class="catalog-side">
      <div class="side-card side-card--accent">
        <span class="eyebrow">Need Help Choosing?</span>
        <h2 class="mt-2">Free site safety consult</h2>
        <p class="mt-2">Tell us your site type, volume, and timeline &mdash; we'll recommend a product mix.</p>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Talk to Our Team</a>
        <?php if ($whatsapp !== ''): ?>
        <a href="https://wa.me/<?= e($whatsapp) ?>?text=<?= e(rawurlencode('Hello, I would like product details for the ' . $category['name'] . ' category.')) ?>"
           class="btn btn-wa mt-2" target="_blank" rel="noopener">Enquire on WhatsApp</a>
        <?php endif; ?>
      </div>
      <div class="side-card mt-4">
        <span class="eyebrow">Other Categories</span>
        <ul class="side-links mt-3">
          <?php foreach ($others as $other): ?>
            <li><a href="<?= e(category_path($other['id'])) ?>"><?= e($other['name']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </aside>
  </div>
</section>

<section class="section section--dim">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Product Range</span>
        <h2 class="section-title"><?= e($category['name']) ?> products</h2>
      </div>
      <p class="section-sub">
        <?= $total
            ? 'Showing ' . ($offset + 1) . '–' . min($offset + CATEGORY_PER_PAGE, $total) . ' of ' . $total . ' products'
            : '0 products available' ?>
      </p>
    </div>
    <?php if ($products): ?>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <?= render_product_card($product) ?>
        <?php endforeach; ?>
      </div>
      <?= render_public_pagination($page, $pages, $basePath) ?>
    <?php else: ?>
      <?= render_empty_state(
          'Nothing listed here yet.',
          "We stock far more than we list. Tell us what you need and we'll source it.",
          [['label' => 'Ask our team', 'href' => 'contact', 'primary' => true]]
      ) ?>
    <?php endif; ?>
  </div>
</section>

<?php if ($category['faqs']): ?>
<section class="section">
  <div class="container catalog-faq-wrap">
    <div class="section-head">
      <div>
        <span class="eyebrow">Popular Questions</span>
        <h2 class="section-title">Got questions? We've got answers</h2>
      </div>
    </div>
    <div class="faq-list">
      <?php foreach ($category['faqs'] as $faq): ?>
        <details class="faq-item">
          <summary><?= e((string) ($faq['q'] ?? '')) ?></summary>
          <p><?= e((string) ($faq['a'] ?? '')) ?></p>
        </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--dim">
  <div class="container">
    <div class="cta-banner">
      <div class="cta-banner-inner">
        <span class="eyebrow eyebrow--light eyebrow--center">Bulk &amp; Project Supply</span>
        <h2 class="section-title">Ready to equip your site?</h2>
        <p>Get installation support, wholesale pricing, and a scoped recommendation within 48 hours.</p>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Request a Quote</a>
      </div>
    </div>
  </div>
</section>
<?php page_foot(); ?>
