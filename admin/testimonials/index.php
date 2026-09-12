<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    setting_delete_item('testimonials', (int) post('index'));
    export_site_config_js();
    flash('ok', 'Testimonial deleted.');
    redirect(admin_url('testimonials/index.php'));
}

$all = setting_list('testimonials');
$pager = paginate_items($all, 10);
$list = $pager['items'];
$offset = ($pager['page'] - 1) * $pager['per_page'];

admin_header('Testimonials', 'testimonials');
?>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
  <a class="btn" href="<?= e(admin_url('testimonials/form.php')) ?>">Add Testimonial</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>Name</th><th>Role</th><th>Quote</th><th></th></tr></thead>
    <tbody>
      <?php if (!$list): ?><tr><td colspan="4" class="muted">No testimonials yet.</td></tr><?php endif; ?>
      <?php foreach ($list as $i => $item): ?>
        <?php $realIndex = $offset + $i; ?>
        <tr>
          <td><strong><?= e($item['name'] ?? '') ?></strong></td>
          <td><?= e($item['role'] ?? '') ?></td>
          <td class="muted"><?= e(truncate($item['quote'] ?? '', 100)) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('testimonials/form.php')) ?>?index=<?= $realIndex ?>">Edit</a>
            <form method="post" data-confirm="Delete this testimonial? This cannot be undone.">
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
<?= render_pagination($pager, admin_url('testimonials/index.php')) ?>
<?php admin_footer(); ?>
