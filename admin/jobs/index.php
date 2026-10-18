<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();
ensure_jobs_setting();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $list = setting_list('jobs');
    $index = (int) post('index');
    $action = post('action');
    if ($action === 'delete' && isset($list[$index])) {
        array_splice($list, $index, 1);
        save_setting('jobs', array_values($list));
        export_site_config_js();
        flash('ok', 'Role removed.');
    } elseif (($action === 'move-up' || $action === 'move-down') && isset($list[$index])) {
        setting_move_item('jobs', $index, $action === 'move-up' ? -1 : 1);
        export_site_config_js();
        flash('ok', 'Role order updated.');
    }
    redirect(admin_url('jobs/index.php'));
}

$all = all_jobs();
$pager = paginate_items($all, 10);
$list = $pager['items'];
$offset = ($pager['page'] - 1) * $pager['per_page'];

admin_header('Careers', 'jobs');
?>
<p class="muted">Open roles appear on the <a href="<?= e(url_for('careers')) ?>" target="_blank" rel="noopener">careers</a> page. People open a role and apply from there. The page title and banner are edited under <a href="<?= e(admin_url('pages/edit.php?page=careers')) ?>">Pages → Careers</a>.</p>
<div class="toolbar">
  <p>Showing <?= (int) $pager['from'] ?>–<?= (int) $pager['to'] ?> of <?= (int) $pager['total'] ?></p>
  <a class="btn" href="<?= e(admin_url('jobs/form.php')) ?>">Add Role</a>
</div>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Role</th>
        <th>Team</th>
        <th>Location</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$list): ?>
        <tr><td colspan="5" class="muted">No roles yet. Add an opening to list it on the careers page.</td></tr>
      <?php endif; ?>
      <?php foreach ($list as $i => $item): ?>
        <?php $realIndex = $offset + $i; ?>
        <tr>
          <td>
            <strong><?= e($item['title']) ?></strong>
            <div class="muted"><?= e($item['type'] !== '' ? $item['type'] : 'Full-time') ?></div>
          </td>
          <td><?= e($item['department'] !== '' ? $item['department'] : '—') ?></td>
          <td class="muted"><?= e($item['location'] !== '' ? $item['location'] : '—') ?></td>
          <td><?= $item['open'] ? '<span class="badge">' . e(job_openings_label((int) ($item['openings'] ?? 1))) . '</span>' : '<span class="badge">Hidden</span>' ?></td>
          <td class="actions">
            <form method="post" class="row-order">
              <?= csrf_field() ?>
              <input type="hidden" name="index" value="<?= $realIndex ?>">
              <button type="submit" name="action" value="move-up" aria-label="Move up" <?= $realIndex === 0 ? 'disabled' : '' ?>>&#9650;</button>
              <button type="submit" name="action" value="move-down" aria-label="Move down" <?= $realIndex === count($all) - 1 ? 'disabled' : '' ?>>&#9660;</button>
            </form>
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('jobs/form.php')) ?>?index=<?= $realIndex ?>">Edit</a>
            <form method="post" data-confirm="Remove this role? This cannot be undone.">
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
<?= render_pagination($pager, admin_url('jobs/index.php')) ?>
<?php admin_footer(); ?>
