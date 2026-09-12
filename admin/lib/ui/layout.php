<?php
declare(strict_types=1);

function admin_nav_groups(): array
{
    return [
        'Overview' => [
            ['id' => 'dashboard', 'href' => 'index.php', 'label' => 'Dashboard'],
        ],
        'Catalog' => [
            ['id' => 'products', 'href' => 'products.php', 'label' => 'Products'],
            ['id' => 'categories', 'href' => 'categories.php', 'label' => 'Categories'],
            ['id' => 'services', 'href' => 'services.php', 'label' => 'Services'],
            ['id' => 'blog', 'href' => 'blog.php', 'label' => 'Blog'],
        ],
        'Site content' => [
            ['id' => 'faqs', 'href' => 'faqs.php', 'label' => 'FAQ'],
            ['id' => 'testimonials', 'href' => 'testimonials.php', 'label' => 'Testimonials'],
            ['id' => 'leadership', 'href' => 'leadership.php', 'label' => 'Leadership'],
            ['id' => 'stats', 'href' => 'stats.php', 'label' => 'Stats'],
            ['id' => 'clients', 'href' => 'clients.php', 'label' => 'Clients'],
        ],
        'Settings' => [
            ['id' => 'company', 'href' => 'company.php', 'label' => 'Company'],
            ['id' => 'contact', 'href' => 'contact.php', 'label' => 'Contact'],
            ['id' => 'social', 'href' => 'social.php', 'label' => 'Social'],
            ['id' => 'seo', 'href' => 'seo.php', 'label' => 'SEO'],
            ['id' => 'account', 'href' => 'account.php', 'label' => 'Account'],
        ],
    ];
}

function admin_header(string $title, string $active = '', array $crumbs = []): void
{
    cms_security_headers(true);
    $user = current_user();
    $flash = take_flash();
    $groups = admin_nav_groups();
    $needsPassword = $user && uses_default_password((int) $user['id']) && $active !== 'account';
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> | Bluelotus CMS</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<a class="skip-link" href="#adminMain">Skip to content</a>
<div class="admin-shell">
  <aside class="admin-nav" id="adminNav">
    <div class="admin-brand">
      <strong>Bluelotus CMS</strong>
      <span>Content Manager</span>
    </div>
    <nav aria-label="Sections">
      <?php foreach ($groups as $groupLabel => $items): ?>
        <p class="nav-group"><?= e($groupLabel) ?></p>
        <?php foreach ($items as $item): ?>
          <a class="<?= $active === $item['id'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"<?= $active === $item['id'] ? ' aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <p class="nav-group">Shortcuts</p>
      <a href="../" target="_blank" rel="noopener">View Site ↗</a>
      <a href="logout.php" class="nav-logout">Log out</a>
    </nav>
    <?php if ($user): ?>
    <div class="admin-user">Signed in as <strong><?= e($user['username']) ?></strong></div>
    <?php endif; ?>
  </aside>
  <main class="admin-main" id="adminMain">
    <header class="admin-top">
      <button class="nav-burger" type="button" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">☰</button>
      <div class="admin-top-copy">
        <?php if ($crumbs): ?>
          <nav class="admin-crumbs" aria-label="Breadcrumb">
            <?php foreach ($crumbs as $label => $href): ?>
              <a href="<?= e((string) $href) ?>"><?= e((string) $label) ?></a><span aria-hidden="true">/</span>
            <?php endforeach; ?>
            <span><?= e($title) ?></span>
          </nav>
        <?php endif; ?>
        <h1><?= e($title) ?></h1>
      </div>
    </header>
    <?php if ($needsPassword): ?>
    <div class="flash flash--warn" role="alert">
      This account still uses the default password. <a href="account.php">Change it now</a> before the site goes live.
    </div>
    <?php endif; ?>
    <?php if ($flash): ?>
    <div class="flash flash--<?= e($flash['type']) ?>" role="status" data-autodismiss>
      <span><?= e($flash['message']) ?></span>
      <button type="button" class="flash-close" aria-label="Dismiss message">&times;</button>
    </div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
<script src="assets/admin.js" defer></script>
</body>
</html>
<?php
}
