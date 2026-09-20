<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$contact = setting('contact', []);
if (!is_array($contact)) {
    $contact = [];
}
$phonesText = '';
foreach (($contact['phones'] ?? []) as $p) {
    $phonesText .= ($p['label'] ?? '') . ' | ' . ($p['number'] ?? '') . "\n";
}
$emailsText = '';
foreach (($contact['emails'] ?? []) as $e) {
    $emailsText .= ($e['label'] ?? '') . ' | ' . ($e['address'] ?? '') . "\n";
}
$mapRows = contact_map_editor_rows($contact);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $phones = [];
    foreach (parse_labeled_lines(post('phones')) as $parts) {
        $phones[] = ['label' => $parts[0], 'number' => $parts[1]];
    }
    $emails = [];
    foreach (parse_labeled_lines(post('emails')) as $parts) {
        $emails[] = ['label' => $parts[0], 'address' => $parts[1]];
    }
    $maps = [];
    $labels = $_POST['map_label'] ?? [];
    $urls = $_POST['map_url'] ?? [];
    if (is_array($labels) && is_array($urls)) {
        $count = max(count($labels), count($urls));
        for ($i = 0; $i < $count; $i++) {
            $label = trim((string) ($labels[$i] ?? ''));
            $url = maps_embed_src((string) ($urls[$i] ?? ''));
            if ($label === '' && $url === '') {
                continue;
            }
            $maps[] = [
                'label' => $label !== '' ? $label : ('Location ' . (count($maps) + 1)),
                'url' => $url,
            ];
        }
    }
    $contact = [
        'phones' => $phones,
        'emails' => $emails,
        'whatsapp' => post('whatsapp'),
        'workingHours' => post('workingHours'),
        'address' => [
            'line1' => post('address1'),
            'line2' => post('address2'),
        ],
        'maps' => $maps,
        'mapLabel' => $maps[0]['label'] ?? '',
        'mapEmbedUrl' => $maps[0]['url'] ?? '',
        'mapLabel2' => $maps[1]['label'] ?? '',
        'mapEmbedUrl2' => $maps[1]['url'] ?? '',
        'mapLabel3' => $maps[2]['label'] ?? '',
        'mapEmbedUrl3' => $maps[2]['url'] ?? '',
        'mapLabel4' => $maps[3]['label'] ?? '',
        'mapEmbedUrl4' => $maps[3]['url'] ?? '',
    ];
    save_setting('contact', $contact);
    export_site_config_js();
    flash('ok', 'Contact details saved.');
    redirect('contact.php');
}

admin_header('Contact', 'contact');
?>
<form method="post" class="form-panel">
  <?= csrf_field() ?>
  <div class="form-grid">
    <label class="full">Phones <span class="hint">One per line: Label | +91 …</span>
      <textarea name="phones" rows="4"><?= e(trim($phonesText)) ?></textarea>
    </label>
    <label class="full">Emails <span class="hint">One per line: Label | email@domain.com</span>
      <textarea name="emails" rows="3"><?= e(trim($emailsText)) ?></textarea>
    </label>
    <label>WhatsApp number
      <input type="text" name="whatsapp" value="<?= e($contact['whatsapp'] ?? '') ?>">
      <span class="hint">Digits with country code, e.g. +917483394208</span>
    </label>
    <label>Working hours
      <input type="text" name="workingHours" value="<?= e($contact['workingHours'] ?? 'Mon – Sat, 9:00 AM – 7:00 PM') ?>">
    </label>
    <label class="full">Address line 1
      <input type="text" name="address1" value="<?= e($contact['address']['line1'] ?? '') ?>">
    </label>
    <label class="full">Address line 2
      <input type="text" name="address2" value="<?= e($contact['address']['line2'] ?? '') ?>">
    </label>
    <div class="full">
      <div class="map-rows-head">
        <strong>Locations / maps</strong>
        <span class="hint">Add as many offices as you need. Paste the Google Maps embed URL, or the full iframe.</span>
      </div>
      <div class="map-rows" data-map-rows>
        <?php foreach ($mapRows as $i => $row): ?>
          <div class="map-row" data-map-row>
            <div class="map-row-head">
              <span>Location <?= $i + 1 ?></span>
              <button type="button" class="btn btn-secondary btn-sm" data-map-remove>Remove</button>
            </div>
            <label>Tab label
              <input type="text" name="map_label[]" value="<?= e((string) ($row['label'] ?? '')) ?>" placeholder="Bangalore Head Office">
            </label>
            <label>Google Maps embed URL
              <textarea name="map_url[]" rows="2" placeholder="https://www.google.com/maps/embed?pb=…"><?= e((string) ($row['url'] ?? '')) ?></textarea>
            </label>
          </div>
        <?php endforeach; ?>
      </div>
      <button type="button" class="btn btn-secondary mt-2" data-map-add>Add location</button>
    </div>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Contact</button>
  </div>
</form>
<template data-map-row-template>
  <div class="map-row" data-map-row>
    <div class="map-row-head">
      <span>New location</span>
      <button type="button" class="btn btn-secondary btn-sm" data-map-remove>Remove</button>
    </div>
    <label>Tab label
      <input type="text" name="map_label[]" value="" placeholder="City or office name">
    </label>
    <label>Google Maps embed URL
      <textarea name="map_url[]" rows="2" placeholder="https://www.google.com/maps/embed?pb=…"></textarea>
    </label>
  </div>
</template>
<?php admin_footer(); ?>
