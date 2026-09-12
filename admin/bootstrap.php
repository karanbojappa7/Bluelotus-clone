<?php
declare(strict_types=1);

define('CMS_ROOT', dirname(__DIR__));
define('CMS_DATA', __DIR__ . '/data');
define('CMS_SEED', CMS_DATA . '/seed.json');
define('CMS_UPLOADS', CMS_ROOT . '/assets/img/uploads');
define('CMS_UPLOADS_URL', 'assets/img/uploads');

define('CMS_DB_HOST', 'localhost');
define('CMS_DB_PORT', '3306');
define('CMS_DB_NAME', 'bluelotus');
define('CMS_DB_USER', 'root');
define('CMS_DB_PASS', '');

if (!defined('CMS_PUBLIC_CONFIG') && session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/upload.php';
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/content.php';
