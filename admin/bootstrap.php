<?php
declare(strict_types=1);

define('CMS_ROOT', dirname(__DIR__));
define('CMS_DATA', __DIR__ . '/data');
define('CMS_SEED', CMS_DATA . '/seed.json');
define('CMS_UPLOADS', CMS_ROOT . '/assets/img/uploads');
define('CMS_UPLOADS_URL', 'assets/img/uploads');

if (is_file(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

defined('CMS_DB_HOST') || define('CMS_DB_HOST', getenv('CMS_DB_HOST') ?: 'localhost');
defined('CMS_DB_PORT') || define('CMS_DB_PORT', getenv('CMS_DB_PORT') ?: '3306');
defined('CMS_DB_NAME') || define('CMS_DB_NAME', getenv('CMS_DB_NAME') ?: 'bluelotus');
defined('CMS_DB_USER') || define('CMS_DB_USER', getenv('CMS_DB_USER') ?: 'root');
defined('CMS_DB_PASS') || define('CMS_DB_PASS', getenv('CMS_DB_PASS') ?: '');

require_once __DIR__ . '/lib/security.php';

if (!defined('CMS_PUBLIC_CONFIG')) {
    cms_start_session();
}

require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/upload.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/content.php';
require_once __DIR__ . '/lib/seo.php';
