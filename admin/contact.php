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
    <label class="full">Google Maps embed URL
      <textarea name="mapEmbedUrl" rows="2"><?= e($contact['mapEmbedUrl'] ?? '') ?></textarea>
    </label>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save Contact</button>
  </div>
</form>
<?php admin_footer(); ?>
