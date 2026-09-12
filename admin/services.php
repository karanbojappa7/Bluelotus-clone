<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('action') === 'delete') {
    verify_csrf();
    $id = post('id');
    $stmt = db()->prepare('DELETE FROM services WHERE id = ?');
    $stmt->execute([$id]);
    export_site_config_js();
    flash('ok', 'Service deleted.');
    redirect('services.php');
}

$services = all_services();
admin_header('Services', 'services');
?>
<div class="toolbar">
  <p><?= count($services) ?> services</p>
  <a class="btn" href="service-form.php">Add Service</a>
</div>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Name</th>
        <th>ID</th>
        <th>Summary</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$services): ?>
        <tr><td colspan="4" class="muted">No services yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($services as $s): ?>
        <tr>
          <td><strong><?= e($s['name']) ?></strong></td>
          <td><span class="badge"><?= e($s['id']) ?></span></td>
          <td class="muted"><?= e(truncate($s['summary'], 100)) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="service-form.php?id=<?= e(urlencode($s['id'])) ?>">Edit</a>
            <form method="post" data-confirm="Delete this service? This cannot be undone.">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= e($s['id']) ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
