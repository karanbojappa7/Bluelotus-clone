<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

const CATALOG_PER_PAGE = 24;

$ready = db_ready();
$categories = $ready ? all_categories() : [];
$catFilter = isset($_GET['cat']) ? trim((string) $_GET['cat']) : '';
$query = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

$activeCategory = null;
foreach ($categories as $candidate) {
    if ($candidate['id'] === $catFilter) {
        $activeCategory = $candidate;
        break;
    }
}
if ($catFilter !== '' && !$activeCategory) {
    $catFilter = '';
}

$all = $ready ? all_products($catFilter !== '' ? $catFilter : null) : [];

if ($query !== '') {
    $terms = preg_split('/\s+/', mb_strtolower($query)) ?: [];
    $all = array_values(array_filter($all, static function (array $p) use ($terms): bool {
        $haystack = mb_strtolower(implode(' ', [
            $p['name'],
            $p['short'],
            $p['description'],
            implode(' ', $p['tags'] ?: []),
            implode(' ', $p['features'] ?: []),
        ]));
        foreach ($terms as $term) {
            if ($term !== '' && !str_contains($haystack, $term)) {
                return false;
            }
        }
        return true;
    }));
}

$total = count($all);
$pages = max(1, (int) ceil($total / CATALOG_PER_PAGE));
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $pages));
$offset = ($page - 1) * CATALOG_PER_PAGE;
$products = array_slice($all, $offset, CATALOG_PER_PAGE);

$extra = array_filter(['cat' => $catFilter, 'q' => $query], static fn ($v) => $v !== '');
$canonical = 'products';
if ($catFilter !== '') {
    $canonical = category_slug_path($catFilter);
} elseif ($page > 1) {
    $canonical = 'products?page=' . $page;
}

$isSearch = $query !== '';
$title = 'Products';
$description = 'Browse certified safety equipment across every category from ' . brand_name() . ' — fire, road, industrial, warehouse, and construction safety.';
if ($isSearch) {
    $title = 'Search results for “' . $query . '”';
    $description = $total . ' products matching “' . $query . '” in the ' . brand_name() . ' safety equipment catalog.';
}

$crumbs = ['Home' => '/', 'Products' => null];
$productsCopy = page_copy('products');

page_head([
    'title' => $page > 1 && !$isSearch ? $title . ' — Page ' . $page : $title,
    'description' => $description,
    'canonical' => $canonical,
    'robots' => $isSearch ? 'noindex, follow' : 'index, follow, max-image-preview:large',
    'breadcrumbs' => $crumbs,
    'schema' => array_values(array_filter([schema_item_list($products, 'Safety equipment catalog')])),
    'prev' => $page > 1 ? ($page - 1 > 1 ? 'products?' . http_build_query($extra + ['page' => $page - 1]) : 'products') : null,
    'next' => $page < $pages ? 'products?' . http_build_query($extra + ['page' => $page + 1]) : null,
]);
render_page_banner($productsCopy, $crumbs, 'photo', $isSearch ? 'Search: ' . $query : null);
?>
<section class="section section--catalog">
  <div class="container">
    <div class="catalog-intro">
      <div>
        <?php if (!$isSearch): ?>
          <p class="catalog-intro-lead"><?= e(page_text($productsCopy, 'intro')) ?></p>
        <?php endif; ?>
      </div>
      <form class="catalog-search" id="productSearchForm" role="search" method="get" action="<?= e(url_for('products')) ?>">
        <svg class="catalog-search-icon" width="18" height="18" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-search"></use></svg>
        <input type="search" id="productSearchInput" name="q" value="<?= e($query) ?>" autocomplete="off"
               placeholder="Search products, tags, or categories…" aria-label="Search products">
        <?php if ($catFilter !== ''): ?>
          <input type="hidden" name="cat" value="<?= e($catFilter) ?>">
        <?php endif; ?>
        <button type="button" class="catalog-search-clear" id="productSearchClear" aria-label="Clear search">&times;</button>
      </form>
    </div>

    <div class="catalog-toolbar">
      <div class="catalog-filters" id="productFilters">
        <a class="filter-chip<?= $catFilter === '' ? ' is-active' : '' ?>" href="<?= e(url_for('products') . ($query !== '' ? '?q=' . rawurlencode($query) : '')) ?>">All products</a>
        <?php foreach ($categories as $cat): ?>
          <a class="filter-chip<?= $catFilter === $cat['id'] ? ' is-active' : '' ?>"
             href="<?= e(category_path($cat['id']) . ($query !== '' ? '?q=' . rawurlencode($query) : '')) ?>"><?= e($cat['name']) ?></a>
        <?php endforeach; ?>
      </div>
      <p class="catalog-count" id="productCount">
        <?php if (!$total): ?>
          <?= $isSearch ? 'No results for “' . e($query) . '”.' : 'No products in this category yet.' ?>
        <?php else: ?>
          Showing <?= $offset + 1 ?>–<?= min($offset + CATALOG_PER_PAGE, $total) ?> of <?= $total ?> product<?= $total === 1 ? '' : 's' ?><?= $isSearch ? ' matching “' . e($query) . '”' : '' ?>
        <?php endif; ?>
      </p>
    </div>

    <?php if ($products): ?>
      <div class="product-grid" id="productCatalog">
        <?php foreach ($products as $product): ?>
          <?= render_product_card($product) ?>
        <?php endforeach; ?>
      </div>
      <?= render_public_pagination($page, $pages, 'products', $extra) ?>
    <?php else: ?>
      <?= render_empty_state(
          $isSearch ? 'No products match “' . $query . '”.' : 'No products match this category.',
          "Try a broader term, or browse the full catalog. We stock far more than we list — tell us what you need.",
          [
              ['label' => 'View all products', 'href' => 'products'],
              ['label' => 'Ask our team', 'href' => 'contact', 'primary' => true],
          ]
      ) ?>
    <?php endif; ?>
  </div>
</section>

<?php if (!$isSearch && $categories): ?>
<section class="section section--dim">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Other Categories</span>
        <h2 class="section-title">Explore more safety ranges</h2>
      </div>
    </div>
    <div class="grid grid-3 other-cat-grid">
      <?php foreach ($categories as $cat): ?>
        <?= render_category_tile($cat) ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
$bulkImage = trim(page_text($productsCopy, 'bulkImage'));
if ($bulkImage === '') {
    $bulkImage = 'assets/img/products.jpg';
}
$bulkPoints = array_values(array_filter([
    page_text($productsCopy, 'bulkPoint1'),
    page_text($productsCopy, 'bulkPoint2'),
    page_text($productsCopy, 'bulkPoint3'),
], static fn (string $line): bool => $line !== ''));
$bulkPrimary = page_text($productsCopy, 'bulkButton');
$bulkPrimaryUrl = trim(page_text($productsCopy, 'bulkButtonUrl'));
$bulkSecondary = page_text($productsCopy, 'bulkButton2');
$bulkSecondaryUrl = trim(page_text($productsCopy, 'bulkButton2Url'));
?>
<section class="section section--flush">
  <div class="bulk-quote">
    <div class="bulk-quote-media">
      <img src="<?= e(url_for($bulkImage)) ?>" alt="" width="1100" height="720" loading="lazy">
      <div class="bulk-quote-media-shade"></div>
      <?php if (page_text($productsCopy, 'bulkMark') !== ''): ?>
        <span class="bulk-quote-mark"><?= e(page_text($productsCopy, 'bulkMark')) ?></span>
      <?php endif; ?>
    </div>
    <div class="bulk-quote-copy">
      <?php if (page_text($productsCopy, 'bulkEyebrow') !== ''): ?>
        <span class="eyebrow eyebrow--light"><?= e(page_text($productsCopy, 'bulkEyebrow')) ?></span>
      <?php endif; ?>
      <h2 class="section-title"><?= e(page_text($productsCopy, 'bulkTitle')) ?></h2>
      <?php if (page_text($productsCopy, 'bulkLead') !== ''): ?>
        <p class="bulk-quote-lead"><?= e(page_text($productsCopy, 'bulkLead')) ?></p>
      <?php endif; ?>
      <?php if ($bulkPoints): ?>
        <ul class="bulk-quote-points">
          <?php foreach ($bulkPoints as $point): ?>
            <li><?= e($point) ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <?php if (($bulkPrimary !== '' && $bulkPrimaryUrl !== '') || ($bulkSecondary !== '' && $bulkSecondaryUrl !== '')): ?>
        <div class="bulk-quote-actions">
          <?php if ($bulkPrimary !== '' && $bulkPrimaryUrl !== ''): ?>
            <a href="<?= e(url_for(ltrim($bulkPrimaryUrl, '/'))) ?>" class="btn btn-primary"><?= e($bulkPrimary) ?>
              <svg width="16" height="16" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-arrow"></use></svg>
            </a>
          <?php endif; ?>
          <?php if ($bulkSecondary !== '' && $bulkSecondaryUrl !== ''): ?>
            <a href="<?= e(url_for(ltrim($bulkSecondaryUrl, '/'))) ?>" class="btn btn-ghost"><?= e($bulkSecondary) ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php page_foot(); ?>
