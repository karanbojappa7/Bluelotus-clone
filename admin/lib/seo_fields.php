<?php
declare(strict_types=1);

function seo_field_defaults(): array
{
    return [
        'metaTitle' => '',
        'metaDescription' => '',
        'metaKeywords' => '',
        'canonical' => '',
        'ogImage' => '',
        'noindex' => false,
        'brand' => '',
        'sku' => '',
        'gtin' => '',
        'mpn' => '',
        'condition' => '',
        'availability' => '',
        'price' => '',
        'currency' => '',
        'author' => '',
        'schemaType' => '',
    ];
}

function seo_fields_from_post(array $keys): array
{
    $out = [];
    foreach ($keys as $key) {
        $out[$key] = $key === 'noindex' ? isset($_POST['noindex']) : post($key);
    }
    return $out;
}

function availability_options(): array
{
    return [
        '' => 'In stock (default)',
        'InStock' => 'In stock',
        'OutOfStock' => 'Out of stock',
        'PreOrder' => 'Pre-order',
        'BackOrder' => 'Back-order',
        'Discontinued' => 'Discontinued',
    ];
}

function condition_options(): array
{
    return [
        '' => 'New (default)',
        'NewCondition' => 'New',
        'RefurbishedCondition' => 'Refurbished',
        'UsedCondition' => 'Used',
    ];
}

function article_type_options(): array
{
    return [
        '' => 'BlogPosting (default)',
        'Article' => 'Article',
        'NewsArticle' => 'NewsArticle',
        'TechArticle' => 'TechArticle',
    ];
}

function render_seo_panel(array $v, array $opts): void
{
    $v += seo_field_defaults();
    $kind = $opts['kind'] ?? 'page';
    $previewBase = $opts['previewBase'] ?? '/';
    $slug = $opts['slug'] ?? '';
    $fallbackTitle = (string) ($opts['fallbackTitle'] ?? '');
    $fallbackDesc = (string) ($opts['fallbackDescription'] ?? '');
    $images = $opts['images'] ?? [];
    $brandDefault = brand_name();
    ?>
<section class="seo-panel" data-seo-panel>
  <header class="seo-panel-head">
    <h2>Search &amp; social</h2>
    <p class="hint">Every field here is optional. Left blank, the page falls back to the content above &mdash; nothing is ever left without a title or description.</p>
  </header>

  <div class="serp-preview" data-serp
       data-base="<?= e(seo_url(ltrim($previewBase, '/'))) ?>"
       data-slug="<?= e($slug) ?>"
       data-fallback-title="<?= e($fallbackTitle) ?>"
       data-fallback-desc="<?= e($fallbackDesc) ?>"
       data-brand="<?= e($brandDefault) ?>">
    <span class="serp-label">Google result preview</span>
    <div class="serp-url" data-serp-url></div>
    <div class="serp-title" data-serp-title></div>
    <div class="serp-desc" data-serp-desc></div>
  </div>

  <div class="form-grid">
    <label class="full">Meta title
      <input type="text" name="metaTitle" value="<?= e((string) $v['metaTitle']) ?>" data-maxlen="60" data-serp-input="title"
             placeholder="<?= e($fallbackTitle !== '' ? $fallbackTitle : 'Falls back to the name above') ?>">
    </label>
    <label class="full">Meta description
      <textarea name="metaDescription" rows="2" data-maxlen="158" data-serp-input="desc"
                placeholder="<?= e($fallbackDesc !== '' ? truncate($fallbackDesc, 110) : 'Falls back to the summary above') ?>"><?= e((string) $v['metaDescription']) ?></textarea>
    </label>
    <label class="full">Focus keywords
      <input type="text" name="metaKeywords" value="<?= e((string) $v['metaKeywords']) ?>"
             placeholder="safety cones, lane barricades, road safety odisha">
      <span class="hint">Comma separated. Used for the keywords meta tag and product schema.</span>
    </label>
    <label class="full">Canonical URL override
      <input type="url" name="canonical" value="<?= e((string) $v['canonical']) ?>" placeholder="Leave blank &mdash; the correct URL is generated automatically">
      <span class="hint">Only set this if this content is a duplicate of a page elsewhere.</span>
    </label>

    <?php if ($images): ?>
    <label class="full">Social share image
      <select name="ogImage">
        <option value="">First image (default)</option>
        <?php foreach ($images as $img): ?>
          <option value="<?= e($img) ?>" <?= $v['ogImage'] === $img ? 'selected' : '' ?>><?= e(basename($img)) ?></option>
        <?php endforeach; ?>
      </select>
      <span class="hint">Shown when the page is shared on WhatsApp, LinkedIn, or Facebook.</span>
    </label>
    <?php endif; ?>

    <label class="full seo-toggle">
      <span>
        <input type="checkbox" name="noindex" value="1" <?= $v['noindex'] ? 'checked' : '' ?>>
        Hide from search engines
      </span>
      <span class="hint">Adds <code>noindex</code> and removes the page from the sitemap. Use for drafts or retired items.</span>
    </label>
  </div>

  <?php if ($kind === 'product'): ?>
  <h3 class="seo-sub">Product structured data</h3>
  <p class="hint" style="margin:-0.35rem 0 0.9rem">
    Sent to Google as <code>Product</code> schema. Filling brand, SKU, and availability makes this product eligible for
    rich results with a product card in search.
  </p>
  <div class="form-grid">
    <label>Brand
      <input type="text" name="brand" value="<?= e((string) $v['brand']) ?>" placeholder="<?= e($brandDefault) ?>">
    </label>
    <label>SKU
      <input type="text" name="sku" value="<?= e((string) $v['sku']) ?>" placeholder="Defaults to the slug">
    </label>
    <label>GTIN / EAN / UPC
      <input type="text" name="gtin" value="<?= e((string) $v['gtin']) ?>">
    </label>
    <label>MPN
      <input type="text" name="mpn" value="<?= e((string) $v['mpn']) ?>">
    </label>
    <label>Availability
      <select name="availability">
        <?php foreach (availability_options() as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $v['availability'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Condition
      <select name="condition">
        <?php foreach (condition_options() as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $v['condition'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Price
      <input type="text" name="price" value="<?= e((string) $v['price']) ?>" inputmode="decimal" placeholder="Leave blank to hide price">
      <span class="hint">Numbers only, e.g. 1250. Omit if you quote on request.</span>
    </label>
    <label>Currency
      <input type="text" name="currency" value="<?= e((string) $v['currency']) ?>" maxlength="3" placeholder="INR">
    </label>
  </div>
  <?php endif; ?>

  <?php if ($kind === 'article'): ?>
  <h3 class="seo-sub">Article structured data</h3>
  <div class="form-grid">
    <label>Schema type
      <select name="schemaType">
        <?php foreach (article_type_options() as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= $v['schemaType'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Author
      <input type="text" name="author" value="<?= e((string) $v['author']) ?>" placeholder="<?= e($brandDefault) ?>">
    </label>
  </div>
  <?php endif; ?>
</section>
<?php
}
