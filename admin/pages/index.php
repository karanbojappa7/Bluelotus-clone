<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

admin_header('Pages', 'pages');
?>
<p class="muted">Edit the copy and images on Home, About, Contact, and Privacy. Hero banners, contact numbers, and maps still have their own screens.</p>
<div class="cards">
  <?php foreach (page_labels() as $slug => $label): ?>
    <a class="card" href="<?= e(admin_url('pages/edit.php?page=' . rawurlencode($slug))) ?>">
      <strong><?= e($label) ?></strong>
      <span>Edit content<?= $slug === 'about' || $slug === 'home' ? ' and images' : '' ?></span>
    </a>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
