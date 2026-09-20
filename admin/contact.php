<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/lib/ui/layout.php';
require_login();

$contact = setting('contact', []);
$phonesText = '';
foreach (($contact['phones'] ?? []) as $p) {
    $phonesText .= ($p['label'] ?? '') . ' | ' . ($p['number'] ?? '') . "\n";
}
$emailsText = '';
foreach (($contact['emails'] ?? []) as $e) {
    $emailsText .= ($e['label'] ?? '') . ' | ' . ($e['address'] ?? '') . "\n";
}

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
    $contact = [
        'phones' => $phones,
        'emails' => $emails,
        'whatsapp' => post('whatsapp'),
        'workingHours' => post('workingHours'),
        'address' => [
            'line1' => post('address1'),
            'line2' => post('address2'),
        ],
        'mapEmbedUrl' => post('mapEmbedUrl'),
        'mapLabel' => post('mapLabel'),
        'mapEmbedUrl2' => post('mapEmbedUrl2'),
        'mapLabel2' => post('mapLabel2'),
        'mapEmbedUrl3' => post('mapEmbedUrl3'),
        'mapLabel3' => post('mapLabel3'),
        'mapEmbedUrl4' => post('mapEmbedUrl4'),
        'mapLabel4' => post('mapLabel4'),
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
    <label>Map 1 label
      <input type="text" name="mapLabel" value="<?= e($contact['mapLabel'] ?? '') ?>" placeholder="Bangalore Head Office">
    </label>
    <label class="full">Google Maps embed URL (map 1)
      <textarea name="mapEmbedUrl" rows="2"><?= e($contact['mapEmbedUrl'] ?? '') ?></textarea>
    </label>
    <label>Map 2 label
      <input type="text" name="mapLabel2" value="<?= e($contact['mapLabel2'] ?? '') ?>" placeholder="Bhubaneswar">
    </label>
    <label class="full">Google Maps embed URL (map 2)
      <textarea name="mapEmbedUrl2" rows="2"><?= e($contact['mapEmbedUrl2'] ?? '') ?></textarea>
    </label>
    <label>Map 3 label
      <input type="text" name="mapLabel3" value="<?= e($contact['mapLabel3'] ?? '') ?>" placeholder="Brahmapur">
    </label>
    <label class="full">Google Maps embed URL (map 3)
      <textarea name="mapEmbedUrl3" rows="2"><?= e($contact['mapEmbedUrl3'] ?? '') ?></textarea>
    </label>
    <label>Map 4 label
      <input type="text" name="mapLabel4" value="<?= e($contact['mapLabel4'] ?? '') ?>" placeholder="Paradeep">
    </label>
    <label class="full">Google Maps embed URL (map 4)
      <textarea name="mapEmbedUrl4" rows="2"><?= e($contact['mapEmbedUrl4'] ?? '') ?></textarea>
      <span class="hint">Paradeep can stay as a placeholder until the exact pin is provided.</span>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Contact</button>
  </div>
</form>
<?php admin_footer(); ?>
