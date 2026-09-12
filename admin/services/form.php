<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$id = isset($_GET['id']) ? (string) $_GET['id'] : '';
$existing = $id !== '' ? get_service($id) : null;
$errors = [];

$service = [
    'id' => $existing['id'] ?? '',
    'name' => $existing['name'] ?? '',
    'icon' => $existing['icon'] ?? '',
    'summary' => $existing['summary'] ?? '',
    'points' => array_to_lines($existing['points'] ?? []),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $service['name'] = post('name');
    $service['id'] = post('id') !== '' ? slugify(post('id')) : slugify(post('name'));
    $service['icon'] = post('icon');
    $service['summary'] = post('summary');
    $service['points'] = post('points');

    if ($service['name'] === '' || $service['id'] === '') {
        $errors[] = 'Name and ID are required.';
    }

    if (!$errors) {
        $points = json_encode(lines_to_array($service['points']), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        try {
            if ($existing) {
                if ($service['id'] !== $existing['id']) {
                    $stmt = db()->prepare('UPDATE services SET id=?, name=?, icon=?, summary=?, points=? WHERE id=?');
                    $stmt->execute([$service['id'], $service['name'], $service['icon'], $service['summary'], $points, $existing['id']]);
                } else {
                    $stmt = db()->prepare('UPDATE services SET name=?, icon=?, summary=?, points=? WHERE id=?');
                    $stmt->execute([$service['name'], $service['icon'], $service['summary'], $points, $existing['id']]);
                }
                flash('ok', 'Service updated.');
            } else {
                $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM services')->fetchColumn();
                $stmt = db()->prepare('INSERT INTO services (id, name, icon, summary, points, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
                $stmt->execute([$service['id'], $service['name'], $service['icon'], $service['summary'], $points, $sort]);
                flash('ok', 'Service created.');
            }
            export_site_config_js();
            redirect(admin_url('services/index.php'));
        } catch (PDOException $e) {
            $errors[] = (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'Duplicate'))
                ? 'Service ID already exists.'
                : 'Could not save service.';
        }
    }
}

admin_header($existing ? 'Edit Service' : 'Add Service', 'services', ['Services' => 'services/index.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Name
      <input type="text" name="name" required value="<?= e($service['name']) ?>">
    </label>
    <label>ID
      <input type="text" name="id" value="<?= e($service['id']) ?>">
      <span class="hint">e.g. consultancy</span>
    </label>
    <label>Icon key
      <input type="text" name="icon" value="<?= e($service['icon']) ?>">
      <span class="hint">Matches sprite icon names (consult, maintenance, resource)</span>
    </label>
    <label class="full">Summary
      <textarea name="summary" rows="3"><?= e($service['summary']) ?></textarea>
    </label>
    <label class="full">Points <span class="hint">(one per line)</span>
      <textarea name="points" rows="6"><?= e($service['points']) ?></textarea>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Service</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('services/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
