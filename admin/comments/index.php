<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();
migrate();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int) post('id');
    if (post('action') === 'delete' && $id > 0) {
        delete_blog_comment($id);
        flash('ok', 'Comment deleted.');
    }
    redirect(admin_url('comments/index.php'));
}

$comments = all_blog_comments();
$titles = [];
foreach (setting_list('blog') as $post) {
    $slug = (string) ($post['slug'] ?? '');
    if ($slug !== '') {
        $titles[$slug] = (string) ($post['title'] ?? $slug);
    }
}

admin_header('Comments', 'comments');
?>
<div class="toolbar">
  <p><?= count($comments) ?> comment<?= count($comments) === 1 ? '' : 's' ?></p>
</div>
<?php if (!$comments): ?>
  <p class="muted">No comments yet. They appear here when readers post on an article.</p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Post</th>
          <th>From</th>
          <th>Comment</th>
          <th>Date</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($comments as $row): ?>
          <?php $slug = (string) ($row['post_slug'] ?? ''); ?>
          <tr>
            <td>
              <?php if ($slug !== ''): ?>
                <a href="<?= e(url_for(post_slug_path($slug))) ?>" target="_blank" rel="noopener"><?= e($titles[$slug] ?? $slug) ?></a>
              <?php else: ?>
                —
              <?php endif; ?>
            </td>
            <td>
              <strong><?= e((string) ($row['name'] ?? '')) ?></strong><br>
              <span class="muted"><?= e((string) ($row['email'] ?? '')) ?></span>
            </td>
            <td><?= e(truncate((string) ($row['body'] ?? ''), 160)) ?></td>
            <td><?= e((string) ($row['created_at'] ?? '')) ?></td>
            <td>
              <form method="post" data-confirm="Delete this comment?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) ($row['id'] ?? 0) ?>">
                <button class="btn btn-secondary" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php admin_footer(); ?>
