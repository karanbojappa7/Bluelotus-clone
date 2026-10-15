<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    $list = all_clients();
    $index = (int) post('index');
    if (isset($list[$index])) {
        array_splice($list, $index, 1);
        save_setting('clients', array_values($list));
        export_site_config_js();
    }
    flash('ok', 'Client removed.');
    redirect(admin_url('clients.php'));
}

$all = all_clients();
$pager = paginate_items($all, 12);
$list = $pager['items'];
$offset = ($pager['page'] - 1) * $pager['per_page'];

admin_header('Clients', 'clients');
?>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
  <a class="btn" href="<?= e(admin_url('clients/form.php')) ?>">Add Client</a>
</div>
<p class="muted">These appear in the homepage hero as a moving strip. Add a logo and company name for each one.</p>
<div class="table-wrap">
  <table>
    <thead><tr><th>Logo</th><th>Name</th><th></th></tr></thead>
    <tbody>
      <?php if (!$list): ?><tr><td colspan="3" class="muted">No clients yet.</td></tr><?php endif; ?>
      <?php foreach ($list as $i => $item): ?>
        <?php $realIndex = $offset + $i; ?>
        <tr>
          <td>
            <?php if ($item['logo'] !== ''): ?>
              <img class="table-thumb" src="<?= e(url_for($item['logo'])) ?>" alt="">
            <?php else: ?>
              <span class="muted">No logo</span>
            <?php endif; ?>
          </td>
          <td><strong><?= e($item['name']) ?></strong></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('clients/form.php')) ?>?index=<?= $realIndex ?>">Edit</a>
            <form method="post" data-confirm="Remove this client from the homepage strip?">
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
<?= render_pagination($pager, admin_url('clients.php')) ?>
<?php admin_footer(); ?>
