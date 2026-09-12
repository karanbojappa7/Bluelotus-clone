<?php
declare(strict_types=1);

define('CMS_PUBLIC_CONFIG', true);
require_once dirname(__DIR__) . '/admin/bootstrap.php';

header('Content-Type: application/javascript; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    $config = build_site_config();
    echo 'window.SITE_CONFIG = ' . json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ";\n";
} catch (Throwable $e) {
    $fallback = CMS_ROOT . '/config/site.config.js';
    if (is_file($fallback)) {
        readfile($fallback);
        exit;
    }
    http_response_code(500);
    echo 'window.SITE_CONFIG = {}; console.error("CMS config failed");';
}
