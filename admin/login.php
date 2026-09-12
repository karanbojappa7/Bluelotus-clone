<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (!db_ready()) {
    redirect('install.php');
}
if (current_user()) {
    redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = post('username');
    $password = post('password');
    if ($username === '' || $password === '') {
        $error = 'Enter username and password.';
    } elseif (attempt_login($username, $password)) {
        redirect('index.php');
    } else {
        $error = 'Invalid username or password.';
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
        <input type="text" name="username" required autofocus value="<?= e(post('username', 'admin')) ?>">
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button class="btn" type="submit">Sign in</button>
    </form>
  </div>
</body>
</html>
