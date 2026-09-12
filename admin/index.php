<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/layout.php';
require_login();

$c = counts();
admin_header('Dashboard', 'dashboard');
?>
<div class="cards">
  <div class="card"><span>Products</span><strong><?= (int) $c['products'] ?></strong></div>
  <div class="card"><span>Services</span><strong><?= (int) $c['services'] ?></strong></div>
  <div class="card"><span>Categories</span><strong><?= (int) $c['categories'] ?></strong></div>
  <div class="card"><span>Blog posts</span><strong><?= (int) $c['blog'] ?></strong></div>
  <div class="card"><span>Testimonials</span><strong><?= (int) $c['testimonials'] ?></strong></div>
  <div class="card"><span>Clients</span><strong><?= (int) $c['clients'] ?></strong></div>
</div>
<div class="card">
  <p class="muted">Everything below publishes live through <code>config/site.config.php</code>.</p>
  <div class="form-actions" style="flex-wrap:wrap">
    <a class="btn" href="products.php">Products</a>
    <a class="btn btn-secondary" href="services.php">Services</a>
    <a class="btn btn-secondary" href="categories.php">Categories</a>
    <a class="btn btn-secondary" href="blog.php">Blog</a>
    <a class="btn btn-secondary" href="testimonials.php">Testimonials</a>
    <a class="btn btn-secondary" href="leadership.php">Leadership</a>
    <a class="btn btn-secondary" href="stats.php">Stats</a>
    <a class="btn btn-secondary" href="clients.php">Clients</a>
    <a class="btn btn-secondary" href="company.php">Company</a>
    <a class="btn btn-secondary" href="contact.php">Contact</a>
    <a class="btn btn-secondary" href="social.php">Social</a>
    <a class="btn btn-secondary" href="seo.php">SEO</a>
  </div>
</div>
<?php admin_footer(); ?>
