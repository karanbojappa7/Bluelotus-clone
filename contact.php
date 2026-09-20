<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

$categories = db_ready() ? all_categories() : [];
$services = db_ready() ? all_services() : [];
$contact = db_ready() ? (setting('contact', []) ?: []) : [];
$maps = contact_maps($contact);
$sent = isset($_GET['sent']);
$sendError = isset($_GET['error']);

page_head([
    'title' => 'Contact Us',
    'description' => 'Call, email, or message ' . brand_name() . ' in Berhampur, Odisha for certified safety equipment supply, installation, and bulk project quotes.',
    'canonical' => 'contact',
    'breadcrumbs' => ['Home' => '/', 'Contact Us' => null],
]);
?>
<section class="page-header">
  <div class="container">
    <span class="eyebrow eyebrow--light">Get In Touch</span>
    <h1 class="page-title">Tell us about your site, we'll take it from there.</h1>
    <div class="breadcrumb-row"><a href="<?= e(url_for('')) ?>">Home</a> / <span>Contact Us</span></div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="grid grid-2 split split--start">
      <div data-reveal>
        <span class="eyebrow">Contact Details</span>
        <h2 class="section-title">Reach us directly</h2>
        <div class="mt-4" data-render="contact-cards"></div>
      </div>
      <div class="tile tile--static" data-reveal>
        <span class="eyebrow">Send a Message</span>
        <h2 class="section-title mb-4">Request a quote or audit</h2>
        <?php if ($sent): ?>
          <p class="form-feedback is-success" role="status">Thank you. Our team will get back to you within one business day.</p>
        <?php elseif ($sendError): ?>
          <p class="form-feedback is-error" role="alert">We could not send that just now. Please try again or call us.</p>
        <?php endif; ?>
        <form id="quote-form" method="post" action="<?= e(url_for('contact-submit.php')) ?>" data-validate novalidate>
          <?= csrf_field() ?>
          <div class="hp" aria-hidden="true">
            <label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
          </div>
          <div class="form-grid">
            <div>
              <label class="form-label" for="fullName">Full Name</label>
              <input class="form-control" type="text" id="fullName" name="fullName" required>
            </div>
            <div>
              <label class="form-label" for="company">Company</label>
              <input class="form-control" type="text" id="company" name="company">
            </div>
            <div>
              <label class="form-label" for="email">Email</label>
              <input class="form-control" type="email" id="email" name="email" required>
            </div>
            <div>
              <label class="form-label" for="phone">Phone</label>
              <input class="form-control" type="tel" id="phone" name="phone" required>
            </div>
            <div class="full">
              <label class="form-label" for="category">Category of Interest</label>
              <select class="form-control" id="category" name="category">
                <?php foreach ($categories as $cat): ?>
                  <option value="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                <?php endforeach; ?>
                <?php foreach ($services as $service): ?>
                  <option value="<?= e($service['name']) ?>"><?= e($service['name']) ?> (service)</option>
                <?php endforeach; ?>
                <option value="Other / Not Sure">Other / Not Sure</option>
              </select>
            </div>
            <div class="full">
              <label class="form-label" for="message">Message</label>
              <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary mt-4">Submit Request</button>
          <span data-form-feedback></span>
        </form>
      </div>
    </div>
  </div>
</section>

<?php if ($maps): ?>
<section class="map-picker" data-map-picker aria-label="Location maps">
  <?php if (count($maps) > 1): ?>
  <div class="map-tabs-wrap">
    <div class="container">
      <nav class="map-tabs" role="tablist" aria-label="Choose a location">
        <?php foreach ($maps as $i => $map): ?>
          <button type="button" role="tab" id="map-tab-<?= $i ?>" data-map-tab="<?= $i ?>"
                  class="<?= $i === 0 ? 'is-active' : '' ?>"
                  aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
                  aria-controls="map-panel-<?= $i ?>"><?= e($map['label']) ?></button>
        <?php endforeach; ?>
      </nav>
    </div>
  </div>
  <?php endif; ?>
  <div class="map-stage">
    <?php foreach ($maps as $i => $map): ?>
      <iframe class="map-frame<?= $i === 0 ? ' is-active' : '' ?>" id="map-panel-<?= $i ?>"
              title="<?= e($map['label']) ?>" src="<?= e($map['src']) ?>"
              allowfullscreen="" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
              referrerpolicy="strict-origin-when-cross-origin"></iframe>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
<?php page_foot(['skipQuoteModal' => true]); ?>
