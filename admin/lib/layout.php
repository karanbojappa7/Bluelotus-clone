<?php
declare(strict_types=1);

function admin_header(string $title, string $active = ''): void
{
    $user = current_user();
    $flash = take_flash();
    $nav = [
        ['id' => 'dashboard', 'href' => 'index.php', 'label' => 'Dashboard'],
        ['id' => 'products', 'href' => 'products.php', 'label' => 'Products'],
        ['id' => 'services', 'href' => 'services.php', 'label' => 'Services'],
        ['id' => 'categories', 'href' => 'categories.php', 'label' => 'Categories'],
        ['id' => 'blog', 'href' => 'blog.php', 'label' => 'Blog'],
        ['id' => 'testimonials', 'href' => 'testimonials.php', 'label' => 'Testimonials'],
        ['id' => 'leadership', 'href' => 'leadership.php', 'label' => 'Leadership'],
        ['id' => 'stats', 'href' => 'stats.php', 'label' => 'Stats'],
        ['id' => 'clients', 'href' => 'clients.php', 'label' => 'Clients'],
        ['id' => 'company', 'href' => 'company.php', 'label' => 'Company'],
        ['id' => 'contact', 'href' => 'contact.php', 'label' => 'Contact'],
        ['id' => 'social', 'href' => 'social.php', 'label' => 'Social'],
        ['id' => 'seo', 'href' => 'seo.php', 'label' => 'SEO'],
        ['id' => 'account', 'href' => 'account.php', 'label' => 'Account'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> | CMS</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-nav">
    <div class="admin-brand">
      <strong>Bluelotus CMS</strong>
      <span>Content Manager</span>
    </div>
    <nav>
      <?php foreach ($nav as $item): ?>
        <a class="<?= $active === $item['id'] ? 'is-active' : '' ?>" href="<?= e($item['href']) ?>"><?= e($item['label']) ?></a>
      <?php endforeach; ?>
      <a href="../index.html" target="_blank" rel="noopener">View Site</a>
      <a href="logout.php">Logout</a>
    </nav>
    <?php if ($user): ?>
    <div class="admin-user">Signed in as <strong><?= e($user['username']) ?></strong></div>
    <?php endif; ?>
  </aside>
  <main class="admin-main">
    <header class="admin-top">
      <h1><?= e($title) ?></h1>
    </header>
    <?php if ($flash): ?>
    <div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
  </main>
</div>
</body>
</html>
<?php
}
