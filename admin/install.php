<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (db_ready() && current_user()) {
    redirect('index.php');
}

$error = '';
$done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        seed_from_json();
        export_site_config_js();
        $done = true;
        flash('ok', 'CMS installed. Sign in with admin / admin123 and change the password.');
        redirect('login.php');
    } catch (Throwable $e) {
        $error = $e->getMessage();
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
    <?php if (db_ready()): ?>
      <div class="install-note">Tables already exist. Re-running install will re-seed catalog content from seed.json (admin user is kept).</div>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?>
      <button class="btn" type="submit">Install / Re-seed</button>
    </form>
    <p style="margin-top:1rem"><a href="login.php">Back to login</a></p>
  </div>
</body>
</html>
