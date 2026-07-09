<?php

declare(strict_types=1);

define('HEALTH_CHECK_SECRET', '');
define('HEALTH_CHECK_HEADER', 'Authorization');
define('HEALTH_CHECK_TIMEOUT', 5);
define('HEALTH_CHECK_CACHE_TTL', 300);
define('HEALTH_CHECK_PLUGIN_FILE', __DIR__ . '/health-check.php');
