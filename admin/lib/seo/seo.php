<?php
declare(strict_types=1);

function seo_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = is_array(setting('seo', [])) ? setting('seo', []) : [];
    }
    return $cache;
}

function seo_company(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = is_array(setting('company', [])) ? setting('company', []) : [];
    }
    return $cache;
}

function seo_contact(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = is_array(setting('contact', [])) ? setting('contact', []) : [];
    }
    return $cache;
}

function seo_domain(): string
{
    $domain = trim((string) (seo_settings()['domain'] ?? ''));
    if ($domain === '') {
        $scheme = cms_is_https() ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $domain = $scheme . '://' . $host;
    }
    return rtrim($domain, '/');
}

function seo_base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
    $pos = strpos($script, '/admin/');
    $dir = $pos !== false ? substr($script, 0, $pos) : dirname($script);
    $dir = rtrim(str_replace('\\', '/', (string) $dir), '/');
    $base = ($dir === '' || $dir === '/' || $dir === '.') ? '' : $dir;
    return $base;
}

function url_for(string $path = ''): string
{
    $path = ltrim($path, '/');
    return (seo_base_path() ?: '') . '/' . $path;
}

function seo_url(string $path = ''): string
{
    $base = trim(seo_base_path(), '/');
    $path = ltrim($path, '/');
    $suffix = $base !== '' ? $base . '/' . $path : $path;
    return seo_domain() . '/' . ltrim($suffix, '/');
}

function product_slug_path(string $slug): string
{
    return 'product/' . rawurlencode($slug);
}

function category_slug_path(string $id): string
{
    return 'products/' . rawurlencode($id);
}

function post_slug_path(string $slug): string
{
    return 'blog/' . rawurlencode($slug);
}

function product_path(string $slug): string
{
    return url_for(product_slug_path($slug));
}

function category_path(string $id): string
{
    return url_for(category_slug_path($id));
}

function post_path(string $slug): string
{
    return url_for(post_slug_path($slug));
}

function brand_name(): string
{
    return (string) (seo_company()['name'] ?? 'Bluelotus Infrasafety');
}

function seo_image(?string $image = null): string
{
    $image = trim((string) $image);
    if ($image === '') {
        $default = trim((string) (seo_settings()['ogImage'] ?? ''));
        $image = $default !== '' ? $default : 'assets/img/og-cover.jpg';
    }
    if (preg_match('#^https?://#i', $image)) {
        return $image;
    }
    return seo_url($image);
}

function meta_text(?string $value, int $limit, string $fallback = ''): string
{
    $value = trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    if ($value === '') {
        $value = trim(preg_replace('/\s+/', ' ', $fallback) ?? '');
    }
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strlen') && mb_strlen($value) > $limit) {
        return rtrim(mb_substr($value, 0, $limit - 1)) . '…';
    }
    if (!function_exists('mb_strlen') && strlen($value) > $limit) {
        return rtrim(substr($value, 0, $limit - 1)) . '…';
    }
    return $value;
}

function json_ld(array $nodes): string
{
    $nodes = array_values(array_filter($nodes));
    if (!$nodes) {
        return '';
    }
    $payload = count($nodes) === 1 ? $nodes[0] : ['@context' => 'https://schema.org', '@graph' => $nodes];
    $json = json_encode(
        $payload,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
    return '<script type="application/ld+json">' . $json . '</script>';
}

function schema_organization(): array
{
    $contact = seo_contact();
    $address = $contact['address'] ?? [];
    $phones = $contact['phones'] ?? [];
    $emails = $contact['emails'] ?? [];
    $social = setting('social', []);

    $node = [
        '@type' => 'LocalBusiness',
        '@id' => seo_url('#organization'),
        'name' => brand_name(),
        'url' => seo_url(),
        'description' => (string) (seo_settings()['defaultDescription'] ?? ''),
        'image' => seo_image(),
        'logo' => seo_image((string) (seo_company()['logo'] ?? 'assets/img/logo.png')),
    ];
    if (!empty($phones[0]['number'])) {
        $node['telephone'] = (string) $phones[0]['number'];
    }
    if (!empty($emails[0]['address'])) {
        $node['email'] = (string) $emails[0]['address'];
    }
    if ($address) {
        $node['address'] = array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => trim((string) ($address['line1'] ?? '')),
            'addressLocality' => (string) ($address['city'] ?? 'Berhampur'),
            'addressRegion' => (string) ($address['state'] ?? 'Odisha'),
            'postalCode' => (string) ($address['postalCode'] ?? '760010'),
            'addressCountry' => 'IN',
        ]);
    }
    if (!empty($contact['workingHours'])) {
        $node['openingHours'] = (string) $contact['workingHours'];
    }
    $sameAs = array_values(array_filter(is_array($social) ? $social : []));
    if ($sameAs) {
        $node['sameAs'] = $sameAs;
    }
    if (!empty($contact['foundedYear']) || !empty(seo_company()['foundedYear'])) {
        $node['foundingDate'] = (string) (seo_company()['foundedYear'] ?? $contact['foundedYear']);
    }
    return $node;
}

function schema_website(): array
{
    return [
        '@type' => 'WebSite',
        '@id' => seo_url('#website'),
        'url' => seo_url(),
        'name' => brand_name(),
        'publisher' => ['@id' => seo_url('#organization')],
        'potentialAction' => [
            '@type' => 'SearchAction',
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => seo_url('products') . '?q={search_term_string}',
            ],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function schema_breadcrumbs(array $crumbs): ?array
{
    if (count($crumbs) < 2) {
        return null;
    }
    $items = [];
    $position = 1;
    foreach ($crumbs as $label => $path) {
        $item = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => (string) $label,
        ];
        if ($path !== null && $path !== '') {
            $item['item'] = seo_url(ltrim((string) $path, '/'));
        }
        $items[] = $item;
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function schema_faq(array $faqs): ?array
{
    $entities = [];
    foreach ($faqs as $faq) {
        $q = trim((string) ($faq['q'] ?? ''));
        $a = trim((string) ($faq['a'] ?? ''));
        if ($q === '' || $a === '') {
            continue;
        }
        $entities[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
    return $entities ? ['@type' => 'FAQPage', 'mainEntity' => $entities] : null;
}

function product_keywords(array $product): string
{
    $explicit = trim((string) ($product['metaKeywords'] ?? ''));
    if ($explicit !== '') {
        return $explicit;
    }
    return implode(', ', array_values(array_filter((array) ($product['tags'] ?? []))));
}

function schema_product(array $product, ?array $category): array
{
    $url = seo_url(product_slug_path((string) $product['slug']));
    $images = [];
    foreach (($product['images'] ?? []) as $img) {
        $images[] = seo_image((string) $img);
    }
    if (!$images) {
        $images[] = seo_image();
    }

    $node = [
        '@type' => 'Product',
        '@id' => $url . '#product',
        'name' => (string) $product['name'],
        'description' => meta_text($product['short'] ?? '', 300, (string) ($product['description'] ?? '')),
        'image' => $images,
        'url' => $url,
        'sku' => ($product['sku'] ?? '') !== '' ? (string) $product['sku'] : (string) $product['slug'],
        'brand' => ['@type' => 'Brand', 'name' => ($product['brand'] ?? '') !== '' ? (string) $product['brand'] : brand_name()],
    ];

    if (($product['gtin'] ?? '') !== '') {
        $node['gtin'] = (string) $product['gtin'];
    }
    if (($product['mpn'] ?? '') !== '') {
        $node['mpn'] = (string) $product['mpn'];
    }
    if ($category) {
        $node['category'] = (string) $category['name'];
    }
    $keywords = product_keywords($product);
    if ($keywords !== '') {
        $node['keywords'] = $keywords;
    }

    $offer = [
        '@type' => 'Offer',
        'url' => $url,
        'availability' => 'https://schema.org/' . (($product['availability'] ?? '') !== '' ? $product['availability'] : 'InStock'),
        'itemCondition' => 'https://schema.org/' . (($product['condition'] ?? '') !== '' ? $product['condition'] : 'NewCondition'),
        'priceCurrency' => ($product['currency'] ?? '') !== '' ? strtoupper((string) $product['currency']) : 'INR',
        'seller' => ['@id' => seo_url('#organization')],
    ];
    if (($product['price'] ?? '') !== '' && is_numeric($product['price'])) {
        $offer['price'] = (string) $product['price'];
    } else {
        $offer['availableAtOrFrom'] = ['@id' => seo_url('#organization')];
    }
    $node['offers'] = $offer;

    return $node;
}

function schema_url_list(string $listName, array $rows, callable $mapper, int $offset = 0): ?array
{
    if (!$rows) {
        return null;
    }
    $items = [];
    foreach (array_values($rows) as $i => $row) {
        $mapped = $mapper($row);
        if (empty($mapped['name']) || empty($mapped['url'])) {
            continue;
        }
        $items[] = [
            '@type' => 'ListItem',
            'position' => $offset + $i + 1,
            'name' => $mapped['name'],
            'url' => $mapped['url'],
        ];
    }
    if (!$items) {
        return null;
    }
    return ['@type' => 'ItemList', 'name' => $listName, 'numberOfItems' => count($items), 'itemListElement' => $items];
}

function schema_item_list(array $products, string $listName): ?array
{
    return schema_url_list($listName, $products, static fn (array $p) => [
        'name' => (string) $p['name'],
        'url' => seo_url(product_slug_path((string) $p['slug'])),
    ]);
}

function schema_article(array $post): array
{
    $url = seo_url(post_slug_path((string) $post['slug']));
    $author = trim((string) ($post['author'] ?? ''));
    $node = array_filter([
        '@type' => ($post['schemaType'] ?? '') !== '' ? (string) $post['schemaType'] : 'BlogPosting',
        '@id' => $url . '#article',
        'headline' => meta_text($post['title'] ?? '', 110),
        'description' => meta_text($post['metaDescription'] ?? '', 300, (string) ($post['excerpt'] ?? '')),
        'image' => seo_image((string) ($post['image'] ?? '')),
        'datePublished' => (string) ($post['date'] ?? ''),
        'dateModified' => (string) ($post['updated'] ?? $post['date'] ?? ''),
        'mainEntityOfPage' => $url,
        'author' => $author !== ''
            ? ['@type' => 'Person', 'name' => $author]
            : ['@type' => 'Organization', 'name' => brand_name()],
        'publisher' => ['@id' => seo_url('#organization')],
    ]);
    $keywords = trim((string) ($post['metaKeywords'] ?? ''));
    if ($keywords !== '') {
        $node['keywords'] = $keywords;
    }
    return $node;
}

function seo_overrides(array $item): array
{
    $out = [];
    if (trim((string) ($item['metaKeywords'] ?? '')) !== '') {
        $out['keywords'] = trim((string) $item['metaKeywords']);
    }
    if (trim((string) ($item['canonical'] ?? '')) !== '') {
        $out['canonicalUrl'] = trim((string) $item['canonical']);
    }
    if (trim((string) ($item['ogImage'] ?? '')) !== '') {
        $out['image'] = trim((string) $item['ogImage']);
    }
    if (!empty($item['noindex'])) {
        $out['robots'] = 'noindex, follow';
    }
    return $out;
}

function analytics_settings(): array
{
    static $cache = null;
    if ($cache === null) {
        $cache = is_array(setting('analytics', [])) ? setting('analytics', []) : [];
    }
    return $cache;
}

function analytics_head(): string
{
    $a = analytics_settings();
    $html = '';

    $gaId = trim((string) ($a['gaId'] ?? ''));
    if ($gaId !== '') {
        $html .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . e($gaId) . '"></script>'
            . '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}'
            . 'gtag("js",new Date());gtag("config",' . json_encode($gaId) . ');</script>';
    }

    $gtmId = trim((string) ($a['gtmId'] ?? ''));
    if ($gtmId !== '') {
        $html .= '<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({"gtm.start":new Date().getTime(),event:"gtm.js"});'
            . 'var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!="dataLayer"?"&l="+l:"";'
            . 'j.async=true;j.src="https://www.googletagmanager.com/gtm.js?id="+i+dl;f.parentNode.insertBefore(j,f);'
            . '})(window,document,"script","dataLayer",' . json_encode($gtmId) . ');</script>';
    }

    $pixelId = trim((string) ($a['metaPixelId'] ?? ''));
    if ($pixelId !== '') {
        $html .= '<script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?'
            . 'n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;'
            . 'n.push=n;n.loaded=!0;n.version="2.0";n.queue=[];t=b.createElement(e);t.async=!0;'
            . 't.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'
            . '"script","https://connect.facebook.net/en_US/fbevents.js");fbq("init",' . json_encode($pixelId) . ');'
            . 'fbq("track","PageView");</script>'
            . '<noscript><img height="1" width="1" style="display:none" alt=""'
            . ' src="https://www.facebook.com/tr?id=' . e($pixelId) . '&ev=PageView&noscript=1"></noscript>';
    }

    $custom = trim((string) ($a['customHead'] ?? ''));
    if ($custom !== '') {
        $html .= "\n" . $custom . "\n";
    }

    return $html;
}

function analytics_body(): string
{
    $gtmId = trim((string) (analytics_settings()['gtmId'] ?? ''));
    if ($gtmId === '') {
        return '';
    }
    return '<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=' . e($gtmId)
        . '" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>';
}

function page_head(array $page): void
{
    $seo = seo_settings();
    $brand = brand_name();
    $rawTitle = trim((string) ($page['title'] ?? ''));
    $fullTitle = trim((string) ($page['fullTitle'] ?? ''));
    if ($fullTitle === '') {
        $fullTitle = $rawTitle !== '' ? $rawTitle . ' | ' . $brand : $brand . ' | ' . (string) ($seo['defaultTitle'] ?? '');
    }

    $description = meta_text($page['description'] ?? '', 158, (string) ($seo['defaultDescription'] ?? ''));
    $keywords = trim((string) ($page['keywords'] ?? ($seo['keywords'] ?? '')));
    $canonical = !empty($page['canonicalUrl'])
        ? (string) $page['canonicalUrl']
        : seo_url(ltrim((string) ($page['canonical'] ?? ''), '/'));
    $image = seo_image($page['image'] ?? null);
    $robots = (string) ($page['robots'] ?? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1');
    $type = (string) ($page['type'] ?? 'website');

    $crumbs = $page['breadcrumbs'] ?? [];
    $schema = $page['schema'] ?? [];
    array_unshift($schema, schema_organization(), schema_website());
    $crumbSchema = schema_breadcrumbs($crumbs);
    if ($crumbSchema) {
        $schema[] = $crumbSchema;
    }

    $seo = seo_settings();
    $author = trim((string) ($page['author'] ?? '')) ?: $brand;
    $twitterHandle = trim((string) ($seo['twitterHandle'] ?? ''), " \t\n\r\0\x0B@");
    $googleVerify = trim((string) ($seo['googleVerification'] ?? ''));
    $bingVerify = trim((string) ($seo['bingVerification'] ?? ''));

    cms_security_headers(false);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($fullTitle) ?></title>
<meta name="description" content="<?= e($description) ?>">
<?php if ($keywords !== ''): ?>
<meta name="keywords" content="<?= e($keywords) ?>">
<?php endif; ?>
<meta name="robots" content="<?= e($robots) ?>">
<meta name="author" content="<?= e($author) ?>">
<?php if ($googleVerify !== ''): ?>
<meta name="google-site-verification" content="<?= e($googleVerify) ?>">
<?php endif; ?>
<?php if ($bingVerify !== ''): ?>
<meta name="msvalidate.01" content="<?= e($bingVerify) ?>">
<?php endif; ?>
<link rel="canonical" href="<?= e($canonical) ?>">
<?php if (!empty($page['prev'])): ?>
<link rel="prev" href="<?= e(seo_url(ltrim((string) $page['prev'], '/'))) ?>">
<?php endif; ?>
<?php if (!empty($page['next'])): ?>
<link rel="next" href="<?= e(seo_url(ltrim((string) $page['next'], '/'))) ?>">
<?php endif; ?>
<meta property="og:type" content="<?= e($type) ?>">
<meta property="og:site_name" content="<?= e($brand) ?>">
<meta property="og:locale" content="en_IN">
<meta property="og:title" content="<?= e($fullTitle) ?>">
<meta property="og:description" content="<?= e($description) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($image) ?>">
<meta property="og:image:alt" content="<?= e($rawTitle !== '' ? $rawTitle : $brand) ?>">
<?php if ($type === 'article' && !empty($page['publishedTime'])): ?>
<meta property="article:published_time" content="<?= e((string) $page['publishedTime']) ?>">
<meta property="article:modified_time" content="<?= e((string) ($page['modifiedTime'] ?? $page['publishedTime'])) ?>">
<meta property="article:author" content="<?= e($author) ?>">
<?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if ($twitterHandle !== ''): ?>
<meta name="twitter:site" content="@<?= e($twitterHandle) ?>">
<meta name="twitter:creator" content="@<?= e($twitterHandle) ?>">
<?php endif; ?>
<meta name="twitter:title" content="<?= e($fullTitle) ?>">
<meta name="twitter:description" content="<?= e($description) ?>">
<meta name="twitter:image" content="<?= e($image) ?>">
<meta name="theme-color" content="#0f1419">
<link rel="icon" href="<?= e(url_for('assets/img/logo.png')) ?>" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Oswald:wght@500;600;700&family=Barlow:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url_for('assets/css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(url_for('assets/css/site.css')) ?>">
<?= json_ld($schema) ?>
<?= analytics_head() ?>
</head>
<body<?= !empty($page['bodyAttr']) ? ' ' . $page['bodyAttr'] : '' ?>>
<?= analytics_body() ?>
<a class="skip-link" href="#main">Skip to content</a>
<div data-site="header"></div>
<main id="main">
<?php
}

function page_foot(array $opts = []): void
{
    $scripts = $opts['scripts'] ?? [];
    ?>
</main>
<div data-site="footer"<?= !empty($opts['compactFooter']) ? ' data-compact' : '' ?>></div>
<div data-site="fabs"></div>
<script>window.SITE_BASE = <?= json_encode(seo_base_path() . '/', JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="<?= e(url_for('config/site.config.php')) ?>"></script>
<script src="<?= e(url_for('assets/js/shell.js')) ?>"></script>
<script src="<?= e(url_for('assets/js/render.js')) ?>"></script>
<script src="<?= e(url_for('assets/js/app.js')) ?>"></script>
<?php foreach ($scripts as $script): ?>
<script src="<?= e(url_for('assets/js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
<?php
}

function render_article_body(string $body): string
{
    $body = trim($body);
    if ($body === '') {
        return '';
    }
    $blocks = preg_split('/\R{2,}/', $body) ?: [];
    $html = '';
    foreach ($blocks as $block) {
        $block = trim($block);
        if ($block === '') {
            continue;
        }
        if (!str_contains($block, "\n") && str_ends_with($block, ':') && mb_strlen($block) < 120) {
            $html .= '<h2>' . e(rtrim($block, ':')) . '</h2>';
            continue;
        }
        $html .= '<p>' . nl2br(e($block)) . '</p>';
    }
    return $html;
}

function render_public_pagination(int $page, int $pages, string $basePath, array $extra = []): string
{
    if ($pages <= 1) {
        return '';
    }

    $link = static function (int $target) use ($basePath, $extra): string {
        $query = $extra;
        if ($target > 1) {
            $query['page'] = $target;
        }
        return e(url_for($basePath) . ($query ? '?' . http_build_query($query) : ''));
    };

    $html = '<div class="pagination-wrap"><nav class="pagination" aria-label="Pagination"><ul>';
    $html .= $page <= 1
        ? '<li><span class="pagination-btn is-disabled" aria-disabled="true">Prev</span></li>'
        : '<li><a class="pagination-btn" rel="prev" href="' . $link($page - 1) . '">Prev</a></li>';

    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 9 && abs($i - $page) > 2 && $i !== 1 && $i !== $pages) {
            if ($i === 2 || $i === $pages - 1) {
                $html .= '<li><span class="pagination-ellipsis">…</span></li>';
            }
            continue;
        }
        $html .= $i === $page
            ? '<li><a class="pagination-btn is-active" aria-current="page" href="' . $link($i) . '">' . $i . '</a></li>'
            : '<li><a class="pagination-btn" href="' . $link($i) . '">' . $i . '</a></li>';
    }

    $html .= $page >= $pages
        ? '<li><span class="pagination-btn is-disabled" aria-disabled="true">Next</span></li>'
        : '<li><a class="pagination-btn" rel="next" href="' . $link($page + 1) . '">Next</a></li>';

    return $html . '</ul></nav></div>';
}

function render_empty_state(string $heading, string $body, array $actions = []): string
{
    $buttons = '';
    foreach ($actions as $action) {
        $class = !empty($action['primary']) ? 'btn btn-primary' : 'btn btn-outline';
        $buttons .= '<a class="' . $class . '" href="' . e(url_for(ltrim((string) $action['href'], '/'))) . '">'
            . e((string) $action['label']) . '</a>';
    }
    return '<div class="catalog-empty"><div class="catalog-empty-inner">'
        . '<strong>' . e($heading) . '</strong>'
        . '<p>' . e($body) . '</p>'
        . ($buttons !== '' ? '<div class="catalog-empty-actions">' . $buttons . '</div>' : '')
        . '</div></div>';
}

function browse_actions(): array
{
    return [
        ['label' => 'Browse all products', 'href' => 'products', 'primary' => true],
        ['label' => 'Ask our team', 'href' => 'contact'],
    ];
}

function render_not_found(array $opts): void
{
    http_response_code(404);
    page_head([
        'title' => (string) $opts['title'],
        'description' => (string) $opts['description'],
        'canonical' => (string) ($opts['canonical'] ?? '404'),
        'robots' => 'noindex, follow',
    ]);
    echo '<section class="section"><div class="container">';
    echo render_empty_state(
        (string) $opts['heading'],
        (string) $opts['body'],
        $opts['actions'] ?? browse_actions()
    );
    echo '</div></section>';
    page_foot();
    exit;
}

function render_product_card(array $product): string
{
    $image = $product['images'][0] ?? 'assets/img/products.jpg';
    return '<a class="product-card" href="' . e(product_path((string) $product['slug'])) . '">'
        . '<span class="product-card-media">'
        . '<img src="' . e(url_for($image)) . '" alt="' . e((string) $product['name']) . '" width="480" height="320" loading="lazy" data-fallback>'
        . '</span>'
        . '<span class="product-card-body">'
        . '<strong>' . e((string) $product['name']) . '</strong>'
        . '<em>' . e((string) $product['short']) . '</em>'
        . '<span class="tile-link">View Product <svg width="18" height="18" aria-hidden="true"><use href="'
        . e(url_for('assets/img/sprite.svg')) . '#icon-arrow"></use></svg></span>'
        . '</span></a>';
}

function render_breadcrumbs(array $crumbs): string
{
    $parts = [];
    foreach ($crumbs as $label => $path) {
        $parts[] = ($path === null || $path === '')
            ? '<span>' . e((string) $label) . '</span>'
            : '<a href="' . e(url_for(ltrim((string) $path, '/'))) . '">' . e((string) $label) . '</a>';
    }
    return implode(' / ', $parts);
}
