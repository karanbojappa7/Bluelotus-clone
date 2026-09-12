<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

const BLOG_PER_PAGE = 8;

$posts = db_ready() ? setting_list('blog') : [];
$total = count($posts);
$pages = max(1, (int) ceil($total / BLOG_PER_PAGE));
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $pages));
$offset = ($page - 1) * BLOG_PER_PAGE;
$list = array_slice($posts, $offset, BLOG_PER_PAGE);

$crumbs = ['Home' => '/', 'Blog' => null];

$schema = schema_url_list('Safety insights', $list, static fn ($post) => [
    'name' => (string) ($post['title'] ?? ''),
    'url' => seo_url(post_slug_path((string) ($post['slug'] ?? ''))),
], $offset);

page_head([
    'title' => $page > 1 ? 'Blog — Page ' . $page : 'Blog',
    'description' => 'Practical safety guidance from the ' . brand_name() . ' engineering team — compliance checklists, product selection, and installation insight.',
    'canonical' => $page > 1 ? 'blog?page=' . $page : 'blog',
    'breadcrumbs' => $crumbs,
    'schema' => array_values(array_filter([$schema])),
    'prev' => $page > 1 ? ($page - 1 > 1 ? 'blog?page=' . ($page - 1) : 'blog') : null,
    'next' => $page < $pages ? 'blog?page=' . ($page + 1) : null,
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light">Safety Insights</span>
    <h1 class="page-title">Guidance from the people who install it.</h1>
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php if ($list): ?>
      <div class="grid grid-3">
        <?php foreach ($list as $post): ?>
          <?php
          $href = post_path((string) ($post['slug'] ?? ''));
          $date = (string) ($post['date'] ?? '');
          $stamp = $date !== '' ? (int) strtotime($date) : 0;
          ?>
          <article class="blog-card">
            <a class="blog-card-media" href="<?= e($href) ?>">
              <?php if ($stamp): ?>
                <span class="blog-card-date"><?= e(date('d M', $stamp)) ?></span>
              <?php endif; ?>
              <img src="<?= e(url_for((string) ($post['image'] ?? 'assets/img/blog-1.jpg'))) ?>"
                   alt="<?= e((string) ($post['title'] ?? '')) ?>" width="600" height="400" loading="lazy" data-fallback>
            </a>
            <div class="blog-card-body">
              <h2><a href="<?= e($href) ?>"><?= e((string) ($post['title'] ?? '')) ?></a></h2>
              <p><?= e((string) ($post['excerpt'] ?? '')) ?></p>
              <a class="blog-read-more" href="<?= e($href) ?>">Read More
                <svg width="18" height="18" aria-hidden="true"><use href="<?= e(url_for('assets/img/sprite.svg')) ?>#icon-arrow"></use></svg>
              </a>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?= render_public_pagination($page, $pages, 'blog') ?>
    <?php else: ?>
      <?= render_empty_state(
          'No articles published yet.',
          "We're writing up field notes from recent installations. Check back shortly.",
          [['label' => 'Talk to our team', 'href' => 'contact', 'primary' => true]]
      ) ?>
    <?php endif; ?>
  </div>
</section>
<?php page_foot(); ?>
