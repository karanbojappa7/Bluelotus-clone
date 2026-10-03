<?php
declare(strict_types=1);

function sitemap_xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function sitemap_lastmod(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m) === 1) {
        return substr($m[0], 0, 10);
    }
    $stamp = strtotime($value);
    return $stamp ? gmdate('Y-m-d', $stamp) : '';
}

function sitemap_url_entry(string $path, string $changefreq, string $priority, string $lastmod = '', array $images = []): string
{
    $xml = "  <url>\n";
    $xml .= '    <loc>' . sitemap_xml_escape(seo_url(ltrim($path, '/'))) . "</loc>\n";
    if ($lastmod !== '') {
        $xml .= '    <lastmod>' . sitemap_xml_escape($lastmod) . "</lastmod>\n";
    }
    $xml .= '    <changefreq>' . $changefreq . "</changefreq>\n";
    $xml .= '    <priority>' . $priority . "</priority>\n";
    foreach (array_slice(array_values(array_filter($images)), 0, 20) as $image) {
        $xml .= '    <image:image><image:loc>'
            . sitemap_xml_escape(seo_image((string) $image))
            . "</image:loc></image:image>\n";
    }
    return $xml . "  </url>\n";
}

function sitemap_urlset(string $body): string
{
    return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" '
        . 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n"
        . $body
        . "</urlset>\n";
}

function sitemap_home_images(): array
{
    $images = [];
    if (function_exists('hero_slides')) {
        foreach (hero_slides() as $slide) {
            $image = trim((string) ($slide['image'] ?? ''));
            if ($image !== '') {
                $images[] = $image;
            }
        }
    }
    if (!$images) {
        $images[] = 'assets/img/hero.jpg';
    }
    return array_values(array_unique($images));
}

function sitemap_pages_xml(): string
{
    $today = gmdate('Y-m-d');
    $body = '';
    $body .= sitemap_url_entry('', 'weekly', '1.0', $today, sitemap_home_images());
    $body .= sitemap_url_entry('about', 'monthly', '0.7', $today);
    $body .= sitemap_url_entry('products', 'weekly', '0.9', $today);
    $body .= sitemap_url_entry('faq', 'monthly', '0.75', $today);
    $body .= sitemap_url_entry('blog', 'weekly', '0.7', $today);
    $body .= sitemap_url_entry('contact', 'monthly', '0.6', $today);
    $body .= sitemap_url_entry('privacy-policy', 'yearly', '0.2');
    return sitemap_urlset($body);
}

function sitemap_collection_xml(array $rows, string $freq, string $priority, callable $path, callable $date, callable $images): string
{
    $body = '';
    foreach ($rows as $row) {
        if (!is_array($row) || !empty($row['noindex'])) {
            continue;
        }
        $loc = (string) $path($row);
        if ($loc === '') {
            continue;
        }
        $body .= sitemap_url_entry($loc, $freq, $priority, sitemap_lastmod((string) $date($row)), (array) $images($row));
    }
    return sitemap_urlset($body);
}

function sitemap_categories_xml(): string
{
    $rows = db_ready() ? all_categories() : [];
    return sitemap_collection_xml(
        $rows,
        'weekly',
        '0.85',
        static fn (array $r): string => !empty($r['id']) ? category_slug_path((string) $r['id']) : '',
        static fn (array $r): string => (string) ($r['updatedAt'] ?? ''),
        static fn (array $r): array => array_values(array_filter([
            (string) ($r['image'] ?? ''),
            (string) ($r['ogImage'] ?? ''),
        ]))
    );
}

function sitemap_products_xml(): string
{
    $rows = db_ready() ? all_products() : [];
    return sitemap_collection_xml(
        $rows,
        'weekly',
        '0.8',
        static fn (array $r): string => !empty($r['slug']) ? product_slug_path((string) $r['slug']) : '',
        static fn (array $r): string => (string) ($r['updatedAt'] ?? ''),
        static fn (array $r): array => (array) ($r['images'] ?? [])
    );
}

function sitemap_posts_xml(): string
{
    $rows = db_ready() ? setting_list('blog') : [];
    return sitemap_collection_xml(
        $rows,
        'weekly',
        '0.65',
        static fn (array $r): string => !empty($r['slug']) ? post_slug_path((string) $r['slug']) : '',
        static fn (array $r): string => (string) ($r['date'] ?? $r['updated'] ?? ''),
        static fn (array $r): array => !empty($r['image']) ? [(string) $r['image']] : []
    );
}

function sitemap_map_names(): array
{
    return ['pages', 'categories', 'products', 'posts'];
}

function sitemap_index_xml(): string
{
    $today = gmdate('Y-m-d');
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (sitemap_map_names() as $name) {
        $xml .= "  <sitemap>\n";
        $xml .= '    <loc>' . sitemap_xml_escape(seo_url('sitemap-' . $name . '.xml')) . "</loc>\n";
        $xml .= '    <lastmod>' . $today . "</lastmod>\n";
        $xml .= "  </sitemap>\n";
    }
    return $xml . "</sitemapindex>\n";
}

function build_sitemap(string $map = 'index'): string
{
    return match ($map) {
        'pages' => sitemap_pages_xml(),
        'categories' => sitemap_categories_xml(),
        'products' => sitemap_products_xml(),
        'posts' => sitemap_posts_xml(),
        default => sitemap_index_xml(),
    };
}

function robots_allow_groups(): array
{
    return [
        'Search engines' => [
            'Googlebot',
            'Googlebot-Image',
            'Googlebot-News',
            'Googlebot-Video',
            'Bingbot',
            'Slurp',
            'DuckDuckBot',
            'Baiduspider',
            'YandexBot',
            'Applebot',
            'Sogou',
            'Qwantify',
            'SeznamBot',
            'Naverbot',
            'Yeti',
            'Ecosia',
            'Brave',
        ],
        'AI / LLM crawlers and chatbots' => [
            '# OpenAI (ChatGPT)',
            'GPTBot',
            'ChatGPT-User',
            'OAI-SearchBot',
            '# Anthropic (Claude)',
            'ClaudeBot',
            'Claude-User',
            'Claude-SearchBot',
            'anthropic-ai',
            'Claude-Web',
            '# Google (Gemini / AI Overviews)',
            'Google-Extended',
            'GoogleOther',
            'Google-CloudVertexBot',
            '# Microsoft (Copilot / Bing AI)',
            'BingPreview',
            '# Perplexity',
            'PerplexityBot',
            'Perplexity-User',
            '# Meta (Llama / Meta AI)',
            'meta-externalagent',
            'meta-externalfetcher',
            'FacebookBot',
            'facebookexternalhit',
            '# Apple Intelligence',
            'Applebot-Extended',
            '# Amazon (Alexa / Amazon AI)',
            'Amazonbot',
            '# ByteDance / TikTok',
            'Bytespider',
            '# DuckDuckGo AI',
            'DuckAssistBot',
            '# Mistral',
            'MistralAI-User',
            '# Cohere',
            'cohere-ai',
            'cohere-training-data-crawler',
            '# You.com',
            'YouBot',
            '# Common Crawl (feeds many AI models)',
            'CCBot',
            '# Diffbot',
            'Diffbot',
            '# Other AI crawlers',
            'AI2Bot',
            'Ai2Bot-Dolma',
            'omgili',
            'omgilibot',
            'PetalBot',
            'Timpibot',
            'ImagesiftBot',
            'Webzio-Extended',
        ],
        'Social preview bots' => [
            'Twitterbot',
            'LinkedInBot',
            'WhatsApp',
            'TelegramBot',
            'Slackbot',
            'Pinterestbot',
        ],
    ];
}

function build_robots_txt(bool $allowCrawling = true): string
{
    if (!$allowCrawling) {
        return "User-agent: *\nDisallow: /\n";
    }

    $out = '# robots.txt for ' . rtrim(seo_domain(), '/') . "/\n";
    $out .= "# Allows all search engines, AI crawlers and chatbots\n\n";
    $out .= "# ---- Default: allow everything ----\n";
    $out .= "User-agent: *\nAllow: /\n\n";

    foreach (robots_allow_groups() as $heading => $agents) {
        $out .= '# ---- ' . $heading . " ----\n";
        foreach ($agents as $agent) {
            if (str_starts_with($agent, '# ')) {
                $out .= $agent . "\n";
                continue;
            }
            $out .= "User-agent: {$agent}\nAllow: /\n\n";
        }
    }

    $out .= "# ---- Sitemap ----\n";
    $out .= 'Sitemap: ' . seo_url('sitemap.xml') . "\n";
    return $out;
}

function export_sitemaps(): void
{
    $root = CMS_ROOT;
    $files = [
        $root . '/sitemap.xml' => build_sitemap('index'),
        $root . '/sitemap-pages.xml' => build_sitemap('pages'),
        $root . '/sitemap-categories.xml' => build_sitemap('categories'),
        $root . '/sitemap-products.xml' => build_sitemap('products'),
        $root . '/sitemap-posts.xml' => build_sitemap('posts'),
        $root . '/robots.txt' => build_robots_txt(true),
    ];
    foreach ($files as $path => $contents) {
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Could not write ' . basename($path));
        }
    }
}

function serve_sitemap(string $map = 'index'): void
{
    if (!in_array($map, array_merge(['index'], sitemap_map_names()), true)) {
        $map = 'index';
    }
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=3600');
    echo build_sitemap($map);
}

function serve_robots(): void
{
    header('Content-Type: text/plain; charset=UTF-8');
    header('Cache-Control: public, max-age=3600');
    echo build_robots_txt(true);
}
