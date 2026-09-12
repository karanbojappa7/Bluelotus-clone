<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    $id = (int) post('id');
    $stmt = db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    export_site_config_js();
    flash('ok', 'Product deleted.');
    $qs = pagination_query(['category' => isset($_GET['category']) ? (string) $_GET['category'] : '']);
    redirect('products.php' . ($qs !== '' ? '?' . $qs : ''));
}

$filter = isset($_GET['category']) ? (string) $_GET['category'] : '';
$all = all_products_admin($filter !== '' ? $filter : null);
$pager = paginate_items($all, 15);
$products = $pager['items'];
$categories = all_categories();
$catNames = [];
foreach ($categories as $cat) {
    $catNames[$cat['id']] = $cat['name'];
}

admin_header('Products', 'products');
?>
<div class="toolbar">
  <form method="get" style="display:flex;gap:0.5rem;align-items:center">
    <select name="category" onchange="this.form.submit()">
      <option value="">All categories</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?= e($cat['id']) ?>" <?= $filter === $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
  <a class="btn" href="product-form.php">Add Product</a>
</div>
<p class="muted" style="margin:0 0 0.75rem">Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>Slug</th>
        <th>Category</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="4" class="muted">No products yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><strong><?= e($p['name']) ?></strong><div class="muted"><?= e(truncate($p['short'])) ?></div></td>
          <td><span class="badge"><?= e($p['slug']) ?></span></td>
          <td><?= e($catNames[$p['category']] ?? $p['category']) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="product-form.php?id=<?= (int) $p['id'] ?>">Edit</a>
            <a class="btn btn-secondary btn-sm" href="../product.html?slug=<?= e(urlencode($p['slug'])) ?>" target="_blank" rel="noopener">View</a>
            <form method="post" onsubmit="return confirm('Delete this product?');">
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
<?= render_pagination($pager, 'products.php') ?>
<?php admin_footer(); ?>
