<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();
migrate();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$existing = $id ? get_leadership_row($id) : null;
$item = [
    'name' => $existing['name'] ?? '',
    'designation' => $existing['designation'] ?? '',
    'experience' => $existing['experience'] ?? '',
    'expertise' => $existing['expertise'] ?? '',
    'background' => $existing['background'] ?? '',
    'linkedin' => $existing['linkedin'] ?? '',
    'image' => $existing['image'] ?? '',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $item = [
        'name' => post('name'),
        'designation' => post('designation'),
        'experience' => post('experience'),
        'expertise' => post('expertise'),
        'background' => post('background'),
        'linkedin' => post('linkedin'),
        'image' => $item['image'],
    ];
    try {
        $item['image'] = (string) (save_uploaded_image('image', $item['image'] !== '' ? $item['image'] : null) ?? '');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
    if ($item['name'] === '') {
        $error = $error !== '' ? $error : 'Name is required.';
    }
    if ($error === '') {
        if ($existing) {
            db()->prepare(
                'UPDATE leadership SET name=?, designation=?, experience=?, expertise=?, background=?, linkedin=?, image=? WHERE id=?'
            )->execute([
                $item['name'], $item['designation'], $item['experience'], $item['expertise'],
                $item['background'], $item['linkedin'], $item['image'], $id,
            ]);
        } else {
            $sort = (int) db()->query('SELECT COALESCE(MAX(sort_order), -1) + 1 FROM leadership')->fetchColumn();
            db()->prepare(
                'INSERT INTO leadership (name, designation, experience, expertise, background, linkedin, image, sort_order)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
            )->execute([
                $item['name'], $item['designation'], $item['experience'], $item['expertise'],
                $item['background'], $item['linkedin'], $item['image'], $sort,
            ]);
        }
        export_site_config_js();
        flash('ok', 'Leadership entry saved.');
        redirect(admin_url('leadership/index.php'));
    }
}

admin_header($existing ? 'Edit Leadership' : 'Add Leadership', 'leadership', ['Leadership' => 'leadership/index.php']);
?>
<?php if ($error): ?>
  <div class="flash flash--error"><?= e($error) ?></div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label>Name
      <input type="text" name="name" required value="<?= e($item['name']) ?>">
    </label>
    <label>Designation
      <input type="text" name="designation" value="<?= e($item['designation']) ?>">
    </label>
    <label>Experience
      <input type="text" name="experience" value="<?= e($item['experience']) ?>" placeholder="e.g. 13+ years">
    </label>
    <label>Area of expertise
      <input type="text" name="expertise" value="<?= e($item['expertise']) ?>">
    </label>
    <label class="full">Professional background
      <textarea name="background" rows="5"><?= e($item['background']) ?></textarea>
    </label>
    <label class="full">LinkedIn / profile URL
      <input type="url" name="linkedin" value="<?= e($item['linkedin']) ?>" placeholder="https://www.linkedin.com/in/...">
    </label>
    <label class="full">Photo
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
      <span class="hint">Choose an image from your computer. Recommended 800 × 1000 px (4:5 portrait).</span>
    </label>
    <?php if ($item['image'] !== ''): ?>
      <div class="full file-preview">
        <img src="<?= e(url_for($item['image'])) ?>" alt="Current photo">
        <span class="hint">Current photo. Choose a new file to replace it.</span>
      </div>
    <?php endif; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-secondary" href="<?= e(admin_url('leadership/index.php')) ?>">Cancel</a>
  </div>
</form>
<?php admin_footer(); ?>
