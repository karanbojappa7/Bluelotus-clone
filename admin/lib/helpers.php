<?php
declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
