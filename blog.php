<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

const BLOG_PER_PAGE = 8;

$posts = blog_posts();
$total = count($posts);
$pages = max(1, (int) ceil($total / BLOG_PER_PAGE));
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$page = max(1, min($page, $pages));
$offset = ($page - 1) * BLOG_PER_PAGE;
$list = array_slice($posts, $offset, BLOG_PER_PAGE);

$blogCopy = page_copy('blog');
$crumbs = ['Home' => '/', 'Blog' => null];
$sprite = url_for('assets/img/sprite.svg');
$pad = static fn (int $i): string => str_pad((string) $i, 2, '0', STR_PAD_LEFT);

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
<?php render_page_banner($blogCopy, $crumbs); ?>

<section class="section blog-index">
  <div class="container blog-index-wrap">
    <?php if ($list): ?>
      <header class="blog-index-head" data-reveal>
        <span class="eyebrow">The list</span>
        <h2 class="section-title"><?= e($total === 1 ? 'One note from the field' : $total . ' notes from the field') ?></h2>
        <p class="section-sub">Jump to a number, or read straight through. Each note is a short brief from the people who specify and install the kit.</p>
      </header>

      <nav class="blog-toc" aria-label="In this list" data-reveal>
        <p class="blog-toc-label">In this list<?= $pages > 1 ? ' · page ' . $page : '' ?></p>
        <ol>
          <?php foreach ($list as $i => $post): ?>
            <?php $anchor = 'note-' . ($offset + $i + 1); ?>
            <li>
              <a href="#<?= e($anchor) ?>">
                <span><?= e($pad($offset + $i + 1)) ?></span>
                <?= e((string) ($post['title'] ?? 'Untitled')) ?>
              </a>
            </li>
          <?php endforeach; ?>
        </ol>
      </nav>

      <ol class="blog-list">
        <?php foreach ($list as $i => $post): ?>
          <?php
          $n = $offset + $i + 1;
          $href = post_path((string) ($post['slug'] ?? ''));
          $title = (string) ($post['title'] ?? '');
          $excerpt = trim((string) ($post['excerpt'] ?? ''));
          $date = (string) ($post['date'] ?? '');
          $stamp = $date !== '' ? (int) strtotime($date) : 0;
          $mins = blog_read_minutes($post);
          $image = url_for((string) ($post['image'] ?? 'assets/img/blog-1.jpg'));
          $anchor = 'note-' . $n;
          $featured = $page === 1 && $i === 0;
          $meta = array_values(array_filter([
              $stamp ? date('j M Y', $stamp) : '',
              $mins . ' min read',
          ]));
          ?>
          <?php if ($featured): ?>
            <li class="blog-feature" id="<?= e($anchor) ?>" data-reveal>
              <a class="blog-feature-media" href="<?= e($href) ?>" aria-label="<?= e($title) ?>">
                <img src="<?= e($image) ?>" alt="" width="960" height="640" data-fallback>
              </a>
              <div class="blog-feature-copy">
                <span class="blog-num" aria-hidden="true"><?= e($pad($n)) ?></span>
                <?php if ($meta): ?>
                  <p class="blog-meta"><?= e(implode(' · ', $meta)) ?></p>
                <?php endif; ?>
                <h2><a href="<?= e($href) ?>"><?= e($title) ?></a></h2>
                <?php if ($excerpt !== ''): ?>
                  <p class="blog-excerpt"><?= e($excerpt) ?></p>
                <?php endif; ?>
                <a class="btn btn-primary" href="<?= e($href) ?>">Read the article
                  <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-arrow"></use></svg>
                </a>
              </div>
            </li>
          <?php else: ?>
            <li class="blog-row" id="<?= e($anchor) ?>" data-reveal>
              <span class="blog-num" aria-hidden="true"><?= e($pad($n)) ?></span>
              <a class="blog-row-media" href="<?= e($href) ?>" aria-label="<?= e($title) ?>">
                <img src="<?= e($image) ?>" alt="" width="360" height="240" loading="lazy" data-fallback>
              </a>
              <div class="blog-row-copy">
                <?php if ($meta): ?>
                  <p class="blog-meta"><?= e(implode(' · ', $meta)) ?></p>
                <?php endif; ?>
                <h2><a href="<?= e($href) ?>"><?= e($title) ?></a></h2>
                <?php if ($excerpt !== ''): ?>
                  <p class="blog-excerpt"><?= e($excerpt) ?></p>
                <?php endif; ?>
                <a class="blog-read-more" href="<?= e($href) ?>">Read the article
                  <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-arrow"></use></svg>
                </a>
              </div>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ol>
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
