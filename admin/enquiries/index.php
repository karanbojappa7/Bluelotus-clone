<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();
migrate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) post('id');
    if (post('action') === 'delete' && $id > 0) {
        delete_enquiry($id);
        flash('ok', 'Enquiry deleted.');
    } elseif (post('action') === 'read' && $id > 0) {
        mark_enquiry_read($id);
        flash('ok', 'Marked as read.');
    }
    redirect(admin_url('enquiries/index.php'));
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$selected = $id > 0 ? get_enquiry($id) : null;
if ($selected && (int) $selected['is_read'] === 0) {
    mark_enquiry_read($id);
    $selected['is_read'] = 1;
}

$enquiries = all_enquiries();
$pager = paginate_items($enquiries, 20);
$enquiries = $pager['items'];
$unread = enquiry_unread_count();

admin_header('Enquiries', 'enquiries');
?>
<div class="toolbar">
  <p><?= (int) $pager['total'] ?> messages<?= $unread ? ' · <strong>' . (int) $unread . ' unread</strong>' : '' ?></p>
</div>

<?php if ($selected): ?>
<div class="card" style="margin-bottom:1rem">
  <strong style="font-size:1rem"><?= e($selected['name']) ?></strong>
  <p class="muted" style="margin:0.35rem 0 0.8rem">
    <?= e((string) $selected['created_at']) ?>
    <?= $selected['category'] !== '' ? ' · ' . e($selected['category']) : '' ?>
  </p>
  <p><strong>Email</strong> · <a href="mailto:<?= e($selected['email']) ?>"><?= e($selected['email']) ?></a></p>
  <p><strong>Phone</strong> · <a href="tel:<?= e(preg_replace('/[^\d+]/', '', $selected['phone']) ?? '') ?>"><?= e($selected['phone']) ?></a></p>
  <?php if ($selected['company'] !== ''): ?>
    <p><strong>Company</strong> · <?= e($selected['company']) ?></p>
  <?php endif; ?>
  <p style="white-space:pre-wrap;margin-top:0.8rem"><?= e($selected['message']) ?></p>
  <div class="actions" style="margin-top:1rem">
    <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('enquiries/index.php')) ?>">Back to list</a>
    <form method="post" data-confirm="Delete this enquiry?">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" value="<?= (int) $selected['id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Delete</button>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>From</th>
        <th>Contact</th>
        <th>Interest</th>
        <th>Received</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$enquiries): ?>
        <tr><td colspan="5" class="muted">No enquiries yet. Messages from the public contact form will appear here.</td></tr>
      <?php endif; ?>
      <?php foreach ($enquiries as $row): ?>
        <tr class="<?= (int) $row['is_read'] === 0 ? 'is-unread' : '' ?>">
          <td>
            <strong><?= e($row['name']) ?></strong>
            <?php if ((int) $row['is_read'] === 0): ?><span class="badge">New</span><?php endif; ?>
            <div class="muted"><?= e(truncate($row['message'], 90)) ?></div>
          </td>
          <td>
            <div><?= e($row['email']) ?></div>
            <div class="muted"><?= e($row['phone']) ?></div>
          </td>
          <td><?= e($row['category'] !== '' ? $row['category'] : '—') ?></td>
          <td class="muted"><?= e((string) $row['created_at']) ?></td>
          <td class="actions">
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('enquiries/index.php')) ?>?id=<?= (int) $row['id'] ?>">View</a>
            <?php if ((int) $row['is_read'] === 0): ?>
              <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="read">
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-secondary btn-sm" type="submit">Mark read</button>
              </form>
            <?php endif; ?>
            <form method="post" data-confirm="Delete this enquiry?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?= render_pagination($pager, admin_url('enquiries/index.php')) ?>
<?php admin_footer(); ?>
