<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();
ensure_jobs_setting();

$index = isset($_GET['index']) ? (int) $_GET['index'] : -1;
$existing = $index >= 0 ? setting_get_item('jobs', $index) : null;
$job = normalize_job($existing ?? []) ?? [
    'title' => '',
    'slug' => '',
    'department' => '',
    'location' => '',
    'type' => 'Full-time',
    'summary' => '',
    'duties' => [],
    'open' => true,
    'openings' => 1,
    'posted' => '',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $job = [
        'title' => post('title'),
        'slug' => post('slug') !== '' ? slugify(post('slug')) : slugify(post('title')),
        'department' => post('department'),
        'location' => post('location'),
        'type' => post('type'),
        'summary' => post('summary'),
        'duties' => lines_to_array(post('duties')),
        'open' => isset($_POST['open']),
        'openings' => max(1, (int) post('openings')),
        'posted' => $existing ? (string) ($existing['posted'] ?? gmdate('Y-m-d')) : gmdate('Y-m-d'),
    ];
    if ($job['title'] === '') {
        $error = 'Give the role a title.';
    } elseif ($job['summary'] === '') {
        $error = 'Add a short summary. It shows on the job page.';
    }
    if ($error === '') {
        $job = normalize_job($job);
        if (!$job) {
            $error = 'Could not save that role. Check the title and try again.';
        } else {
            setting_save_item('jobs', $existing ? $index : null, $job);
            export_site_config_js();
            flash('ok', 'Saved “' . $job['title'] . '”.');
            redirect(admin_url('jobs/index.php'));
        }
    }
}

admin_header($existing ? 'Edit Role' : 'Add Role', 'jobs', ['Careers' => 'jobs/index.php']);
?>
<?php if ($error): ?>
  <div class="flash flash--error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Role title
      <input type="text" name="title" required value="<?= e($job['title']) ?>" autofocus>
    </label>
    <label>Slug
      <input type="text" name="slug" value="<?= e($job['slug']) ?>">
      <span class="hint">Used in the job link, e.g. /careers/safety-installation-technician.</span>
    </label>
    <label>Team / department
      <input type="text" name="department" value="<?= e($job['department']) ?>" placeholder="Field, Sales, Operations…">
    </label>
    <label>Location
      <input type="text" name="location" value="<?= e($job['location']) ?>" placeholder="Berhampur, Odisha">
    </label>
    <label>Type
      <input type="text" name="type" value="<?= e($job['type']) ?>" placeholder="Full-time">
    </label>
    <label>Open positions
      <input type="number" name="openings" min="1" step="1" value="<?= (int) ($job['openings'] ?? 1) ?>">
      <span class="hint">Shown on the job card, for example “2 open positions”.</span>
    </label>
    <label class="full">Short summary
      <textarea name="summary" rows="2" required><?= e($job['summary']) ?></textarea>
      <span class="hint">One or two sentences on the job page.</span>
    </label>
    <label class="full">What you will do <span class="hint">(one per line)</span>
      <textarea name="duties" rows="6"><?= e(array_to_lines($job['duties'])) ?></textarea>
    </label>
    <label class="full seo-toggle">
      <span>
        <input type="checkbox" name="open" value="1" <?= $job['open'] ? 'checked' : '' ?>>
        This role is open
      </span>
      <span class="hint">Checked roles appear on the careers page so people can apply. Unchecked roles stay hidden.</span>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit"><?= $existing ? 'Save Changes' : 'Create Role' ?></button>
    <a class="btn btn-secondary" href="<?= e(admin_url('jobs/index.php')) ?>">Cancel</a>
    <?php if ($existing && $job['slug'] !== ''): ?>
      <a class="btn btn-secondary" href="<?= e(url_for('careers/' . rawurlencode($job['slug']))) ?>" target="_blank" rel="noopener">Preview &#8599;</a>
    <?php endif; ?>
  </div>
</form>
<?php admin_footer(); ?>
