<?php
declare(strict_types=1);

define('CMS_ROOT', dirname(__DIR__));
define('CMS_DATA', __DIR__ . '/data');
define('CMS_SEED', CMS_DATA . '/seed.json');
define('CMS_UPLOADS', CMS_ROOT . '/assets/img/uploads');
define('CMS_UPLOADS_URL', 'assets/img/uploads');

require_once __DIR__ . '/lib/connections/load.php';

require_once __DIR__ . '/lib/auth/security.php';

if (!defined('CMS_PUBLIC_CONFIG')) {
    cms_start_session();
}

require_once __DIR__ . '/lib/core/helpers.php';
require_once __DIR__ . '/lib/uploads/upload.php';
require_once __DIR__ . '/lib/connections/db.php';
require_once __DIR__ . '/lib/auth/auth.php';
require_once __DIR__ . '/lib/content/content.php';
require_once __DIR__ . '/lib/seo/seo.php';
