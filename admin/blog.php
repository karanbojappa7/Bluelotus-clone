<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    setting_delete_item('blog', (int) post('index'));
    export_site_config_js();
    flash('ok', 'Blog post deleted.');
    redirect('blog.php');
}

$all = setting_list('blog');
$pager = paginate_items($all, 10);
$list = $pager['items'];
$offset = ($pager['page'] - 1) * $pager['per_page'];

admin_header('Blog Posts', 'blog');
?>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
  <a class="btn" href="blog-form.php">Add Post</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>Title</th><th>Slug</th><th>Date</th><th></th></tr></thead>
    <tbody>
      <?php if (!$list): ?><tr><td colspan="4" class="muted">No blog posts yet.</td></tr><?php endif; ?>
      <?php foreach ($list as $i => $item): ?>
        <?php $realIndex = $offset + $i; ?>
        <tr>
          <td><strong><?= e($item['title'] ?? '') ?></strong><div class="muted"><?= e(truncate($item['excerpt'] ?? '', 80)) ?></div></td>
          <td><span class="badge"><?= e($item['slug'] ?? '') ?></span></td>
          <td><?= e($item['date'] ?? '') ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="blog-form.php?index=<?= $realIndex ?>">Edit</a>
            <a class="btn btn-secondary btn-sm" href="../blog-single.html?post=<?= e(urlencode($item['slug'] ?? '')) ?>" target="_blank" rel="noopener">View</a>
            <form method="post" onsubmit="return confirm('Delete this post?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="index" value="<?= $realIndex ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= render_pagination($pager, 'blog.php') ?>
<?php admin_footer(); ?>
