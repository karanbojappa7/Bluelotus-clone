<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function admin_base(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $pos = strpos($script, '/admin/');
    if ($pos !== false) {
        return substr($script, 0, $pos) . '/admin';
    }
    return rtrim(str_replace('\\', '/', dirname($script)), '/');
}

function admin_url(string $path = ''): string
{
    return admin_base() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function take_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-') ?: 'item';
}

function lines_to_array(string $text): array
{
    $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function array_to_lines(array $items): string
{
    return implode("\n", array_map('strval', $items));
}

function json_list(?string $json): array
{
    if ($json === null || $json === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function field_class(array $errors, string $key, bool $full = false): string
{
    $classes = $full ? ['full'] : [];
    if (isset($errors[$key])) {
        $classes[] = 'field-error';
    }
    return implode(' ', $classes);
}

function field_msg(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-msg">' . e($errors[$key]) . '</span>' : '';
}

function parse_blocks(string $text, string $firstKey, string $restKey): array
{
    $out = [];
    foreach (preg_split('/\R\s*\R/', trim($text)) ?: [] as $block) {
        $lines = lines_to_array($block);
        if (count($lines) < 2) {
            continue;
        }
        $first = array_shift($lines);
        $out[] = [$firstKey => $first, $restKey => implode(' ', $lines)];
    }
    return $out;
}

function format_blocks(array $items, string $firstKey, string $restKey): string
{
    $blocks = [];
    foreach ($items as $item) {
        $blocks[] = ($item[$firstKey] ?? '') . "\n" . ($item[$restKey] ?? '');
    }
    return implode("\n\n", $blocks);
}

function truncate(string $text, int $width = 90): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($text, 0, $width, '…');
    }
    if (strlen($text) <= $width) {
        return $text;
    }
    return substr($text, 0, max(0, $width - 1)) . '…';
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) ? trim((string) $_POST[$key]) : $default;
}

function sanitize_hex_color(string $value, string $fallback = '#ffffff'): string
{
    $value = trim($value);
    if (preg_match('/^#([0-9a-fA-F]{3})$/', $value, $m) === 1) {
        $h = $m[1];
        return '#' . strtolower($h[0] . $h[0] . $h[1] . $h[1] . $h[2] . $h[2]);
    }
    if (preg_match('/^#([0-9a-fA-F]{6})$/', $value) === 1) {
        return strtolower($value);
    }
    return $fallback;
}

function post_slug_list(string $key): array
{
    $raw = $_POST[$key] ?? [];
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $value) {
        $value = trim((string) $value);
        if ($value !== '' && preg_match('/^[a-z0-9\-]+$/', $value) === 1) {
            $out[] = $value;
        }
    }
    return array_values(array_unique($out));
}

function string_list($value): array
{
    if (!is_array($value)) {
        return [];
    }
    $out = [];
    foreach ($value as $item) {
        $item = trim((string) $item);
        if ($item !== '') {
            $out[] = $item;
        }
    }
    return array_values(array_unique($out));
}

function maps_embed_src(?string $url): string
{
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }
    if (preg_match('#src=["\'](https://[^"\']+)["\']#i', $url, $m) === 1) {
        $url = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    $url = trim($url);
    if (preg_match('#^https://(www\.)?(google\.com/maps/embed|maps\.google\.com/)#i', $url) !== 1) {
        return '';
    }
    return $url;
}

function contact_maps_from_legacy(array $contact): array
{
    $slots = [
        ['label' => 'mapLabel', 'url' => 'mapEmbedUrl'],
        ['label' => 'mapLabel2', 'url' => 'mapEmbedUrl2'],
        ['label' => 'mapLabel3', 'url' => 'mapEmbedUrl3'],
        ['label' => 'mapLabel4', 'url' => 'mapEmbedUrl4'],
    ];
    $out = [];
    foreach ($slots as $i => $slot) {
        $src = maps_embed_src((string) ($contact[$slot['url']] ?? ''));
        if ($src === '') {
            continue;
        }
        $label = trim((string) ($contact[$slot['label']] ?? ''));
        $out[] = [
            'label' => $label !== '' ? $label : ('Location ' . (count($out) + 1)),
            'url' => $src,
        ];
    }
    return $out;
}

function contact_maps(?array $contact = null): array
{
    if ($contact === null) {
        $contact = is_array(setting('contact', [])) ? setting('contact', []) : [];
    }
    $rows = [];
    $stored = $contact['maps'] ?? null;
    if (is_array($stored) && $stored) {
        foreach ($stored as $row) {
            if (!is_array($row)) {
                continue;
            }
            $src = maps_embed_src((string) ($row['url'] ?? $row['src'] ?? ''));
            if ($src === '') {
                continue;
            }
            $label = trim((string) ($row['label'] ?? ''));
            $rows[] = [
                'label' => $label !== '' ? $label : ('Location ' . (count($rows) + 1)),
                'src' => $src,
            ];
        }
    }
    if (!$rows) {
        foreach (contact_maps_from_legacy($contact) as $row) {
            $rows[] = [
                'label' => $row['label'],
                'src' => $row['url'],
            ];
        }
    }
    return $rows;
}

function contact_map_editor_rows(array $contact): array
{
    $stored = $contact['maps'] ?? null;
    if (is_array($stored) && $stored) {
        $out = [];
        foreach ($stored as $row) {
            if (!is_array($row)) {
                continue;
            }
            $out[] = [
                'label' => (string) ($row['label'] ?? ''),
                'url' => (string) ($row['url'] ?? $row['src'] ?? ''),
            ];
        }
        if ($out) {
            return $out;
        }
    }
    $legacy = contact_maps_from_legacy($contact);
    return $legacy ?: [['label' => '', 'url' => '']];
}

function quote_captcha_issue(): array
{
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['quote_captcha'] = $a + $b;
    return ['a' => $a, 'b' => $b];
}

function quote_captcha_ok(string $answer): bool
{
    $expected = $_SESSION['quote_captcha'] ?? null;
    $digits = preg_replace('/\D/', '', $answer) ?? '';
    if ($expected === null || $digits === '') {
        return false;
    }
    return (int) $digits === (int) $expected;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('Invalid security token. Go back and try again.');
    }
}

function paginate_items(array $items, int $perPage = 12, string $param = 'page'): array
{
    $total = count($items);
    $pages = max(1, (int) ceil($total / max(1, $perPage)));
    $page = isset($_GET[$param]) ? (int) $_GET[$param] : 1;
    if ($page < 1) {
        $page = 1;
    }
    if ($page > $pages) {
        $page = $pages;
    }
    $offset = ($page - 1) * $perPage;
    return [
        'items' => array_slice($items, $offset, $perPage),
        'page' => $page,
        'pages' => $pages,
        'total' => $total,
        'per_page' => $perPage,
        'from' => $total ? $offset + 1 : 0,
        'to' => min($offset + $perPage, $total),
    ];
}

function pagination_query(array $extra = []): string
{
    $query = array_merge($_GET, $extra);
    unset($query['page']);
    foreach ($query as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        }
    }
    return http_build_query($query);
}

function render_pagination(array $pager, string $base = ''): string
{
    if ($pager['pages'] <= 1) {
        return '';
    }
    $baseQuery = pagination_query();
    $prefix = $base . ($baseQuery !== '' ? '?' . $baseQuery . '&' : '?');
    $html = '<nav class="pagination" aria-label="Pagination"><ul>';
    $page = $pager['page'];
    $pages = $pager['pages'];

    $prevDisabled = $page <= 1 ? ' is-disabled' : '';
    $prevHref = $page <= 1 ? '#' : $prefix . 'page=' . ($page - 1);
    $html .= '<li><a class="pagination-btn' . $prevDisabled . '" href="' . e($prevHref) . '"' . ($page <= 1 ? ' aria-disabled="true"' : '') . '>Prev</a></li>';

    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 9 && abs($i - $page) > 2 && $i !== 1 && $i !== $pages) {
            if ($i === 2 || $i === $pages - 1) {
                $html .= '<li><span class="pagination-ellipsis">…</span></li>';
            }
            continue;
        }
        $active = $i === $page ? ' is-active' : '';
        $html .= '<li><a class="pagination-btn' . $active . '" href="' . e($prefix . 'page=' . $i) . '">' . $i . '</a></li>';
    }

    $nextDisabled = $page >= $pages ? ' is-disabled' : '';
    $nextHref = $page >= $pages ? '#' : $prefix . 'page=' . ($page + 1);
    $html .= '<li><a class="pagination-btn' . $nextDisabled . '" href="' . e($nextHref) . '"' . ($page >= $pages ? ' aria-disabled="true"' : '') . '>Next</a></li>';
    $html .= '</ul></nav>';
    return $html;
}
