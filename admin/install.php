<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
cms_security_headers(true);

$installed = db_ready();
if ($installed && !current_user()) {
    flash('error', 'Sign in before re-seeding the CMS.');
    redirect('login.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if ($installed && post('confirm') !== 'RESEED') {
        $error = 'Type RESEED to confirm. This replaces all catalog content with seed.json.';
    } else {
        try {
            seed_from_json();
            export_site_config_js();
            if ($installed) {
                flash('ok', 'Catalog re-seeded from seed.json.');
                redirect('index.php');
            }
            flash('ok', 'CMS installed. Sign in with admin / admin123, then change the password immediately.');
            redirect('login.php');
        } catch (Throwable $e) {
            $error = 'Install failed. Check the database connection and seed.json.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Install CMS</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-page">
  <div class="auth-card">
    <h1>Install CMS</h1>
    <p>Creates tables in the MySQL database <code>bluelotus</code> and imports products, services, and categories.</p>
    <?php if ($error): ?>
      <div class="flash flash--error"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($installed): ?>
      <div class="install-note">Tables already exist. Re-seeding <strong>permanently replaces</strong> every product, service, category, and leadership entry with the contents of seed.json. Your admin login is kept.</div>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <?php if ($installed): ?>
        <label>Type <code>RESEED</code> to confirm
          <input type="text" name="confirm" required autocomplete="off" placeholder="RESEED">
        </label>
      <?php endif; ?>
      <button class="btn<?= $installed ? ' btn-danger' : '' ?>" type="submit"><?= $installed ? 'Re-seed Catalog' : 'Install CMS' ?></button>
    </form>
    <p style="margin-top:1rem"><a href="<?= $installed ? 'index.php' : 'login.php' ?>"><?= $installed ? 'Back to dashboard' : 'Back to login' ?></a></p>
  </div>
</body>
</html>
