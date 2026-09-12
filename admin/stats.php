<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$stats = setting_list('stats');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (post('action') === 'delete') {
        setting_delete_item('stats', (int) post('index'));
        export_site_config_js();
        flash('ok', 'Stat removed.');
        redirect('stats.php');
    }

    $next = [];
    $values = $_POST['value'] ?? [];
    $suffixes = $_POST['suffix'] ?? [];
    $labels = $_POST['label'] ?? [];
    if (is_array($values)) {
        foreach ($values as $i => $value) {
            $value = trim((string) $value);
            $label = trim((string) ($labels[$i] ?? ''));
            if ($value === '' && $label === '') {
                continue;
            }
            $next[] = [
                'value' => $value,
                'suffix' => trim((string) ($suffixes[$i] ?? '')),
                'label' => $label,
            ];
        }
    }
    save_setting('stats', $next);
    export_site_config_js();
    flash('ok', 'Stats saved.');
    redirect('stats.php');
}

if (!$stats) {
    $stats = [['value' => '', 'suffix' => '', 'label' => '']];
}

admin_header('Stats Counters', 'stats');
?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <p class="muted">Shown on the homepage hero strip and About page. Use numbers only in Value (e.g. 13) and put + or /7 in Suffix.</p>
  <?php foreach ($stats as $i => $stat): ?>
    <div class="form-grid" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
      <label>Value
        <input type="text" name="value[]" value="<?= e($stat['value'] ?? '') ?>">
      </label>
      <label>Suffix
        <input type="text" name="suffix[]" value="<?= e($stat['suffix'] ?? '') ?>">
      </label>
      <label class="full">Label
        <input type="text" name="label[]" value="<?= e($stat['label'] ?? '') ?>">
      </label>
    </div>
  <?php endforeach; ?>
  <div class="form-grid" style="margin-bottom:1rem">
    <label>Value
      <input type="text" name="value[]" placeholder="New value">
    </label>
    <label>Suffix
      <input type="text" name="suffix[]" placeholder="+">
    </label>
    <label class="full">Label
      <input type="text" name="label[]" placeholder="New label">
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Stats</button>
  </div>
</form>

<div class="table-wrap" style="margin-top:1.5rem">
  <table>
    <thead><tr><th>#</th><th>Preview</th><th></th></tr></thead>
    <tbody>
      <?php foreach (setting_list('stats') as $i => $stat): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= e(($stat['value'] ?? '') . ($stat['suffix'] ?? '')) ?></strong> — <?= e($stat['label'] ?? '') ?></td>
          <td>
            <form method="post" onsubmit="return confirm('Remove this stat?');">
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
