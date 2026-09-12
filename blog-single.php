<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$slug = isset($_GET['post']) ? trim((string) $_GET['post']) : '';
$posts = db_ready() ? setting_list('blog') : [];

$post = null;
foreach ($posts as $candidate) {
    if (($candidate['slug'] ?? '') === $slug && $slug !== '') {
        $post = $candidate;
        break;
    }
}

if (!$post) {
    render_not_found([
        'title' => 'Article not found',
        'description' => 'That article is no longer available. Browse the latest safety insights from ' . brand_name() . '.',
        'canonical' => 'blog/' . rawurlencode($slug),
        'heading' => "We couldn't find that article.",
        'body' => 'The link may be out of date. Browse the latest posts instead.',
        'actions' => [['label' => 'All articles', 'href' => 'blog', 'primary' => true]],
    ]);
}

$related = [];
foreach ($posts as $candidate) {
    if (($candidate['slug'] ?? '') !== $post['slug']) {
        $related[] = $candidate;
    }
    if (count($related) >= 3) {
        break;
    }
}

$crumbs = ['Home' => '/', 'Blog' => 'blog', (string) $post['title'] => null];
$published = (string) ($post['date'] ?? '');
$displayDate = $published !== '' ? date('j F Y', (int) strtotime($published)) : '';

page_head(seo_overrides($post) + [
    'title' => ($post['metaTitle'] ?? '') !== '' ? $post['metaTitle'] : $post['title'],
    'description' => ($post['metaDescription'] ?? '') !== '' ? $post['metaDescription'] : ($post['excerpt'] ?? ''),
    'canonical' => post_slug_path((string) $post['slug']),
    'image' => $post['image'] ?? null,
    'type' => 'article',
    'author' => (string) ($post['author'] ?? ''),
    'publishedTime' => $published,
    'breadcrumbs' => $crumbs,
    'schema' => [schema_article($post)],
]);
?>
<section class="page-header page-header--slim">
  <div class="container">
    <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
  </div>
</section>

<section class="section">
  <div class="container article-layout">
    <article>
      <span class="eyebrow">Safety Insights</span>
      <h1 class="page-title"><?= e((string) $post['title']) ?></h1>
      <?php if ($displayDate !== ''): ?>
        <p class="article-meta">Published <time datetime="<?= e($published) ?>"><?= e($displayDate) ?></time></p>
      <?php endif; ?>
      <?php if (!empty($post['image'])): ?>
        <img class="media-frame mt-4" src="<?= e(url_for((string) $post['image'])) ?>" alt="<?= e((string) $post['title']) ?>" width="900" height="520" data-fallback>
      <?php endif; ?>
      <?php if (!empty($post['excerpt'])): ?>
        <p class="article-lead mt-4"><?= e((string) $post['excerpt']) ?></p>
      <?php endif; ?>
      <div class="article-body mt-4"><?= render_article_body((string) ($post['body'] ?? '')) ?></div>
    </article>
    <aside class="catalog-side">
      <div class="side-card side-card--accent">
        <span class="eyebrow">Need a site audit?</span>
        <h2 class="mt-2">Talk to our engineers</h2>
        <p class="mt-2">We scope compliance gaps and recommend a product mix within a week.</p>
        <a href="<?= e(url_for('contact')) ?>" class="btn btn-primary mt-4">Book an Audit</a>
      </div>
      <?php if ($related): ?>
      <div class="side-card mt-4">
        <span class="eyebrow">More Articles</span>
        <ul class="side-links mt-3">
          <?php foreach ($related as $item): ?>
            <li><a href="<?= e(post_path((string) $item['slug'])) ?>"><?= e((string) $item['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>
<?php page_foot(); ?>
