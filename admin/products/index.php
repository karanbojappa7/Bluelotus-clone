<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$filter = isset($_GET['category']) ? (string) $_GET['category'] : '';
$search = isset($_GET['q']) ? trim((string) $_GET['q']) : '';

function products_url(string $filter, string $search): string
{
    $query = array_filter(['category' => $filter, 'q' => $search], static fn ($v) => $v !== '');
    return admin_url('products/index.php') . ($query ? '?' . http_build_query($query) : '');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = post('action');
    $id = (int) post('id');

    if ($action === 'delete') {
        $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        export_site_config_js();
        flash('ok', 'Product deleted.');
    } elseif ($action === 'move-up' || $action === 'move-down') {
        if (move_product($id, $action === 'move-up' ? -1 : 1)) {
            export_site_config_js();
            flash('ok', 'Product order updated.');
        }
    }
    redirect(products_url($filter, $search));
}

$all = all_products_admin($filter !== '' ? $filter : null, $search !== '' ? $search : null);
$pager = paginate_items($all, 15);
$products = $pager['items'];
$categories = all_categories();
$catNames = [];
foreach ($categories as $cat) {
    $catNames[$cat['id']] = $cat['name'];
}
$isFiltered = $filter !== '' || $search !== '';
$totalProducts = (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn();

admin_header('Products', 'products');
?>
<div class="toolbar">
  <form method="get" class="toolbar-search" role="search">
    <span class="search-field">
      <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search name, slug, tags…" aria-label="Search products">
      <?php if ($search !== ''): ?>
        <a class="search-clear" href="<?= e(products_url($filter, '')) ?>" aria-label="Clear search">&times;</a>
      <?php endif; ?>
    </span>
    <select name="category" aria-label="Filter by category" data-autosubmit>
      <option value="">All categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat['id']) ?>" <?= $filter === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-secondary" type="submit">Search</button>
  </form>
  <a class="btn" href="<?= e(admin_url('products/form.php')) ?>">Add Product</a>
</div>

<?php if (!$products): ?>
  <div class="empty-state">
    <?php if ($isFiltered): ?>
      <strong>No products match your search.</strong>
      <span>Try a different term, or clear the filters to see all <?= $totalProducts ?> products.</span>
      <a class="btn btn-secondary" href="<?= e(admin_url('products/index.php')) ?>">Clear filters</a>
    <?php else: ?>
      <strong>No products yet.</strong>
      <span>Add your first product to publish it on the site.</span>
      <a class="btn" href="<?= e(admin_url('products/form.php')) ?>">Add Product</a>
    <?php endif; ?>
  </div>
<?php else: ?>
  <p class="muted" style="margin:0 0 0.75rem">
    Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?><?= $isFiltered ? ' matching (' . $totalProducts . ' total)' : '' ?>
  </p>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Order</th>
          <th>Product</th>
          <th>Slug</th>
          <th>Category</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $i => $p): ?>
          <?php
          $images = json_list($p['images'] ?? '[]');
          $thumb = (string) ($images[0] ?? '');
          $isFirst = $pager['page'] === 1 && $i === 0;
          $isLast = $pager['page'] === $pager['pages'] && $i === count($products) - 1;
          ?>
          <tr>
            <td data-label="Order">
              <?php if (!$isFiltered): ?>
                <form method="post" class="row-order">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                  <button type="submit" name="action" value="move-up" aria-label="Move <?= e($p['name']) ?> up" <?= $isFirst ? 'disabled' : '' ?>>&#9650;</button>
                  <button type="submit" name="action" value="move-down" aria-label="Move <?= e($p['name']) ?> down" <?= $isLast ? 'disabled' : '' ?>>&#9660;</button>
                </form>
              <?php else: ?>
                <span class="muted" title="Clear filters to reorder">&mdash;</span>
              <?php endif; ?>
            </td>
            <td data-label="Product">
              <?php if ($thumb !== ''): ?>
                <img class="table-thumb" src="<?= e(url_for($thumb)) ?>" alt="" loading="lazy">
              <?php endif; ?>
              <strong><?= e($p['name']) ?></strong>
              <div class="muted"><?= e(truncate($p['short'])) ?></div>
            </td>
            <td data-label="Slug"><span class="badge"><?= e($p['slug']) ?></span></td>
            <td data-label="Category"><?= e($catNames[$p['category']] ?? $p['category']) ?></td>
            <td class="actions" data-label="Actions">
              <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('products/form.php')) ?>?id=<?= (int) $p['id'] ?>">Edit</a>
              <a class="btn btn-secondary btn-sm" href="<?= e(url_for(product_slug_path((string) $p['slug']))) ?>" target="_blank" rel="noopener">View</a>
              <form method="post" data-confirm="Delete &quot;<?= e($p['name']) ?>&quot;? This cannot be undone.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?= render_pagination($pager, admin_url('products/index.php')) ?>
<?php endif; ?>
<?php admin_footer(); ?>
