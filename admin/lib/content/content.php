<?php
declare(strict_types=1);

function setting(string $key, $default = null)
{
    if (!db_ready()) {
        return $default;
    }
    $stmt = db()->prepare('SELECT value FROM settings WHERE `key` = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return $default;
    }
    $decoded = json_decode($row['value'], true);
    return $decoded === null ? $default : $decoded;
}

function save_setting(string $key, $value): void
{
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $stmt = db()->prepare('INSERT INTO settings (`key`, value) VALUES (?, ?) ON DUPLICATE KEY UPDATE value = ?');
    $stmt->execute([$key, $json, $json]);
}

function setting_list(string $key): array
{
    $value = setting($key, []);
    return is_array($value) ? array_values($value) : [];
}

function setting_get_item(string $key, int $index): ?array
{
    $list = setting_list($key);
    if (!isset($list[$index]) || !is_array($list[$index])) {
        return null;
    }
    return $list[$index];
}

function setting_save_item(string $key, ?int $index, array $item): int
{
    $list = setting_list($key);
    if ($index === null || $index < 0 || $index >= count($list)) {
        $list[] = $item;
        $index = count($list) - 1;
    } else {
        $list[$index] = $item;
    }
    save_setting($key, $list);
    return $index;
}

function setting_move_item(string $key, int $index, int $direction): void
{
    $list = setting_list($key);
    $target = $index + $direction;
    if ($index < 0 || $index >= count($list) || $target < 0 || $target >= count($list)) {
        return;
    }
    $swap = $list[$index];
    $list[$index] = $list[$target];
    $list[$target] = $swap;
    save_setting($key, array_values($list));
}

function setting_delete_item(string $key, int $index): void
{
    $list = setting_list($key);
    if (!isset($list[$index])) {
        return;
    }
    array_splice($list, $index, 1);
    save_setting($key, array_values($list));
}

function normalize_client($row): ?array
{
    if (is_string($row)) {
        $name = trim($row);
        return $name !== '' ? ['name' => $name, 'logo' => ''] : null;
    }
    if (!is_array($row)) {
        return null;
    }
    $name = trim((string) ($row['name'] ?? $row['title'] ?? ''));
    if ($name === '') {
        return null;
    }
    return [
        'name' => $name,
        'logo' => trim((string) ($row['logo'] ?? $row['image'] ?? '')),
    ];
}

function all_clients(): array
{
    $out = [];
    foreach (setting_list('clients') as $row) {
        $item = normalize_client($row);
        if ($item) {
            $out[] = $item;
        }
    }
    return $out;
}

function default_jobs(): array
{
    return [
        [
            'title' => 'Safety Installation Technician',
            'slug' => 'safety-installation-technician',
            'department' => 'Field',
            'location' => 'Berhampur, with site travel',
            'type' => 'Full-time',
            'openings' => 1,
            'summary' => 'Install, commission, and hand over certified safety equipment on industrial, road, and warehouse sites.',
            'duties' => [
                'Fit barricades, fire systems, rack guards, and related kit to drawing',
                'Work with the site engineer on layout, fixings, and handover notes',
                'Travel to live plants and highway jobs across Odisha and neighbouring states',
                'Keep tools, PPE, and van stock in usable condition',
            ],
            'open' => true,
            'posted' => '2026-10-01',
        ],
        [
            'title' => 'B2B Sales Engineer',
            'slug' => 'b2b-sales-engineer',
            'department' => 'Sales',
            'location' => 'Odisha & Andhra Pradesh',
            'type' => 'Full-time',
            'openings' => 1,
            'summary' => 'Specify and close certified safety packages for contractors, industries, and government buyers.',
            'duties' => [
                'Qualify project enquiries and build a scoped bill of materials',
                'Visit sites with the technical team before quoting',
                'Own the relationship from first call through order and installation',
                'Keep a clean pipeline of municipal, industrial, and contractor accounts',
            ],
            'open' => true,
            'posted' => '2026-10-01',
        ],
        [
            'title' => 'Warehouse & Dispatch Coordinator',
            'slug' => 'warehouse-dispatch-coordinator',
            'department' => 'Operations',
            'location' => 'Berhampur, Odisha',
            'type' => 'Full-time',
            'openings' => 1,
            'summary' => 'Stage, pack, and dispatch project lots so crews arrive with the right kit, in the right sequence.',
            'duties' => [
                'Pick and pack orders against project lists',
                'Coordinate inbound stock and outbound site vehicles',
                'Keep inventory counts honest across ten product categories',
                'Flag shortages before a crew leaves the yard',
            ],
            'open' => true,
            'posted' => '2026-10-01',
        ],
        [
            'title' => 'Fire Systems Service Technician',
            'slug' => 'fire-systems-service-technician',
            'department' => 'Service',
            'location' => 'Pan-India project sites',
            'type' => 'Full-time',
            'openings' => 1,
            'summary' => 'Inspect, refill, and maintain installed fire systems under AMC, including emergency call-outs.',
            'duties' => [
                'Run scheduled inspections and refill cycles',
                'Respond to breakdown calls with the service roster',
                'Document every visit so the next technician is not guessing',
                'Escalate OEM warranty issues instead of patching around them',
            ],
            'open' => true,
            'posted' => '2026-10-01',
        ],
    ];
}

function normalize_job($row): ?array
{
    if (!is_array($row)) {
        return null;
    }
    $title = trim((string) ($row['title'] ?? ''));
    if ($title === '') {
        return null;
    }
    $duties = $row['duties'] ?? [];
    if (is_string($duties)) {
        $duties = lines_to_array($duties);
    }
    if (!is_array($duties)) {
        $duties = [];
    }
    $open = $row['open'] ?? true;
    if (is_string($open)) {
        $open = !in_array(strtolower($open), ['0', 'false', 'no', ''], true);
    }
    $openings = (int) ($row['openings'] ?? 1);
    if ($openings < 1) {
        $openings = 1;
    }
    $slug = trim((string) ($row['slug'] ?? ''));
    return [
        'title' => $title,
        'slug' => $slug !== '' ? slugify($slug) : slugify($title),
        'department' => trim((string) ($row['department'] ?? '')),
        'location' => trim((string) ($row['location'] ?? '')),
        'type' => trim((string) ($row['type'] ?? 'Full-time')),
        'summary' => trim((string) ($row['summary'] ?? '')),
        'duties' => array_values(array_filter(array_map(static fn ($line) => trim((string) $line), $duties))),
        'open' => (bool) $open,
        'openings' => $openings,
        'posted' => trim((string) ($row['posted'] ?? '')),
    ];
}

function job_by_slug(string $slug): ?array
{
    $slug = slugify($slug);
    if ($slug === '') {
        return null;
    }
    foreach (all_jobs() as $job) {
        if ($job['slug'] === $slug && !empty($job['open'])) {
            return $job;
        }
    }
    return null;
}

function job_openings_label(int $count): string
{
    $count = max(1, $count);
    return $count . ' open position' . ($count === 1 ? '' : 's');
}

function job_date_label(string $posted): string
{
    $posted = substr(trim($posted), 0, 10);
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $posted);
    if (!$date) {
        return '';
    }
    return $date->format('j F Y');
}

function ensure_jobs_setting(): void
{
    if (!db_ready()) {
        return;
    }
    if (!is_array(setting('jobs', null))) {
        save_setting('jobs', default_jobs());
    }
}

function all_jobs(): array
{
    $source = db_ready() && is_array(setting('jobs', null))
        ? setting_list('jobs')
        : default_jobs();
    $out = [];
    foreach ($source as $row) {
        $job = normalize_job($row);
        if ($job) {
            $out[] = $job;
        }
    }
    return $out;
}

function open_jobs(): array
{
    return array_values(array_filter(all_jobs(), static fn (array $job) => $job['open']));
}

function career_listicle_items(array $copy, string $prefix, int $count): array
{
    $items = [];
    for ($i = 1; $i <= $count; $i++) {
        $title = trim(page_text($copy, $prefix . $i . 'Title'));
        $body = trim(page_text($copy, $prefix . $i . 'Body'));
        if ($title === '' && $body === '') {
            continue;
        }
        $items[] = ['title' => $title, 'body' => $body];
    }
    return $items;
}

function default_hero_slides(): array
{
    return [
        [
            'eyebrow' => 'Certified Safety Solutions · Berhampur',
            'title' => 'Protection engineered for every worksite, every day.',
            'highlight' => 'every worksite,',
            'subtitle' => 'We supply, install, and maintain fire, road, industrial, and household safety systems for contractors, industries, and government agencies across India.',
            'image' => 'assets/img/hero.jpg',
            'navLabel' => 'Supply',
            'buttonLabel' => 'Request a Site Audit',
            'buttonUrl' => 'contact',
            'button2Label' => 'Browse Product Range',
            'button2Url' => 'products',
        ],
        [
            'eyebrow' => 'Fire Safety',
            'title' => 'Contain fire before it reaches people.',
            'highlight' => 'before',
            'subtitle' => 'Extinguishers, hydrants, and detection specified for Indian sites — installed and maintained by our own crews.',
            'image' => 'assets/img/products.jpg',
            'navLabel' => 'Install',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'products/fire-safety',
            'button2Label' => '',
            'button2Url' => '',
        ],
        [
            'eyebrow' => 'Road & Traffic Safety',
            'title' => 'Keep every lane and work zone under control.',
            'highlight' => 'work zone',
            'subtitle' => 'Barricades, cones, signage, and crash protection built for highways, yards, and temporary site approaches.',
            'image' => 'assets/img/about.jpg',
            'navLabel' => 'Maintain',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'products/road-traffic-safety',
            'button2Label' => '',
            'button2Url' => '',
        ],
        [
            'eyebrow' => 'End-to-end Support',
            'title' => 'Audit, install, and stay accountable.',
            'highlight' => 'stay accountable.',
            'subtitle' => 'Consultancy, installation, and 24/7 maintenance — one team past the invoice, not a chain of vendors.',
            'image' => 'assets/img/blog-1.jpg',
            'navLabel' => 'Accountably',
            'buttonLabel' => 'Learn More',
            'buttonUrl' => 'faq',
            'button2Label' => '',
            'button2Url' => '',
        ],
    ];
}

function hero_slides(): array
{
    $stored = setting_list('hero');
    if ($stored) {
        $out = [];
        foreach ($stored as $slide) {
            if (!is_array($slide)) {
                continue;
            }
            $title = trim((string) ($slide['title'] ?? ''));
            if ($title === '') {
                continue;
            }
            $out[] = $slide;
        }
        if ($out) {
            return $out;
        }
    }
    return default_hero_slides();
}

function hero_title_html(array $slide): string
{
    $title = trim((string) ($slide['title'] ?? ''));
    $highlight = trim((string) ($slide['highlight'] ?? ''));
    if ($highlight === '') {
        return e($title);
    }
    $pos = stripos($title, $highlight);
    if ($pos === false) {
        return e($title) . ' <em>' . e($highlight) . '</em>';
    }
    $len = strlen($highlight);
    return e(substr($title, 0, $pos)) . '<em>' . e(substr($title, $pos, $len)) . '</em>' . e(substr($title, $pos + $len));
}

function hero_link(string $path, string $fallback = 'contact'): string
{
    $path = trim($path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('#^https?://#i', $path) === 1) {
        return $path;
    }
    return url_for(ltrim($path, '/'));
}

function hero_image_url(array $slide): string
{
    $image = trim((string) ($slide['image'] ?? ''));
    if ($image === '') {
        $image = 'assets/img/hero.jpg';
    }
    return url_for($image);
}

function hero_mobile_image_url(array $slide): string
{
    $image = trim((string) ($slide['imageMobile'] ?? ''));
    if ($image === '') {
        return hero_image_url($slide);
    }
    return url_for($image);
}

function page_labels(): array
{
    return [
        'home' => 'Home',
        'about' => 'About Us',
        'products' => 'Products',
        'faq' => 'FAQ',
        'blog' => 'Blog',
        'careers' => 'Careers',
        'contact' => 'Contact Us',
        'privacy' => 'Privacy Policy',
    ];
}

function default_page_copy(): array
{
    return [
        'home' => [
            'supplyEyebrow' => 'What We Supply',
            'supplyTitle' => 'Ten categories, one accountable vendor',
            'supplyIntro' => 'From roadside barricading to warehouse rack guards, every category is stocked, certified, and backed by our own installation crews.',
            'servicesEyebrow' => 'What We Do',
            'servicesTitle' => 'Supply is the start. Service is the contract.',
            'servicesIntro' => 'Consultancy, installation, and maintenance from the same crew that specified the equipment — so accountability does not stop at delivery.',
            'catalogEyebrow' => 'Product Catalog',
            'catalogTitle' => 'Equipment stocked for live sites',
            'whyEyebrow' => 'Why Bluelotus',
            'whyTitle' => 'Built for accountability, not just supply',
            'whyIntro' => 'We hold the relationship end to end — audit, spec, install, and maintain — so nothing falls through the gap between vendors.',
            'whyImage' => '',
            'why1Title' => 'Professional',
            'why1Body' => 'An in-house team of certified safety engineers handles design, execution, commissioning, and maintenance across every category we sell.',
            'why2Title' => 'Secure',
            'why2Body' => 'Every product is sourced against regulatory standards first, curated for reliability, not just price point.',
            'why3Title' => 'Guaranteed',
            'why3Body' => 'We only integrate products with confirmed OEM after-sales support, so warranty and servicing are never in question.',
            'faqEyebrow' => 'FAQ',
            'faqTitle' => 'Answers before you call',
            'testimonialsEyebrow' => 'Client Feedback',
            'testimonialsTitle' => 'Trusted on sites across Odisha',
            'clientsEyebrow' => 'Trusted By Companies Across India',
            'festiveFrom' => '',
            'festiveTo' => '',
            'festiveImage' => '',
            'blogEyebrow' => 'From the Blog',
            'blogTitle' => 'Field notes on safety standards',
            'ctaEyebrow' => 'Get Started',
            'ctaTitle' => 'Ready to safety-proof your site?',
            'ctaIntro' => "Tell us your site type and headcount — we'll come back with a scoped safety plan within 24 hours.",
            'ctaButton' => 'Talk to Our Team',
        ],
        'about' => [
            'eyebrow' => 'About Bluelotus',
            'title' => 'Safety systems built by people who install them.',
            'heroImage' => 'assets/img/about.jpg',
            'storyEyebrow' => 'Our Story',
            'storyTitle' => 'Founded on-site, not in a boardroom',
            'storyBody1' => 'Bluelotus Infrasafety started with a single fire-safety contract in Berhampur, Odisha in {foundedYear}. What we learned fitting out that first plant — that most safety failures come from mismatched vendors, not bad products — still shapes how we work today.',
            'storyBody2' => 'We now run supply, installation, and maintenance under one roof across ten safety categories, so a facilities manager deals with one accountable partner instead of six.',
            'storyImage' => 'assets/img/about.jpg',
            'approachEyebrow' => 'Our Approach',
            'approachTitle' => 'Three commitments behind every project',
            'tile1Title' => 'Professional',
            'tile1Body' => 'Design, execution, commissioning, and maintenance run through the same certified in-house team on every engagement.',
            'tile2Title' => 'Secure',
            'tile2Body' => 'Every product line is checked against IS and ISO standards before it enters our catalog, not after a complaint.',
            'tile3Title' => 'Guaranteed',
            'tile3Body' => 'We only recommend products with confirmed OEM after-sales support, so servicing is never a dead end.',
            'teamEyebrow' => 'Meet the team',
            'teamTitle' => 'Founders & Leadership',
            'teamIntro' => 'The people guiding every site from first audit to handover.',
            'ctaEyebrow' => 'Work With Us',
            'ctaTitle' => "Let's talk about your site's safety plan",
            'ctaButton' => 'Contact Our Team',
        ],
        'contact' => [
            'eyebrow' => 'Get In Touch',
            'title' => "Tell us about your site, we'll take it from there.",
            'heroImage' => 'assets/img/contact-hero.jpg',
            'detailsEyebrow' => 'Contact Details',
            'detailsTitle' => 'Reach us directly',
            'formEyebrow' => 'Send a Message',
            'formTitle' => 'Request a quote or audit',
        ],
        'blog' => [
            'eyebrow' => 'Safety Insights',
            'title' => 'Guidance from the people who install it.',
            'heroImage' => 'assets/img/hero.jpg',
        ],
        'products' => [
            'eyebrow' => 'Full Catalog',
            'title' => 'Certified safety equipment, one vendor.',
            'intro' => 'Browse fire, road, industrial, warehouse, and construction ranges — ready for site supply and bulk orders.',
            'heroImage' => 'assets/img/products.jpg',
            'bulkImage' => 'assets/img/products.jpg',
            'bulkMark' => 'Project Supply',
            'bulkEyebrow' => 'Bulk & Project Supply',
            'bulkTitle' => 'Need equipment for a full site rollout?',
            'bulkLead' => 'Bulk procurement, staged delivery, and on-site installation for campuses, industrial parks, and government contracts — one accountable partner for the whole project.',
            'bulkPoint1' => 'Wholesale pricing for multi-site and govt. orders',
            'bulkPoint2' => 'Staged delivery matched to your install calendar',
            'bulkPoint3' => 'Single point of contact from audit to handover',
            'bulkButton' => 'Request Bulk Quote',
            'bulkButtonUrl' => 'contact',
            'bulkButton2' => 'Talk to Sales',
            'bulkButton2Url' => 'contact#quote-form',
        ],
        'faq' => [
            'eyebrow' => 'Help Center',
            'title' => 'Frequently asked questions.',
            'heroImage' => 'assets/img/hero.jpg',
        ],
        'careers' => [
            'eyebrow' => 'Careers',
            'title' => 'Work on sites that actually get installed.',
            'heroImage' => 'assets/img/about.jpg',
            'introEyebrow' => 'The list',
            'introTitle' => 'Five reasons people join the crew',
            'intro' => 'Bluelotus is a field company. You will spend more time on plants, highways, and warehouses than in a conference room — and you will own the work from spec to handover.',
            'why1Title' => 'Live sites, not slide decks',
            'why1Body' => 'Installation, commissioning, and maintenance happen on real industrial floors. You learn the product by fitting it, not by quoting a catalog.',
            'why2Title' => 'One crew, end to end',
            'why2Body' => 'Audit, supply, install, and service sit in the same team. There is no vendor to blame when something does not land.',
            'why3Title' => 'Certified kit, serious standards',
            'why3Body' => 'We specify against IS, ISO, and IRC requirements before a line enters the truck. You will be expected to know why a product is on site.',
            'why4Title' => 'Ten categories, one career path',
            'why4Body' => 'Fire, road, warehouse, industrial, construction — people grow sideways here. A technician who can work two categories is more useful than a specialist who will not travel.',
            'why5Title' => 'Berhampur base, India-wide work',
            'why5Body' => 'The office is in Berhampur, Odisha. The jobs are on plants, highways, campuses, and government sites across the country.',
            'rolesEyebrow' => 'Openings',
            'rolesTitle' => 'Roles we are hiring for',
            'rolesIntro' => 'Each role below is a live opening. Apply to a specific one, or send a general profile if you work across categories.',
            'processEyebrow' => 'The process',
            'processTitle' => 'How hiring works here',
            'process1Title' => 'Send your profile',
            'process1Body' => 'A short note, a phone number, and what you have actually installed or sold. We read every application that lands in Enquiries.',
            'process2Title' => 'Screening call',
            'process2Body' => 'Fifteen to twenty minutes with the hiring lead. We will ask about sites you have worked, not about hypotheticals.',
            'process3Title' => 'Role conversation',
            'process3Body' => 'A longer talk — sometimes on a live site — covering tools, travel, and the category you would own.',
            'process4Title' => 'Offer and joining',
            'process4Body' => 'If it is a fit, we send written terms and a joining date. Field roles typically start within two to three weeks.',
            'applyEyebrow' => 'Apply',
            'applyTitle' => 'Tell us where you fit',
            'applyIntro' => 'Applications land in the same enquiry desk as quote requests. We typically reply within one business day.',
            'applyButton' => 'Submit application',
        ],
        'privacy' => [
            'eyebrow' => 'Legal',
            'title' => 'Privacy Policy',
            'heroImage' => 'assets/img/hero.jpg',
            'body' => "We collect only the information you provide through our contact and quote forms — name, company, email, phone, and message content — to respond to your enquiry. We do not sell or share this data with third parties outside of fulfilling your request.\n\n## Information We Collect\n\nContact form submissions, newsletter sign-ups, and standard analytics data such as pages visited and approximate location derived from IP address.\n\n## How We Use It\n\nTo respond to quote and audit requests, send requested updates, and improve site content. We retain enquiry data for as long as needed to service your request.\n\n## Your Rights\n\nYou may request access to, correction of, or deletion of your data at any time by emailing us through the contact page.",
        ],
    ];
}

function page_copy(string $slug): array
{
    $defaults = default_page_copy()[$slug] ?? [];
    $all = setting('pages', []);
    $stored = is_array($all) && is_array($all[$slug] ?? null) ? $all[$slug] : [];
    foreach ($stored as $key => $next) {
        if (is_array($next)) {
            $defaults[$key] = $next;
        }
    }
    foreach ($defaults as $key => $value) {
        if (!array_key_exists($key, $stored)) {
            continue;
        }
        $next = $stored[$key];
        if (is_string($next) && $next !== '') {
            $defaults[$key] = $next;
        }
    }
    return $defaults;
}

function about_approach_icons(): array
{
    return [
        'consult' => 'Person',
        'shield' => 'Shield',
        'award' => 'Award',
        'check' => 'Check',
        'maintenance' => 'Tools',
        'resource' => 'Document',
    ];
}

function about_approach_tiles(array $copy): array
{
    $icons = about_approach_icons();
    $stored = $copy['approachTiles'] ?? null;
    if (is_array($stored)) {
        $tiles = [];
        foreach ($stored as $tile) {
            if (!is_array($tile)) {
                continue;
            }
            $title = trim((string) ($tile['title'] ?? ''));
            $body = trim((string) ($tile['body'] ?? ''));
            if ($title === '' && $body === '') {
                continue;
            }
            $icon = (string) ($tile['icon'] ?? 'consult');
            if (!isset($icons[$icon])) {
                $icon = 'consult';
            }
            $tiles[] = ['icon' => $icon, 'title' => $title, 'body' => $body];
        }
        return $tiles;
    }

    $fallback = [
        ['icon' => 'consult', 'title' => page_text($copy, 'tile1Title'), 'body' => page_text($copy, 'tile1Body')],
        ['icon' => 'shield', 'title' => page_text($copy, 'tile2Title'), 'body' => page_text($copy, 'tile2Body')],
        ['icon' => 'award', 'title' => page_text($copy, 'tile3Title'), 'body' => page_text($copy, 'tile3Body')],
    ];
    return array_values(array_filter($fallback, static fn (array $tile): bool => $tile['title'] !== '' || $tile['body'] !== ''));
}

function about_tiles_from_post(): array
{
    $icons = array_keys(about_approach_icons());
    $titles = $_POST['tileTitle'] ?? [];
    $bodies = $_POST['tileBody'] ?? [];
    $chosen = $_POST['tileIcon'] ?? [];
    if (!is_array($titles)) {
        return [];
    }
    $tiles = [];
    foreach ($titles as $i => $title) {
        $title = trim((string) $title);
        $body = trim((string) ($bodies[$i] ?? ''));
        if ($title === '' && $body === '') {
            continue;
        }
        $icon = (string) ($chosen[$i] ?? 'consult');
        if (!in_array($icon, $icons, true)) {
            $icon = 'consult';
        }
        $tiles[] = ['icon' => $icon, 'title' => $title, 'body' => $body];
    }
    return $tiles;
}

function save_page_copy(string $slug, array $data): void
{
    $all = setting('pages', []);
    if (!is_array($all)) {
        $all = [];
    }
    $all[$slug] = $data;
    save_setting('pages', $all);
}

function festival_time(string $value): ?DateTimeImmutable
{
    $value = trim($value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) !== 1) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('Asia/Kolkata'));
    return $date ?: null;
}

function festival_clear(array $pages): void
{
    $home = is_array($pages['home'] ?? null) ? $pages['home'] : [];
    $image = trim((string) ($home['festiveImage'] ?? ''));
    if ($image !== '') {
        delete_uploaded_image($image);
    }
    $home['festiveImage'] = '';
    $home['festiveFrom'] = '';
    $home['festiveTo'] = '';
    $pages['home'] = $home;
    save_setting('pages', $pages);
}

function festival_expire(): void
{
    if (!db_ready()) {
        return;
    }
    $pages = setting('pages', []);
    if (!is_array($pages) || !is_array($pages['home'] ?? null)) {
        return;
    }
    $to = festival_time((string) ($pages['home']['festiveTo'] ?? ''));
    if (!$to) {
        return;
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
    if ($now <= $to) {
        return;
    }
    festival_clear($pages);
}

function festival_popup(): ?array
{
    festival_expire();
    $home = page_copy('home');
    $image = trim(page_text($home, 'festiveImage'));
    $from = festival_time(page_text($home, 'festiveFrom'));
    $to = festival_time(page_text($home, 'festiveTo'));
    if ($image === '' || !$from || !$to) {
        return null;
    }
    $now = new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata'));
    if ($now < $from || $now > $to) {
        return null;
    }
    return [
        'image' => $image,
        'key' => substr(md5($image . '|' . $from->format('c') . '|' . $to->format('c')), 0, 12),
    ];
}

function page_text(array $copy, string $key): string
{
    $text = (string) ($copy[$key] ?? '');
    if (str_contains($text, '{foundedYear}')) {
        $year = trim((string) (seo_company()['foundedYear'] ?? ''));
        $text = str_replace('{foundedYear}', $year !== '' ? $year : '2011', $text);
    }
    return $text;
}

function page_hero_image(array $copy, string $fallback = 'assets/img/hero.jpg'): string
{
    $image = trim((string) ($copy['heroImage'] ?? ''));
    return $image !== '' ? $image : $fallback;
}

function render_page_banner(array $copy, array $crumbs, string $variant = 'photo', ?string $titleOverride = null): void
{
    $fallback = $variant === 'contact' ? 'assets/img/contact-hero.jpg' : 'assets/img/hero.jpg';
    $image = page_hero_image($copy, $fallback);
    $class = 'page-header page-header--banner page-header--' . ($variant === 'contact' ? 'contact' : 'photo');
    $title = $titleOverride !== null && $titleOverride !== '' ? $titleOverride : page_text($copy, 'title');
    ?>
<section class="<?= e($class) ?>" style="--page-header-image:url('<?= e(url_for($image)) ?>')">
  <div class="container">
    <span class="eyebrow eyebrow--light"><?= e(page_text($copy, 'eyebrow')) ?></span>
    <h1 class="page-title"><?= e($title) ?></h1>
    <?php if ($crumbs): ?>
      <div class="breadcrumb-row"><?= render_breadcrumbs($crumbs) ?></div>
    <?php endif; ?>
  </div>
</section>
    <?php
}

function page_editor_fields(string $slug): array
{
    $fields = [
        'home' => [
            ['legend' => 'Categories block'],
            ['key' => 'supplyEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'supplyTitle', 'label' => 'Heading'],
            ['key' => 'supplyIntro', 'label' => 'Intro', 'type' => 'textarea'],
            ['legend' => 'Services block'],
            ['key' => 'servicesEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'servicesTitle', 'label' => 'Heading'],
            ['key' => 'servicesIntro', 'label' => 'Intro', 'type' => 'textarea'],
            ['legend' => 'Catalog block'],
            ['key' => 'catalogEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'catalogTitle', 'label' => 'Heading'],
            ['legend' => 'Why Bluelotus'],
            ['key' => 'whyEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'whyTitle', 'label' => 'Heading'],
            ['key' => 'whyIntro', 'label' => 'Intro', 'type' => 'textarea'],
            ['key' => 'whyImage', 'label' => 'Section image', 'type' => 'image', 'hint' => 'Recommended 900 × 700 px. Optional.'],
            ['key' => 'why1Title', 'label' => 'Point 1 title'],
            ['key' => 'why1Body', 'label' => 'Point 1 text', 'type' => 'textarea'],
            ['key' => 'why2Title', 'label' => 'Point 2 title'],
            ['key' => 'why2Body', 'label' => 'Point 2 text', 'type' => 'textarea'],
            ['key' => 'why3Title', 'label' => 'Point 3 title'],
            ['key' => 'why3Body', 'label' => 'Point 3 text', 'type' => 'textarea'],
            ['legend' => 'FAQ, testimonials, clients, blog'],
            ['key' => 'faqEyebrow', 'label' => 'FAQ eyebrow'],
            ['key' => 'faqTitle', 'label' => 'FAQ heading'],
            ['key' => 'testimonialsEyebrow', 'label' => 'Testimonials eyebrow'],
            ['key' => 'testimonialsTitle', 'label' => 'Testimonials heading'],
            ['key' => 'clientsEyebrow', 'label' => 'Clients eyebrow'],
            ['legend' => 'Festival popup'],
            ['key' => 'festiveFrom', 'label' => 'From', 'type' => 'datetime', 'hint' => 'India time. The picture popup starts at this moment.'],
            ['key' => 'festiveTo', 'label' => 'To', 'type' => 'datetime', 'hint' => 'India time. When this moment passes, the picture is deleted and the popup stops.'],
            ['key' => 'festiveImage', 'label' => 'Festival picture', 'type' => 'image', 'hint' => 'Shown once when a page loads, until the visitor closes it. Recommended 1000 × 1200 px.'],
            ['key' => 'blogEyebrow', 'label' => 'Blog eyebrow'],
            ['key' => 'blogTitle', 'label' => 'Blog heading'],
            ['legend' => 'Bottom CTA'],
            ['key' => 'ctaEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'ctaTitle', 'label' => 'Heading'],
            ['key' => 'ctaIntro', 'label' => 'Intro', 'type' => 'textarea'],
            ['key' => 'ctaButton', 'label' => 'Button label'],
        ],
        'about' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
            ['legend' => 'Our story'],
            ['key' => 'storyEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'storyTitle', 'label' => 'Heading', 'full' => true],
            ['key' => 'storyBody1', 'label' => 'First paragraph', 'type' => 'textarea', 'hint' => 'Use {foundedYear} to insert the company founded year.'],
            ['key' => 'storyBody2', 'label' => 'Second paragraph', 'type' => 'textarea'],
            ['key' => 'storyImage', 'label' => 'Story image', 'type' => 'image', 'hint' => 'Recommended 900 × 420 px.'],
            ['legend' => 'Approach tiles'],
            ['key' => 'approachEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'approachTitle', 'label' => 'Heading', 'full' => true],
            ['key' => 'approachTiles', 'label' => 'Cards', 'type' => 'approach-tiles', 'full' => true],
            ['legend' => 'Leadership & CTA'],
            ['key' => 'teamEyebrow', 'label' => 'Team eyebrow'],
            ['key' => 'teamTitle', 'label' => 'Team heading'],
            ['key' => 'teamIntro', 'label' => 'Team intro', 'type' => 'textarea'],
            ['key' => 'ctaEyebrow', 'label' => 'CTA eyebrow'],
            ['key' => 'ctaTitle', 'label' => 'CTA heading', 'full' => true],
            ['key' => 'ctaButton', 'label' => 'CTA button'],
        ],
        'contact' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
            ['legend' => 'Columns'],
            ['key' => 'detailsEyebrow', 'label' => 'Details eyebrow'],
            ['key' => 'detailsTitle', 'label' => 'Details heading'],
            ['key' => 'formEyebrow', 'label' => 'Form eyebrow'],
            ['key' => 'formTitle', 'label' => 'Form heading'],
        ],
        'blog' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
        ],
        'products' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'intro', 'label' => 'Intro', 'type' => 'textarea'],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
            ['legend' => 'Bulk supply banner'],
            ['key' => 'bulkEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'bulkTitle', 'label' => 'Heading', 'full' => true],
            ['key' => 'bulkLead', 'label' => 'Intro', 'type' => 'textarea'],
            ['key' => 'bulkPoint1', 'label' => 'Point 1'],
            ['key' => 'bulkPoint2', 'label' => 'Point 2'],
            ['key' => 'bulkPoint3', 'label' => 'Point 3'],
            ['key' => 'bulkMark', 'label' => 'Image label'],
            ['key' => 'bulkImage', 'label' => 'Banner image', 'type' => 'image', 'hint' => 'Left photo on the products page. Recommended 1100 × 720 px.'],
            ['key' => 'bulkButton', 'label' => 'Primary button'],
            ['key' => 'bulkButtonUrl', 'label' => 'Primary button link'],
            ['key' => 'bulkButton2', 'label' => 'Secondary button'],
            ['key' => 'bulkButton2Url', 'label' => 'Secondary button link'],
        ],
        'faq' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
        ],
        'careers' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
            ['legend' => 'Application form'],
            ['key' => 'applyEyebrow', 'label' => 'Eyebrow'],
            ['key' => 'applyTitle', 'label' => 'Heading', 'full' => true],
            ['key' => 'applyIntro', 'label' => 'Intro', 'type' => 'textarea'],
            ['key' => 'applyButton', 'label' => 'Button label'],
        ],
        'privacy' => [
            ['legend' => 'Page header'],
            ['key' => 'eyebrow', 'label' => 'Eyebrow'],
            ['key' => 'title', 'label' => 'Page title', 'full' => true],
            ['key' => 'heroImage', 'label' => 'Hero banner', 'type' => 'image', 'hint' => 'Wide banner behind the page title. Recommended 1920 × 450 px.'],
            ['legend' => 'Policy body'],
            ['key' => 'body', 'label' => 'Privacy policy', 'type' => 'rich', 'hint' => 'Use H1–H3 and Internal link the same way as blog posts.'],
        ],
    ];
    return $fields[$slug] ?? [];
}

function default_faqs(): array
{
    $contact = is_array(setting('contact', [])) ? setting('contact', []) : [];
    $phone = (string) ($contact['phones'][0]['number'] ?? '');
    $quote = $phone !== ''
        ? 'Call ' . $phone . ', email us, or send the form on the contact page. We typically respond within one business day.'
        : 'Email us or send the form on the contact page. We typically respond within one business day.';

    return [
        ['q' => 'Which areas do you supply?', 'a' => 'We supply and install across India from our base in Berhampur, Odisha — including industrial sites, campuses, highways, and government projects.'],
        ['q' => 'Do you only sell products, or do you install as well?', 'a' => 'Both. We supply certified equipment and handle installation, commissioning, and maintenance with our own crews.'],
        ['q' => 'Can I place a bulk or government order?', 'a' => 'Yes. We handle wholesale and project orders with staged delivery. Share your quantity and timeline on the contact page for a scoped quote.'],
        ['q' => 'Are your products certified?', 'a' => 'Product lines are checked against applicable IS, ISO, IRC, and OEM standards before they enter the catalog.'],
        ['q' => 'How do I request a quote?', 'a' => $quote],
        ['q' => 'Do you offer after-sales and emergency support?', 'a' => 'Yes. Maintenance contracts, refills, and 24/7 breakdown response are available for installed systems.'],
    ];
}

function site_faqs(): array
{
    $stored = setting('faqs', null);
    if (!is_array($stored) || !$stored) {
        return default_faqs();
    }
    $out = [];
    foreach ($stored as $item) {
        $q = trim((string) ($item['q'] ?? ''));
        $a = trim((string) ($item['a'] ?? ''));
        if ($q !== '' && $a !== '') {
            $out[] = ['q' => $q, 'a' => $a];
        }
    }
    return $out ?: default_faqs();
}

function faqs_to_text(array $faqs): string
{
    $blocks = [];
    foreach ($faqs as $faq) {
        $blocks[] = $faq['q'] . "\n" . $faq['a'];
    }
    return implode("\n\n", $blocks);
}

function parse_labeled_lines(string $text): array
{
    $out = [];
    foreach (lines_to_array($text) as $line) {
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            $out[] = $parts;
        }
    }
    return $out;
}

function map_category(array $row): array
{
    return [
        'id' => $row['id'],
        'icon' => $row['icon'],
        'name' => $row['name'],
        'desc' => $row['desc'],
        'headline' => $row['headline'],
        'intro' => $row['intro'],
        'buyers' => $row['buyers'],
        'image' => (string) ($row['image'] ?? ''),
        'useCases' => json_list($row['use_cases']),
        'faqs' => json_list($row['faqs']),
        'metaTitle' => (string) ($row['meta_title'] ?? ''),
        'metaDescription' => (string) ($row['meta_description'] ?? ''),
        'metaKeywords' => (string) ($row['meta_keywords'] ?? ''),
        'canonical' => (string) ($row['canonical'] ?? ''),
        'ogImage' => (string) ($row['og_image'] ?? ''),
        'noindex' => (int) ($row['noindex'] ?? 0) === 1,
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
    ];
}

function category_card_image(array $cat): string
{
    foreach (['image', 'ogImage'] as $key) {
        $value = trim((string) ($cat[$key] ?? ''));
        if ($value !== '') {
            return $value;
        }
    }
    return 'assets/img/products.jpg';
}

function map_product(array $row): array
{
    return [
        'slug' => $row['slug'],
        'category' => $row['category'],
        'name' => $row['name'],
        'short' => $row['short'],
        'description' => $row['description'],
        'tags' => json_list($row['tags']),
        'features' => json_list($row['features']),
        'images' => json_list($row['images']),
        'metaTitle' => (string) ($row['meta_title'] ?? ''),
        'metaDescription' => (string) ($row['meta_description'] ?? ''),
        'metaKeywords' => (string) ($row['meta_keywords'] ?? ''),
        'canonical' => (string) ($row['canonical'] ?? ''),
        'ogImage' => (string) ($row['og_image'] ?? ''),
        'noindex' => (int) ($row['noindex'] ?? 0) === 1,
        'brand' => (string) ($row['brand'] ?? ''),
        'sku' => (string) ($row['sku'] ?? ''),
        'gtin' => (string) ($row['gtin'] ?? ''),
        'mpn' => (string) ($row['mpn'] ?? ''),
        'condition' => (string) ($row['item_condition'] ?? ''),
        'availability' => (string) ($row['availability'] ?? ''),
        'price' => (string) ($row['price'] ?? ''),
        'currency' => (string) ($row['currency'] ?? ''),
        'updatedAt' => (string) ($row['updated_at'] ?? ''),
    ];
}

function map_service(array $row): array
{
    return [
        'id' => $row['id'],
        'name' => $row['name'],
        'icon' => $row['icon'],
        'summary' => $row['summary'],
        'points' => json_list($row['points']),
    ];
}

function all_categories(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    $rows = db()->query('SELECT * FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
    return array_map('map_category', $rows);
}

function get_category(string $id): ?array
{
    if (!db_ready()) {
        return null;
    }
    migrate();
    $stmt = db()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_category($row) : null;
}

function all_products_admin(?string $category = null, ?string $search = null): array
{
    migrate();
    $where = [];
    $args = [];

    if ($category !== null && $category !== '') {
        $where[] = 'category = ?';
        $args[] = $category;
    }
    if ($search !== null && trim($search) !== '') {
        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], trim($search)) . '%';
        $where[] = '(name LIKE ? OR slug LIKE ? OR short LIKE ? OR tags LIKE ?)';
        array_push($args, $like, $like, $like, $like);
    }

    $sql = 'SELECT * FROM products';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY sort_order ASC, name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function move_product(int $id, int $direction): bool
{
    $rows = db()->query('SELECT id FROM products ORDER BY sort_order ASC, name ASC')->fetchAll();
    $ids = array_map('intval', array_column($rows, 'id'));
    $index = array_search($id, $ids, true);
    if ($index === false) {
        return false;
    }
    $target = $index + $direction;
    if ($target < 0 || $target >= count($ids)) {
        return false;
    }

    $moved = array_splice($ids, $index, 1);
    array_splice($ids, $target, 0, $moved);

    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
    foreach ($ids as $position => $rowId) {
        $stmt->execute([$position, $rowId]);
    }
    $pdo->commit();
    return true;
}

function all_products(?string $category = null): array
{
    return array_map('map_product', all_products_admin($category));
}

function get_product_by_slug(string $slug): ?array
{
    migrate();
    $stmt = db()->prepare('SELECT * FROM products WHERE slug = ?');
    $stmt->execute([$slug]);
    $row = $stmt->fetch();
    return $row ? map_product($row) : null;
}

function get_product_row(int $id): ?array
{
    migrate();
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function all_services(): array
{
    $rows = db()->query('SELECT * FROM services ORDER BY sort_order ASC, name ASC')->fetchAll();
    return array_map('map_service', $rows);
}

function map_leader(array $row): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'name' => $row['name'] ?? '',
        'designation' => $row['designation'] ?? ($row['role'] ?? ''),
        'experience' => $row['experience'] ?? '',
        'expertise' => $row['expertise'] ?? '',
        'background' => $row['background'] ?? ($row['bio'] ?? ''),
        'linkedin' => $row['linkedin'] ?? '',
        'image' => $row['image'] ?? '',
    ];
}

function all_leadership(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    $rows = db()->query('SELECT * FROM leadership ORDER BY sort_order ASC, id ASC')->fetchAll();
    return array_map('map_leader', $rows);
}

function get_leadership_row(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM leadership WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_leader($row) : null;
}

function leader_initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= strtoupper(substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : 'BL';
}

function render_leadership_cards(): string
{
    $html = '';
    foreach (all_leadership() as $person) {
        $photo = $person['image'] !== ''
            ? '<img src="' . e($person['image']) . '" alt="' . e($person['name']) . '" width="480" height="360" loading="lazy">'
            : '<span class="leader-fallback" aria-hidden="true">' . e(leader_initials($person['name'])) . '</span>';
        $linkedin = $person['linkedin'] !== ''
            ? '<a class="leader-in" href="' . e($person['linkedin']) . '" target="_blank" rel="noopener" aria-label="LinkedIn profile for ' . e($person['name']) . '"><svg width="18" height="18" aria-hidden="true"><use href="assets/img/sprite.svg#icon-linkedin"></use></svg></a>'
            : '';
        $experience = $person['experience'] !== ''
            ? '<div class="leader-meta"><span>Experience</span><p>' . e($person['experience']) . '</p></div>'
            : '';
        $expertise = $person['expertise'] !== ''
            ? '<div class="leader-meta"><span>Area of expertise</span><p>' . e($person['expertise']) . '</p></div>'
            : '';
        $html .= '<article class="leader-card" data-reveal>'
            . '<div class="leader-photo">' . $photo . '</div>'
            . '<div class="leader-body">'
            . '<h3>' . e($person['name']) . '</h3>'
            . '<p class="leader-role">' . e($person['designation']) . '</p>'
            . $experience
            . $expertise
            . '<p class="leader-background">' . e($person['background']) . '</p>'
            . $linkedin
            . '</div></article>';
    }
    return $html;
}

function get_service(string $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? map_service($row) : null;
}

function build_site_config(): array
{
    if (!db_ready()) {
        $seed = json_decode((string) file_get_contents(CMS_SEED), true);
        return is_array($seed) ? $seed : [];
    }

    return [
        'company' => setting('company', []),
        'contact' => setting('contact', []),
        'social' => setting('social', []),
        'seo' => setting('seo', []),
        'stats' => setting('stats', []),
        'leadership' => all_leadership(),
        'categories' => all_categories(),
        'products' => all_products(),
        'services' => all_services(),
        'testimonials' => setting('testimonials', []),
        'blog' => blog_posts(),
        'clients' => all_clients(),
        'faqs' => site_faqs(),
        'hero' => hero_slides(),
    ];
}

function export_site_config_js(): void
{
    $config = build_site_config();
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $js = "window.SITE_CONFIG = {$json};\n";
    $target = CMS_ROOT . '/config/site.config.js';
    file_put_contents($target, $js);
    try {
        export_sitemaps();
    } catch (Throwable $e) {
    }
}

function counts(): array
{
    return [
        'products' => (int) db()->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'services' => (int) db()->query('SELECT COUNT(*) FROM services')->fetchColumn(),
        'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'blog' => count(setting_list('blog')),
        'testimonials' => count(setting_list('testimonials')),
        'leadership' => count(all_leadership()),
        'clients' => count(all_clients()),
        'stats' => count(setting_list('stats')),
        'hero' => count(setting_list('hero')),
        'jobs' => count(all_jobs()),
        'enquiries' => enquiry_count(),
        'enquiriesUnread' => enquiry_unread_count(),
        'comments' => blog_comment_count(),
    ];
}

function enquiry_count(): int
{
    if (!db_ready()) {
        return 0;
    }
    migrate();
    try {
        return (int) db()->query('SELECT COUNT(*) FROM enquiries')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function enquiry_unread_count(): int
{
    if (!db_ready()) {
        return 0;
    }
    migrate();
    try {
        return (int) db()->query('SELECT COUNT(*) FROM enquiries WHERE is_read = 0')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function recent_enquiries(int $limit = 8): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    try {
        $limit = max(1, min(50, $limit));
        return db()->query('SELECT * FROM enquiries ORDER BY created_at DESC, id DESC LIMIT ' . $limit)->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function all_enquiries(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    try {
        return db()->query('SELECT * FROM enquiries ORDER BY created_at DESC, id DESC')->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function get_enquiry(int $id): ?array
{
    if ($id < 1 || !db_ready()) {
        return null;
    }
    migrate();
    $stmt = db()->prepare('SELECT * FROM enquiries WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function mark_enquiry_read(int $id): void
{
    db()->prepare('UPDATE enquiries SET is_read = 1 WHERE id = ?')->execute([$id]);
}

function delete_enquiry(int $id): void
{
    db()->prepare('DELETE FROM enquiries WHERE id = ?')->execute([$id]);
}

function enquiry_ip_limited(string $ip): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM enquiries WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn() >= 8;
}

function save_enquiry(array $data): int
{
    migrate();
    $stmt = db()->prepare(
        'INSERT INTO enquiries (name, company, email, phone, category, message, resume, ip, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())'
    );
    $stmt->execute([
        $data['name'],
        $data['company'],
        $data['email'],
        $data['phone'],
        $data['category'],
        $data['message'],
        (string) ($data['resume'] ?? ''),
        $data['ip'],
    ]);
    unset($_SESSION['quote_captcha']);
    return (int) db()->lastInsertId();
}

function blog_read_minutes(array $post): int
{
    $text = trim((string) ($post['excerpt'] ?? '') . ' ' . (string) ($post['body'] ?? ''));
    $words = str_word_count(strip_tags($text));
    return max(1, (int) ceil($words / 180));
}

function blog_posts(): array
{
    $posts = setting_list('blog');
    usort($posts, static function (array $a, array $b): int {
        $byDate = strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
        if ($byDate !== 0) {
            return $byDate;
        }
        return strcmp((string) ($b['updated'] ?? ''), (string) ($a['updated'] ?? ''));
    });
    return $posts;
}

function popular_blog_posts(array $all, string $excludeSlug = '', int $limit = 6): array
{
    $out = [];
    foreach ($all as $candidate) {
        $slug = (string) ($candidate['slug'] ?? '');
        if ($slug === '' || $slug === $excludeSlug) {
            continue;
        }
        $out[] = $candidate;
    }
    usort($out, static function (array $a, array $b): int {
        return strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? ''));
    });
    return array_slice($out, 0, $limit);
}

function blog_comment_count(?string $slug = null): int
{
    if (!db_ready()) {
        return 0;
    }
    migrate();
    try {
        if ($slug) {
            $stmt = db()->prepare('SELECT COUNT(*) FROM blog_comments WHERE post_slug = ? AND is_approved = 1');
            $stmt->execute([$slug]);
            return (int) $stmt->fetchColumn();
        }
        return (int) db()->query('SELECT COUNT(*) FROM blog_comments')->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function blog_comments(string $slug): array
{
    if ($slug === '' || !db_ready()) {
        return [];
    }
    migrate();
    try {
        $stmt = db()->prepare(
            'SELECT * FROM blog_comments WHERE post_slug = ? AND is_approved = 1 ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute([$slug]);
        return $stmt->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function all_blog_comments(): array
{
    if (!db_ready()) {
        return [];
    }
    migrate();
    try {
        return db()->query('SELECT * FROM blog_comments ORDER BY created_at DESC, id DESC')->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }
}

function blog_comment_ip_limited(string $ip): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM blog_comments WHERE ip = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');
    $stmt->execute([$ip]);
    return (int) $stmt->fetchColumn() >= 5;
}

function save_blog_comment(array $data): int
{
    migrate();
    $stmt = db()->prepare(
        'INSERT INTO blog_comments (post_slug, name, email, body, ip, is_approved, created_at)
         VALUES (?, ?, ?, ?, ?, 1, NOW())'
    );
    $stmt->execute([
        $data['post_slug'],
        $data['name'],
        $data['email'],
        $data['body'],
        $data['ip'],
    ]);
    return (int) db()->lastInsertId();
}

function delete_blog_comment(int $id): void
{
    db()->prepare('DELETE FROM blog_comments WHERE id = ?')->execute([$id]);
}

function article_auto_links(?string $excludePostSlug = null): array
{
    $links = [];
    foreach (all_categories() as $cat) {
        $name = trim((string) ($cat['name'] ?? ''));
        if ($name !== '' && (str_contains($name, ' ') || mb_strlen($name) >= 10)) {
            $links[] = ['label' => $name, 'href' => category_path((string) $cat['id'])];
        }
    }
    foreach (all_products() as $product) {
        $name = trim((string) ($product['name'] ?? ''));
        if (mb_strlen($name) >= 8) {
            $links[] = ['label' => $name, 'href' => product_path((string) $product['slug'])];
        }
    }
    foreach (setting_list('blog') as $item) {
        $slug = (string) ($item['slug'] ?? '');
        if ($slug === '' || $slug === $excludePostSlug) {
            continue;
        }
        $title = trim((string) ($item['title'] ?? ''));
        if (mb_strlen($title) >= 12) {
            $links[] = ['label' => $title, 'href' => post_path($slug)];
        }
    }
    usort($links, static fn (array $a, array $b): int => mb_strlen($b['label']) <=> mb_strlen($a['label']));
    return $links;
}

function related_blog_posts(array $post, array $all, int $limit = 3): array
{
    $current = (string) ($post['slug'] ?? '');
    $bySlug = [];
    foreach ($all as $candidate) {
        $slug = (string) ($candidate['slug'] ?? '');
        if ($slug === '' || $slug === $current) {
            continue;
        }
        $bySlug[$slug] = $candidate;
    }

    $out = [];
    foreach (string_list($post['relatedPosts'] ?? []) as $slug) {
        if (isset($bySlug[$slug])) {
            $out[] = $bySlug[$slug];
            unset($bySlug[$slug]);
        }
        if (count($out) >= $limit) {
            return $out;
        }
    }

    $hay = mb_strtolower(
        (string) ($post['title'] ?? '') . ' ' . (string) ($post['excerpt'] ?? '') . ' ' . (string) ($post['body'] ?? '')
    );
    $scored = [];
    foreach ($bySlug as $candidate) {
        $words = preg_split(
            '/[^\p{L}\p{N}]+/u',
            mb_strtolower((string) ($candidate['title'] ?? '') . ' ' . (string) ($candidate['excerpt'] ?? ''))
        ) ?: [];
        $score = 0;
        foreach ($words as $word) {
            if (mb_strlen($word) < 4) {
                continue;
            }
            if (mb_strpos($hay, $word) !== false) {
                $score++;
            }
        }
        $scored[] = [$score, $candidate];
    }
    usort($scored, static fn (array $a, array $b): int => $b[0] <=> $a[0]);
    foreach ($scored as $row) {
        $out[] = $row[1];
        if (count($out) >= $limit) {
            break;
        }
    }
    return $out;
}

function related_blog_products(array $post): array
{
    $out = [];
    foreach (string_list($post['relatedProducts'] ?? []) as $slug) {
        $product = get_product_by_slug($slug);
        if ($product) {
            $out[] = $product;
        }
        if (count($out) >= 4) {
            break;
        }
    }
    return $out;
}

function related_blog_categories(array $post): array
{
    $out = [];
    foreach (string_list($post['relatedCategories'] ?? []) as $id) {
        $cat = get_category($id);
        if ($cat) {
            $out[] = $cat;
        }
    }
    return $out;
}
