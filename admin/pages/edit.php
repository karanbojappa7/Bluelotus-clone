<?php
declare(strict_types=1);
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../lib/ui/layout.php';
require_login();

$labels = page_labels();
$slug = isset($_GET['page']) ? trim((string) $_GET['page']) : '';
if (!isset($labels[$slug])) {
    flash('error', 'That page is not editable here.');
    redirect(admin_url('pages/index.php'));
}

if ($slug === 'home') {
    festival_expire();
}
$item = page_copy($slug);
$fields = page_editor_fields($slug);
$errors = [];
$linkProducts = all_products();
$linkCategories = all_categories();
$otherPosts = [];
foreach (setting_list('blog') as $other) {
    if (($other['slug'] ?? '') !== '') {
        $otherPosts[] = $other;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $next = $item;
    foreach ($fields as $field) {
        $key = (string) ($field['key'] ?? '');
        if ($key === '') {
            continue;
        }
        $type = (string) ($field['type'] ?? 'text');
        if ($type === 'check') {
            $next[$key] = isset($_POST[$key]) ? '1' : '0';
            continue;
        }
        if ($type === 'image') {
            try {
                $keep = (string) ($item[$key] ?? '');
                $next[$key] = (string) (save_uploaded_image($key, $keep !== '' ? $keep : null) ?? '');
            } catch (Throwable $e) {
                $errors[$key] = $e->getMessage();
            }
            continue;
        }
        if ($type === 'rich') {
            $next[$key] = (string) ($_POST[$key] ?? '');
            continue;
        }
        if ($type === 'approach-tiles') {
            $next[$key] = about_tiles_from_post();
            continue;
        }
        $next[$key] = post($key);
    }
    if ($slug === 'home' && !$errors) {
        $fromRaw = trim((string) ($next['festiveFrom'] ?? ''));
        $toRaw = trim((string) ($next['festiveTo'] ?? ''));
        $image = trim((string) ($next['festiveImage'] ?? ''));
        if ($fromRaw === '' && $toRaw === '') {
            if ($image !== '') {
                delete_uploaded_image($image);
            }
            $next['festiveImage'] = '';
            $next['festiveFrom'] = '';
            $next['festiveTo'] = '';
        } else {
            $from = festival_time($fromRaw);
            $to = festival_time($toRaw);
            if (!$from) {
                $errors['festiveFrom'] = 'Choose a start date and time.';
            }
            if (!$to) {
                $errors['festiveTo'] = 'Choose an end date and time.';
            } elseif ($from && $to <= $from) {
                $errors['festiveTo'] = 'The end must be after the start.';
            }
            if ($image === '') {
                $errors['festiveImage'] = 'Upload the festival picture.';
            }
            $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
            if (!$errors && $to && $now > $to) {
                delete_uploaded_image($image);
                $next['festiveImage'] = '';
                $next['festiveFrom'] = '';
                $next['festiveTo'] = '';
            }
        }
    }
    if (!$errors) {
        save_page_copy($slug, $next);
        export_site_config_js();
        flash('ok', $labels[$slug] . ' page saved.');
        redirect(admin_url('pages/edit.php?page=' . rawurlencode($slug)));
    }
    $item = $next;
}

admin_header($labels[$slug], 'pages', ['Pages' => 'pages/index.php']);
?>
<?php if ($errors): ?>
  <div class="flash flash--error" role="alert">Please fix the highlighted fields below.</div>
<?php endif; ?>
<form method="post" class="form-panel" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php if ($slug === 'contact'): ?>
    <p class="muted">Phone numbers, emails, WhatsApp, address, and maps are edited under <a href="<?= e(admin_url('contact.php')) ?>">Contact</a>.</p>
  <?php elseif ($slug === 'home'): ?>
    <p class="muted">Homepage hero slides are edited under <a href="<?= e(admin_url('hero/index.php')) ?>">Homepage banner</a>.</p>
  <?php elseif ($slug === 'products'): ?>
    <p class="muted">Individual category heroes are edited on each <a href="<?= e(admin_url('categories/index.php')) ?>">category</a>.</p>
  <?php elseif ($slug === 'careers'): ?>
    <p class="muted">Open roles are edited under <a href="<?= e(admin_url('jobs/index.php')) ?>">Careers</a>.</p>
  <?php endif; ?>
  <div class="form-grid">
    <?php foreach ($fields as $field): ?>
      <?php if (!empty($field['legend'])): ?>
        <p class="full muted" style="margin:1rem 0 0;font-weight:600;"><?= e((string) $field['legend']) ?></p>
        <?php continue; ?>
      <?php endif; ?>
      <?php
      $key = (string) ($field['key'] ?? '');
      $type = (string) ($field['type'] ?? 'text');
      $label = (string) ($field['label'] ?? $key);
      $value = (string) ($item[$key] ?? '');
      $full = !empty($field['full']) || $type === 'textarea' || $type === 'rich' || $type === 'image';
      ?>
      <?php if ($type === 'image'): ?>
        <label class="<?= field_class($errors, $key, $full) ?>"><?= e($label) ?>
          <input type="file" name="<?= e($key) ?>" accept="image/jpeg,image/png,image/webp,image/gif">
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
          <?= field_msg($errors, $key) ?>
        </label>
        <?php if ($value !== ''): ?>
          <div class="full file-preview">
            <img src="<?= e(url_for($value)) ?>" alt="Current <?= e($label) ?>">
          </div>
        <?php endif; ?>
      <?php elseif ($type === 'rich'): ?>
        <div class="<?= field_class($errors, $key, true) ?>">
          <label for="pageBody"><?= e($label) ?></label>
          <div class="editor-toolbar" data-rich-toolbar>
            <button type="button" class="editor-btn" data-blog-heading="1">H1</button>
            <button type="button" class="editor-btn" data-blog-heading="2">H2</button>
            <button type="button" class="editor-btn" data-blog-heading="3">H3</button>
            <span class="editor-toolbar-sep" aria-hidden="true"></span>
            <label class="editor-link">
              <span class="visually-hidden">Link target</span>
              <select id="blogLinkTarget">
                <option value="">Internal link…</option>
                <optgroup label="Pages">
                  <option value="page:home" data-label="Home">Homepage</option>
                  <option value="page:about" data-label="About us">About us</option>
                  <option value="page:products" data-label="products">All products</option>
                  <option value="page:faq" data-label="FAQs">FAQs</option>
                  <option value="page:blog" data-label="blog">Blog</option>
                  <option value="page:careers" data-label="Careers">Careers</option>
                  <option value="page:contact" data-label="contact">Contact</option>
                </optgroup>
                <?php if ($linkCategories): ?>
                  <optgroup label="Categories">
                    <?php foreach ($linkCategories as $cat): ?>
                      <option value="category:<?= e($cat['id']) ?>" data-label="<?= e($cat['name']) ?>"><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if ($linkProducts): ?>
                  <optgroup label="Products">
                    <?php foreach ($linkProducts as $product): ?>
                      <option value="product:<?= e($product['slug']) ?>" data-label="<?= e($product['name']) ?>"><?= e($product['name']) ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
                <?php if ($otherPosts): ?>
                  <optgroup label="Other posts">
                    <?php foreach ($otherPosts as $other): ?>
                      <option value="blog:<?= e($other['slug']) ?>" data-label="<?= e($other['title'] ?? '') ?>"><?= e($other['title'] ?? '') ?></option>
                    <?php endforeach; ?>
                  </optgroup>
                <?php endif; ?>
              </select>
            </label>
            <button type="button" class="btn btn-secondary" data-insert-blog-link>Insert link</button>
          </div>
          <textarea name="<?= e($key) ?>" id="pageBody" rows="16" data-rich-body><?= e($value) ?></textarea>
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
        </div>
      <?php elseif ($type === 'datetime'): ?>
        <label class="<?= field_class($errors, $key, $full) ?>"><?= e($label) ?>
          <input type="datetime-local" name="<?= e($key) ?>" value="<?= e(festival_time($value) ? $value : '') ?>">
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
          <?= field_msg($errors, $key) ?>
        </label>
      <?php elseif ($type === 'check'): ?>
        <label class="full seo-toggle">
          <span>
            <input type="checkbox" name="<?= e($key) ?>" value="1" <?= $value === '1' ? 'checked' : '' ?>>
            <?= e($label) ?>
          </span>
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
        </label>
      <?php elseif ($type === 'approach-tiles'): ?>
        <?php $approachTiles = about_approach_tiles($item); ?>
        <div class="full">
          <p class="muted">Add as many cards as you need. Leave a card blank to skip it.</p>
          <div data-approach-list>
            <?php foreach ($approachTiles as $tile): ?>
              <?php $icon = (string) ($tile['icon'] ?? 'consult'); ?>
              <div class="form-grid" data-approach-card style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
                <label>Icon
                  <select name="tileIcon[]">
                    <?php foreach (about_approach_icons() as $iconKey => $iconLabel): ?>
                      <option value="<?= e($iconKey) ?>" <?= $icon === $iconKey ? 'selected' : '' ?>><?= e($iconLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label>Title
                  <input type="text" name="tileTitle[]" value="<?= e((string) ($tile['title'] ?? '')) ?>">
                </label>
                <label class="full">Text
                  <textarea name="tileBody[]" rows="3"><?= e((string) ($tile['body'] ?? '')) ?></textarea>
                </label>
                <div class="full"><button class="btn btn-danger btn-sm" type="button" data-remove-approach>Remove</button></div>
              </div>
            <?php endforeach; ?>
          </div>
          <template id="approach-tile-template">
            <div class="form-grid" data-approach-card style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid var(--line)">
              <label>Icon
                <select name="tileIcon[]">
                  <?php foreach (about_approach_icons() as $iconKey => $iconLabel): ?>
                    <option value="<?= e($iconKey) ?>"><?= e($iconLabel) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <label>Title
                <input type="text" name="tileTitle[]" value="">
              </label>
              <label class="full">Text
                <textarea name="tileBody[]" rows="3"></textarea>
              </label>
              <div class="full"><button class="btn btn-danger btn-sm" type="button" data-remove-approach>Remove</button></div>
            </div>
          </template>
          <button class="btn btn-secondary" type="button" data-add-approach>Add card</button>
        </div>
      <?php elseif ($type === 'textarea'): ?>
        <label class="<?= field_class($errors, $key, $full) ?>"><?= e($label) ?>
          <textarea name="<?= e($key) ?>" rows="3"><?= e($value) ?></textarea>
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
        </label>
      <?php else: ?>
        <label class="<?= field_class($errors, $key, $full) ?>"><?= e($label) ?>
          <input type="text" name="<?= e($key) ?>" value="<?= e($value) ?>">
          <?php if (!empty($field['hint'])): ?>
            <span class="hint"><?= e((string) $field['hint']) ?></span>
          <?php endif; ?>
        </label>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
  <div class="form-actions">
    <button class="btn" type="submit">Save <?= e($labels[$slug]) ?></button>
    <a class="btn btn-secondary" href="<?= e(admin_url('pages/index.php')) ?>">Back</a>
  </div>
</form>
<script>
document.querySelectorAll("[data-add-approach]").forEach(function (button) {
  button.addEventListener("click", function () {
    var list = document.querySelector("[data-approach-list]");
    var template = document.getElementById("approach-tile-template");
    if (!list || !template) return;
    list.appendChild(template.content.cloneNode(true));
  });
});
document.addEventListener("click", function (event) {
  var button = event.target.closest("[data-remove-approach]");
  if (!button) return;
  var card = button.closest("[data-approach-card]");
  if (card) card.remove();
});
</script>
<?php admin_footer(); ?>
