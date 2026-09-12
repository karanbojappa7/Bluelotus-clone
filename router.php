<?php
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rawurldecode($uri);

$denied = [
    '/composer.json',
    '/composer.lock',
    '/package.json',
    '/package-lock.json',
    '/README.md',
    '/.gitignore',
];
foreach ($denied as $path) {
    if (strcasecmp($uri, $path) === 0) {
        http_response_code(403);
        echo 'Forbidden';
        return true;
    }
}
if (preg_match('#config\\.local\\.php$#i', $uri) === 1) {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}

$file = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && is_file($file)) {
    return false;
}

if ($uri !== '/' && is_dir($file)) {
    foreach (['index.php', 'index.html'] as $index) {
        $candidate = $file . DIRECTORY_SEPARATOR . $index;
        if (is_file($candidate)) {
            $script = rtrim($uri, '/') . '/' . $index;
            dispatch($script);
            return true;
        }
    }
}

if ($uri === '/' || $uri === '') {
    dispatch('/index.php');
    return true;
}

if (preg_match('#^/services(\.(html|php))?/?$#', $uri) === 1) {
    header('Location: /#services', true, 301);
    return true;
}

if ($uri === '/sitemap.xml') {
    dispatch('/sitemap.php');
    return true;
}
if ($uri === '/robots.txt') {
    dispatch('/robots.php');
    return true;
}

if (preg_match('#^/product/([^/]+)/?$#', $uri, $m) === 1) {
    $_GET['slug'] = $m[1];
    dispatch('/product.php');
    return true;
}
if (preg_match('#^/products/?$#', $uri) === 1) {
    dispatch('/products.php');
    return true;
}
if (preg_match('#^/products/([^/]+)/?$#', $uri, $m) === 1) {
    $_GET['cat'] = $m[1];
    dispatch('/category.php');
    return true;
}
if (preg_match('#^/blog/?$#', $uri) === 1) {
    dispatch('/blog.php');
    return true;
}
if (preg_match('#^/blog/([^/]+)/?$#', $uri, $m) === 1) {
    $_GET['post'] = $m[1];
    dispatch('/blog-single.php');
    return true;
}
if (preg_match('#^/(about|contact|faq|privacy-policy)/?$#', $uri, $m) === 1) {
    dispatch('/' . $m[1] . '.php');
    return true;
}

http_response_code(404);
dispatch('/404.php');
return true;

function dispatch(string $script): void
{
    $_SERVER['SCRIPT_NAME'] = $script;
    $_SERVER['PHP_SELF'] = $script;
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $script);
    require $_SERVER['SCRIPT_FILENAME'];
}
