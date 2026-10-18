<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

admin_header('Pages', 'pages');
?>
<p class="muted">Edit hero banners, titles, and copy for every public page. Homepage slides stay under Homepage banner; category banners stay on each category; contact numbers and maps stay under Contact; job openings stay under Careers.</p>
<div class="cards">
  <?php foreach (page_labels() as $slug => $label): ?>
    <a class="card" href="<?= e(admin_url('pages/edit.php?page=' . rawurlencode($slug))) ?>">
      <strong><?= e($label) ?></strong>
      <span>Edit content and hero banner</span>
    </a>
  <?php endforeach; ?>
</div>
<?php admin_footer(); ?>
