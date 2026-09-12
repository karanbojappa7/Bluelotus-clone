<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();
migrate();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    $id = (int) post('id');
    db()->prepare('DELETE FROM leadership WHERE id = ?')->execute([$id]);
    export_site_config_js();
    flash('ok', 'Leadership entry deleted.');
    redirect('leadership.php');
}

$all = all_leadership();
$pager = paginate_items($all, 10);
$list = $pager['items'];

admin_header('Leadership', 'leadership');
?>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
  <a class="btn" href="leadership-form.php">Add Entry</a>
</div>
<div class="table-wrap">
  <table>
    <thead><tr><th>Photo</th><th>Name</th><th>Designation</th><th>Expertise</th><th></th></tr></thead>
    <tbody>
      <?php if (!$list): ?><tr><td colspan="5" class="muted">No leadership entries yet.</td></tr><?php endif; ?>
      <?php foreach ($list as $item): ?>
        <tr>
          <td>
            <?php if (!empty($item['image'])): ?>
              <img class="table-thumb" src="../<?= e($item['image']) ?>" alt="">
            <?php else: ?>
              <span class="muted">No photo</span>
            <?php endif; ?>
          </td>
          <td><strong><?= e($item['name']) ?></strong></td>
          <td><?= e($item['designation']) ?></td>
          <td class="muted"><?= e(truncate($item['expertise'], 80)) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="leadership-form.php?id=<?= (int) $item['id'] ?>">Edit</a>
            <form method="post" onsubmit="return confirm('Delete this entry?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= render_pagination($pager, 'leadership.php') ?>
<?php admin_footer(); ?>
