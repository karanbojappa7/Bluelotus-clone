<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

page_head([
    'title' => 'Privacy Policy',
    'description' => 'How ' . brand_name() . ' collects, uses, and protects the information you share through this website.',
    'canonical' => 'privacy-policy',
    'breadcrumbs' => ['Home' => '/', 'Privacy Policy' => null],
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light">Legal</span>
    <h1 class="page-title">Privacy Policy</h1>
    <div class="breadcrumb-row"><a href="<?= e(url_for('')) ?>">Home</a> / <span>Privacy Policy</span></div>
  </div>
</section>

<section class="section">
  <div class="container prose-wrap">
    <p class="section-sub">We collect only the information you provide through our contact and quote forms — name, company, email, phone, and message content — to respond to your enquiry. We do not sell or share this data with third parties outside of fulfilling your request.</p>
    <h3 class="mt-5">Information We Collect</h3>
    <p class="section-sub mt-2">Contact form submissions, newsletter sign-ups, and standard analytics data such as pages visited and approximate location derived from IP address.</p>
    <h3 class="mt-5">How We Use It</h3>
    <p class="section-sub mt-2">To respond to quote and audit requests, send requested updates, and improve site content. We retain enquiry data for as long as needed to service your request.</p>
    <h3 class="mt-5">Your Rights</h3>
    <p class="section-sub mt-2">You may request access to, correction of, or deletion of your data at any time by emailing <span data-config="contact.emails.0.address"></span>.</p>
  </div>
</section>
<?php page_foot(['compactFooter' => true]); ?>
