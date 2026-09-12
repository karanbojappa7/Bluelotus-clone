<?php
declare(strict_types=1);
require_once __DIR__ . '/admin/bootstrap.php';

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
        <form id="quote-form" data-validate novalidate>
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
                <option>Fire Safety</option>
                <option>Road & Traffic Safety</option>
                <option>Industrial Safety</option>
                <option>Warehouse Safety</option>
                <option>Construction Safety</option>
                <option>Other / Not Sure</option>
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

<section class="map-section" aria-label="Location map">
  <iframe class="map-frame" data-map-embed title="Bluelotus Infrasafety location map" src="" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
</section>
<?php page_foot(); ?>
