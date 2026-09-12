<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $list = setting_list('hero') ?: default_hero_slides();
    $index = (int) post('index');
    $action = post('action');
    if ($action === 'delete' && isset($list[$index])) {
        array_splice($list, $index, 1);
        save_setting('hero', array_values($list));
        export_site_config_js();
        flash('ok', 'Banner slide removed.');
    } elseif (($action === 'move-up' || $action === 'move-down') && isset($list[$index])) {
        save_setting('hero', array_values($list));
        setting_move_item('hero', $index, $action === 'move-up' ? -1 : 1);
        export_site_config_js();
        flash('ok', 'Slide order updated.');
    }
    redirect(admin_url('hero/index.php'));
}

$slides = setting_list('hero') ?: default_hero_slides();
admin_header('Homepage Banner', 'hero');
?>
<div class="toolbar">
  <p><?= count($slides) ?> slides on the homepage carousel. Upload a photo and copy for each slide.</p>
  <a class="btn" href="<?= e(admin_url('hero/form.php')) ?>">Add Slide</a>
</div>
<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th></th>
        <th>Nav label</th>
        <th>Title</th>
        <th>Button</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$slides): ?>
        <tr><td colspan="5" class="muted">No banner slides yet.</td></tr>
      <?php endif; ?>
      <?php foreach ($slides as $i => $slide): ?>
        <tr>
          <td>
            <?php if (!empty($slide['image'])): ?>
              <img src="<?= e(url_for((string) $slide['image'])) ?>" alt="" width="72" height="44" style="object-fit:cover;border-radius:4px;display:block">
            <?php endif; ?>
          </td>
          <td><span class="badge"><?= e($slide['navLabel'] ?: ($slide['eyebrow'] ?? '')) ?></span></td>
          <td>
            <strong><?= e($slide['title'] ?? '') ?></strong>
            <div class="muted"><?= e($slide['eyebrow'] ?? '') ?></div>
          </td>
          <td class="muted"><?= e($slide['buttonLabel'] ?? '') ?></td>
          <td class="actions">
            <form method="post" class="row-order">
              <?= csrf_field() ?>
              <input type="hidden" name="index" value="<?= $i ?>">
              <button type="submit" name="action" value="move-up" aria-label="Move up" <?= $i === 0 ? 'disabled' : '' ?>>&#9650;</button>
              <button type="submit" name="action" value="move-down" aria-label="Move down" <?= $i === count($slides) - 1 ? 'disabled' : '' ?>>&#9660;</button>
            </form>
            <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('hero/form.php')) ?>?index=<?= $i ?>">Edit</a>
            <form method="post" data-confirm="Remove this banner slide?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="index" value="<?= $i ?>">
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php admin_footer(); ?>
