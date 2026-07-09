<?php

declare(strict_types=1);

require_once __DIR__ . '/stubs/wp-rest-api.php';
require_once __DIR__ . '/stubs/wp-cli.php';

require_once __DIR__ . '/../vendor/autoload.php';

define('HEALTH_CHECK_PLUGIN_FILE', __DIR__ . '/../health-check.php');
