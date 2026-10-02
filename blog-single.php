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

$commentError = '';
$commentName = '';
$commentEmail = '';
$commentBody = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && post('action') === 'comment') {
    $csrf = $_POST['csrf'] ?? '';
    $commentName = post('commentName');
    $commentEmail = post('commentEmail');
    $commentBody = post('commentBody');
    if (!is_string($csrf) || $csrf === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $csrf)) {
        $commentError = 'Your session expired. Refresh the page and try again.';
    } elseif (trim(post('website')) !== '') {
        redirect(url_for(post_slug_path($slug)) . '#comments');
    } elseif (!db_ready()) {
        $commentError = 'Comments are temporarily unavailable.';
    } elseif ($commentName === '' || $commentEmail === '' || $commentBody === '') {
        $commentError = 'Please fill in your name, email, and comment.';
    } elseif (!filter_var($commentEmail, FILTER_VALIDATE_EMAIL)) {
        $commentError = 'Enter a valid email address.';
    } elseif (mb_strlen($commentBody) < 8) {
        $commentError = 'Write a little more — at least 8 characters.';
    } else {
        try {
            migrate();
            $ip = cms_client_ip();
            if (blog_comment_ip_limited($ip)) {
                $commentError = 'Too many comments from this connection. Please try again later.';
            } else {
                save_blog_comment([
                    'post_slug' => $slug,
                    'name' => $commentName,
                    'email' => $commentEmail,
                    'body' => $commentBody,
                    'ip' => $ip,
                ]);
                $_SESSION['blog_comment_ok'] = $slug;
                redirect(url_for(post_slug_path($slug)) . '#comments');
            }
        } catch (Throwable $e) {
            $commentError = 'Could not save your comment. Please try again.';
        }
    }
}

$related = related_blog_posts($post, $posts);
$relatedProducts = related_blog_products($post);
$popular = popular_blog_posts($posts, $slug, 6);
$comments = blog_comments($slug);

$crumbs = ['Home' => '/', 'Blog' => 'blog', (string) $post['title'] => null];
$published = (string) ($post['date'] ?? '');
$displayDate = $published !== '' ? date('j F Y', (int) strtotime($published)) : '';
$author = trim((string) ($post['author'] ?? ''));
if ($author === '') {
    $author = brand_name();
}
$readMins = blog_read_minutes($post);
$company = seo_company();
$authorRole = trim((string) ($company['tagline'] ?? ''));
if ($authorRole === '') {
    $authorRole = 'Safety equipment specialists';
}
$authorInitial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($author, 0, 1)) : strtoupper(substr($author, 0, 1));
$postUrl = seo_url(post_slug_path((string) $post['slug']));
$shareText = trim((string) ($post['title'] ?? ''));
$phones = contact_phones();
$ctaPhone = (string) ($phones[0] ?? '');
$ctaTel = preg_replace('/[^\d+]/', '', $ctaPhone) ?? '';
$sprite = url_for('assets/img/sprite.svg');
$instagram = trim((string) ((setting('social', []) ?: [])['instagram'] ?? ''));
if ($instagram === '') {
    $instagram = 'https://www.instagram.com/bluelotusenterprises';
}
$commented = isset($_SESSION['blog_comment_ok']) && $_SESSION['blog_comment_ok'] === $slug;
if ($commented) {
    unset($_SESSION['blog_comment_ok']);
}

page_head(seo_overrides($post) + [
    'title' => ($post['metaTitle'] ?? '') !== '' ? $post['metaTitle'] : $post['title'],
    'description' => ($post['metaDescription'] ?? '') !== '' ? $post['metaDescription'] : ($post['excerpt'] ?? ''),
    'canonical' => post_slug_path((string) $post['slug']),
    'image' => $post['image'] ?? null,
    'type' => 'article',
    'author' => $author,
    'publishedTime' => $published,
    'breadcrumbs' => $crumbs,
    'schema' => [schema_article($post)],
]);
?>
<section class="article-page">
  <div class="container">
    <div class="breadcrumb-row article-crumbs"><?= render_breadcrumbs($crumbs) ?></div>
    <div class="article-layout">
      <div class="article-main">
        <article class="article-card">
          <h1 class="article-title"><?= e((string) $post['title']) ?></h1>
          <div class="article-author">
            <span class="article-author-mark" aria-hidden="true"><?= e($authorInitial) ?></span>
            <span>
              <strong><?= e($author) ?></strong>
              <em><?= e($authorRole) ?></em>
            </span>
          </div>
          <?php if (!empty($post['image'])): ?>
            <img class="article-cover" src="<?= e(url_for((string) $post['image'])) ?>" alt="<?= e((string) $post['title']) ?>" width="1200" height="675" data-fallback>
          <?php endif; ?>
          <?php if (!empty($post['excerpt'])): ?>
            <p class="article-lead"><?= e((string) $post['excerpt']) ?></p>
          <?php endif; ?>
          <div class="article-body"><?= render_article_body((string) ($post['body'] ?? ''), ['excludeSlug' => (string) ($post['slug'] ?? '')]) ?></div>
          <?php if ($relatedProducts): ?>
            <div class="article-related mt-5">
              <h2>Related equipment</h2>
              <div class="grid grid-2 mt-4">
                <?php foreach ($relatedProducts as $product): ?>
                  <?= render_product_card($product) ?>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </article>

        <section class="article-card article-comments" id="comments">
          <h2>Comments<?= $comments ? ' (' . count($comments) . ')' : '' ?></h2>
          <?php if ($commented && $commentError === ''): ?>
            <p class="form-feedback is-success" role="status">Thank you. Your comment is published.</p>
          <?php endif; ?>
          <?php if ($comments): ?>
            <ol class="comment-list">
              <?php foreach ($comments as $row): ?>
                <?php
                $who = (string) ($row['name'] ?? 'Reader');
                $initial = function_exists('mb_substr') ? mb_strtoupper(mb_substr($who, 0, 1)) : strtoupper(substr($who, 0, 1));
                $when = !empty($row['created_at']) ? date('j M Y', strtotime((string) $row['created_at'])) : '';
                ?>
                <li class="comment-item">
                  <span class="comment-avatar" aria-hidden="true"><?= e($initial) ?></span>
                  <div>
                    <strong><?= e($who) ?></strong>
                    <?php if ($when !== ''): ?>
                      <time datetime="<?= e((string) $row['created_at']) ?>"><?= e($when) ?></time>
                    <?php endif; ?>
                    <p><?= nl2br(e((string) ($row['body'] ?? ''))) ?></p>
                  </div>
                </li>
              <?php endforeach; ?>
            </ol>
          <?php else: ?>
            <p class="comment-empty">No comments yet. Be the first to share a question or site experience.</p>
          <?php endif; ?>

          <form class="comment-form" method="post" action="<?= e(url_for(post_slug_path($slug))) ?>#comments" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="comment">
            <div class="hp" aria-hidden="true">
              <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
            </div>
            <h3>Leave a comment</h3>
            <?php if ($commentError !== ''): ?>
              <p class="form-feedback is-error" role="alert"><?= e($commentError) ?></p>
            <?php endif; ?>
            <div class="comment-form-grid">
              <div>
                <label class="form-label" for="commentName">Name</label>
                <input class="form-control" type="text" id="commentName" name="commentName" required value="<?= e($commentName) ?>">
              </div>
              <div>
                <label class="form-label" for="commentEmail">Email</label>
                <input class="form-control" type="email" id="commentEmail" name="commentEmail" required value="<?= e($commentEmail) ?>">
              </div>
              <div class="full">
                <label class="form-label" for="commentBody">Comment</label>
                <textarea class="form-control" id="commentBody" name="commentBody" rows="4" required><?= e($commentBody) ?></textarea>
              </div>
            </div>
            <button class="btn btn-primary mt-4" type="submit">Post comment</button>
          </form>
        </section>
      </div>

      <aside class="article-side">
        <?php if ($popular): ?>
        <div class="blog-widget">
          <div class="blog-widget-head">Popular Blogs</div>
          <div class="blog-widget-body">
            <?php foreach ($popular as $item): ?>
              <?php
              $itemDate = (string) ($item['date'] ?? '');
              $itemStamp = $itemDate !== '' ? date('M d, Y', (int) strtotime($itemDate)) : '';
              $itemMins = blog_read_minutes($item);
              $thumb = (string) ($item['image'] ?? 'assets/img/blog-1.jpg');
              ?>
              <a class="popular-post" href="<?= e(post_path((string) ($item['slug'] ?? ''))) ?>">
                <img src="<?= e(url_for($thumb !== '' ? $thumb : 'assets/img/blog-1.jpg')) ?>" alt="" width="72" height="72" loading="lazy" data-fallback>
                <span>
                  <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
                  <em><?= e(trim($itemStamp . ($itemStamp !== '' ? ' · ' : '') . $itemMins . ' min read')) ?></em>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="blog-cta">
          <h2>Need a site audit?</h2>
          <p>Certified supply, installation, and maintenance from one accountable team.</p>
          <a class="blog-cta-btn" href="<?= e(url_for('contact')) ?>">Get a Quote</a>
          <?php if ($ctaTel !== ''): ?>
            <a class="blog-cta-btn blog-cta-btn--ghost" href="tel:<?= e($ctaTel) ?>"><?= e($ctaPhone) ?></a>
          <?php endif; ?>
        </div>

        <div class="blog-widget">
          <div class="blog-widget-head">Share This Article</div>
          <div class="blog-widget-body article-share-row">
            <a class="article-share article-share--wa" href="https://api.whatsapp.com/send?text=<?= e(rawurlencode($shareText . "\n" . $postUrl)) ?>" target="_blank" rel="noopener" aria-label="Share on WhatsApp">
              <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-whatsapp"></use></svg>
            </a>
            <a class="article-share article-share--ig" href="<?= e($instagram) ?>" target="_blank" rel="noopener" aria-label="Share on Instagram">
              <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-instagram"></use></svg>
            </a>
            <a class="article-share article-share--fb" href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($postUrl)) ?>" target="_blank" rel="noopener" aria-label="Share on Facebook">
              <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-facebook"></use></svg>
            </a>
            <a class="article-share article-share--li" href="https://www.linkedin.com/sharing/share-offsite/?url=<?= e(rawurlencode($postUrl)) ?>" target="_blank" rel="noopener" aria-label="Share on LinkedIn">
              <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-linkedin"></use></svg>
            </a>
            <button type="button" class="article-share article-share--copy" data-copy-link="<?= e($postUrl) ?>" aria-label="Copy link">
              <svg width="18" height="18" aria-hidden="true"><use href="<?= e($sprite) ?>#icon-link"></use></svg>
            </button>
          </div>
        </div>

        <div class="blog-widget">
          <div class="blog-widget-head">Why <?= e(brand_name()) ?>?</div>
          <div class="blog-widget-body">
            <ul class="why-list">
              <li>Certified equipment</li>
              <li>Supply, install &amp; maintain</li>
              <li>Pan-India delivery</li>
              <li>OEM after-sales support</li>
              <li>Quote in 48 hours</li>
              <li>One accountable partner</li>
            </ul>
            <a class="btn btn-primary" href="<?= e(url_for('contact')) ?>">Get a Quote</a>
          </div>
        </div>
      </aside>
    </div>
  </div>
</section>
<?php page_foot(); ?>
