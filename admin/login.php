<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
cms_security_headers(true);

if (!db_ready()) {
    redirect('install.php');
}
if (current_user()) {
    redirect('index.php');
}

$ip = cms_client_ip();
$lockout = login_lockout_seconds($ip);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = post('username');
    $password = post('password');
    if ($lockout > 0) {
        $error = 'Too many failed attempts. Try again in ' . describe_lockout($lockout) . '.';
    } elseif ($username === '' || $password === '') {
        $error = 'Enter username and password.';
    } elseif (attempt_login($username, $password)) {
        clear_login_attempts($ip);
        redirect('index.php');
    } else {
        record_failed_login($ip, $username);
        $lockout = login_lockout_seconds($ip);
        $error = $lockout > 0
            ? 'Too many failed attempts. Try again in ' . describe_lockout($lockout) . '.'
            : 'Invalid username or password.';
    }
}
$flash = take_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body class="auth-page">
  <div class="auth-card">
    <h1>Admin Login</h1>
    <p>Manage products and services for Bluelotus Infrasafety.</p>
    <?php if ($flash): ?>
      <div class="flash flash--<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="flash flash--error"><?= e($error) ?></div>
    <?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label>Username
        <input type="text" name="username" required autofocus value="<?= e(post('username')) ?>" <?= $lockout > 0 ? 'disabled' : '' ?>>
      </label>
      <label>Password
        <input type="password" name="password" required <?= $lockout > 0 ? 'disabled' : '' ?>>
      </label>
      <button class="btn" type="submit" <?= $lockout > 0 ? 'disabled' : '' ?>>Sign in</button>
    </form>
  </div>
</body>
</html>
