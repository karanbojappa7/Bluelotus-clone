<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$c = counts();
$recentEnquiries = recent_enquiries(6);
$tiles = [
    ['label' => 'Enquiries', 'count' => $c['enquiriesUnread'], 'href' => 'enquiries/index.php', 'action' => null, 'hint' => $c['enquiries'] === 1 ? '1 total' : $c['enquiries'] . ' total'],
    ['label' => 'Products', 'count' => $c['products'], 'href' => 'products/index.php', 'action' => 'products/form.php'],
    ['label' => 'Categories', 'count' => $c['categories'], 'href' => 'categories/index.php', 'action' => 'categories/form.php'],
    ['label' => 'Services', 'count' => $c['services'], 'href' => 'services/index.php', 'action' => 'services/form.php'],
    ['label' => 'Blog posts', 'count' => $c['blog'], 'href' => 'blog/index.php', 'action' => 'blog/form.php'],
    ['label' => 'Testimonials', 'count' => $c['testimonials'], 'href' => 'testimonials/index.php', 'action' => 'testimonials/form.php'],
    ['label' => 'Leadership', 'count' => $c['leadership'], 'href' => 'leadership/index.php', 'action' => 'leadership/form.php'],
    ['label' => 'Stats', 'count' => $c['stats'], 'href' => 'stats.php', 'action' => null],
    ['label' => 'Banner slides', 'count' => $c['hero'], 'href' => 'hero/index.php', 'action' => 'hero/form.php'],
    ['label' => 'Clients', 'count' => $c['clients'], 'href' => 'clients.php', 'action' => null],
];

$emptySections = array_values(array_filter($tiles, static fn ($t) => (int) $t['count'] === 0 && !empty($t['action'])));

admin_header('Dashboard', 'dashboard');
?>
<div class="dash-grid">
  <?php foreach ($tiles as $tile): ?>
    <a class="dash-card" href="<?= e(admin_url($tile['href'])) ?>">
      <span><?= e($tile['label']) ?></span>
      <strong><?= (int) $tile['count'] ?></strong>
      <em><?= e($tile['hint'] ?? 'Manage →') ?></em>
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

<div class="card" style="margin-bottom:1rem">
  <div class="toolbar" style="margin:0 0 0.8rem;padding:0">
    <strong style="font-size:1rem">Latest enquiries</strong>
    <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('enquiries/index.php')) ?>">View all</a>
  </div>
  <?php if (!$recentEnquiries): ?>
    <p class="muted" style="margin:0">No messages yet. Quotes and audit requests from the contact form will show up here.</p>
  <?php else: ?>
    <div class="table-wrap" style="border:0">
      <table>
        <thead>
          <tr>
            <th>From</th>
            <th>Contact</th>
            <th>Interest</th>
            <th>Received</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentEnquiries as $row): ?>
            <tr class="<?= (int) $row['is_read'] === 0 ? 'is-unread' : '' ?>">
              <td>
                <a href="<?= e(admin_url('enquiries/index.php')) ?>?id=<?= (int) $row['id'] ?>"><strong><?= e($row['name']) ?></strong></a>
                <?php if ((int) $row['is_read'] === 0): ?><span class="badge">New</span><?php endif; ?>
                <div class="muted"><?= e(truncate($row['message'], 80)) ?></div>
              </td>
              <td>
                <div><?= e($row['email']) ?></div>
                <div class="muted"><?= e($row['phone']) ?></div>
              </td>
              <td><?= e($row['category'] !== '' ? $row['category'] : '—') ?></td>
              <td class="muted"><?= e((string) $row['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <strong style="font-size:1rem">Publishing</strong>
  <div class="actions">
    <a class="btn btn-secondary btn-sm" href="<?= e(url_for('')) ?>" target="_blank" rel="noopener">View live site &#8599;</a>
    <a class="btn btn-secondary btn-sm" href="<?= e(admin_url('account.php')) ?>">Change password</a>
  </div>
</div>
<?php admin_footer(); ?>
