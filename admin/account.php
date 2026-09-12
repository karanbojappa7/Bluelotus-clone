<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$user = current_user();
$errors = [];
$ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = post('current_password');
    $next = post('new_password');
    $confirm = post('confirm_password');
    if (strlen($next) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($next !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } elseif (!change_password((int) $user['id'], $current, $next)) {
        $errors[] = 'Current password is incorrect.';
    } else {
        flash('ok', 'Password updated.');
        redirect('account.php');
    }
}

admin_header('Account', 'account');
?>
<?php if ($errors): ?>
  <div class="flash flash--error"><?= e(implode(' ', $errors)) ?></div>
<?php endif; ?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <p class="muted">Signed in as <strong><?= e($user['username']) ?></strong>. Change your password after first install.</p>
  <div class="form-grid">
    <label class="full">Current password
      <input type="password" name="current_password" required>
    </label>
    <label>New password
      <input type="password" name="new_password" required minlength="8">
    </label>
    <label>Confirm new password
      <input type="password" name="confirm_password" required minlength="8">
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Update Password</button>
  </div>
</form>
<?php admin_footer(); ?>
