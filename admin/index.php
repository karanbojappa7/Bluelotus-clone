<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$c = counts();
$tiles = [
    ['label' => 'Products', 'count' => $c['products'], 'href' => 'products/index.php', 'action' => 'products/form.php'],
    ['label' => 'Categories', 'count' => $c['categories'], 'href' => 'categories/index.php', 'action' => 'categories/form.php'],
    ['label' => 'Services', 'count' => $c['services'], 'href' => 'services/index.php', 'action' => 'services/form.php'],
    ['label' => 'Blog posts', 'count' => $c['blog'], 'href' => 'blog/index.php', 'action' => 'blog/form.php'],
    ['label' => 'Testimonials', 'count' => $c['testimonials'], 'href' => 'testimonials/index.php', 'action' => 'testimonials/form.php'],
    ['label' => 'Leadership', 'count' => $c['leadership'], 'href' => 'leadership/index.php', 'action' => 'leadership/form.php'],
    ['label' => 'Stats', 'count' => $c['stats'], 'href' => 'stats.php', 'action' => null],
    ['label' => 'Clients', 'count' => $c['clients'], 'href' => 'clients.php', 'action' => null],
];

$emptySections = array_values(array_filter($tiles, static fn ($t) => (int) $t['count'] === 0));

admin_header('Dashboard', 'dashboard');
?>
<div class="dash-grid">
  <?php foreach ($tiles as $tile): ?>
    <a class="dash-card" href="<?= e(admin_url($tile['href'])) ?>">
      <span><?= e($tile['label']) ?></span>
      <strong><?= (int) $tile['count'] ?></strong>
      <em>Manage &rarr;</em>
    </a>
  <?php endforeach; ?>
</div>

<?php if ($emptySections): ?>
<div class="card" style="margin-bottom:1rem">
  <strong style="font-size:1rem">Sections still empty</strong>
  <p class="muted" style="margin:0.4rem 0 0.8rem">These areas render blank on the public site until you add content.</p>
  <div class="actions">
    <?php foreach ($emptySections as $tile): ?>
      <a class="btn btn-secondary btn-sm" href="<?= e(admin_url($tile['action'] ?? $tile['href'])) ?>">Add <?= e(rtrim($tile['label'], 's')) ?></a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <strong style="font-size:1rem">Publishing</strong>
  <div class="actions">
    <a class="btn btn-secondary btn-sm" href="<?= e(url_for('')) ?>" target="_blank" rel="noopener">View live site &#8599;</a>
    <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('account.php')) ?>">Change password</a>
  </div>
</div>
<?php admin_footer(); ?>
