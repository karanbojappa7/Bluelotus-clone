<?php
declare(strict_types=1);

function cms_environment_name(): string
{
    $env = getenv('CMS_ENV');
    return $env !== false && $env !== '' ? $env : 'local';
}

$cmsLocalOverride = __DIR__ . '/../../config.local.php';
$cmsUsedLocalOverride = is_file($cmsLocalOverride);

if ($cmsUsedLocalOverride) {
    require_once $cmsLocalOverride;
} else {
    $cmsEnvFile = __DIR__ . '/environments/' . cms_environment_name() . '.php';
    if (is_file($cmsEnvFile)) {
        require_once $cmsEnvFile;
    }
}

defined('CMS_DB_HOST') || define('CMS_DB_HOST', getenv('CMS_DB_HOST') ?: 'localhost');
defined('CMS_DB_PORT') || define('CMS_DB_PORT', getenv('CMS_DB_PORT') ?: '3306');
defined('CMS_DB_NAME') || define('CMS_DB_NAME', getenv('CMS_DB_NAME') ?: 'bluelotus');
defined('CMS_DB_USER') || define('CMS_DB_USER', getenv('CMS_DB_USER') ?: 'root');
defined('CMS_DB_PASS') || define('CMS_DB_PASS', getenv('CMS_DB_PASS') ?: '');

define('CMS_CONNECTION_SOURCE', $cmsUsedLocalOverride ? 'config.local.php' : cms_environment_name() . '.php');

if (cms_environment_name() === 'production' && CMS_DB_PASS === '' && !$cmsUsedLocalOverride) {
    http_response_code(500);
    exit(
        'CMS_ENV=production is set but no database password is configured. Add CMS_DB_PASS to '
        . 'admin/config.local.php on this server (never commit it), or set the CMS_DB_PASS '
        . 'environment variable. See admin/lib/connections/environments/production.php for details.'
    );
}

unset($cmsLocalOverride, $cmsUsedLocalOverride);
