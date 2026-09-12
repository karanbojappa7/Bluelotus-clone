<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    $id = post('id');
    $count = db()->prepare('SELECT COUNT(*) FROM products WHERE category = ?');
    $count->execute([$id]);
    if ((int) $count->fetchColumn() > 0) {
        flash('error', 'Cannot delete category while products still use it.');
        redirect(admin_url('categories/index.php'));
    }
    $stmt = db()->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    export_site_config_js();
    flash('ok', 'Category deleted.');
    redirect(admin_url('categories/index.php'));
}

$categories = all_categories();
$pager = paginate_items($categories, 10);
$categories = $pager['items'];
admin_header('Categories', 'categories');
?>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?> (needed for products)</p>
  <a class="btn" href="<?= e(admin_url('categories/form.php')) ?>">Add Category</a>
</div>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th></th>
        <th>Name</th>
        <th>ID</th>
        <th>Description</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td>
            <?php if ($cat['image'] !== ''): ?>
              <img src="<?= e(url_for($cat['image'])) ?>" alt="" width="48" height="36" style="object-fit:cover;border-radius:4px;display:block">
            <?php endif; ?>
          </td>
          <td><strong><?= e($cat['name']) ?></strong></td>
          <td><span class="badge"><?= e($cat['id']) ?></span></td>
          <td class="muted"><?= e(truncate($cat['desc'])) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('categories/form.php')) ?>?id=<?= e(urlencode($cat['id'])) ?>">Edit</a>
            <a class="btn btn-secondary btn-sm" href="<?= e(url_for(category_slug_path($cat['id']))) ?>" target="_blank" rel="noopener">View</a>
            <form method="post" data-confirm="Delete this category? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= e($cat['id']) ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= render_pagination($pager, admin_url('categories/index.php')) ?>
<?php admin_footer(); ?>
