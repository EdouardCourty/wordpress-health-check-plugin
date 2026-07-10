<?php
/**
 * Plugin Name: Health Check
 * Plugin URI: https://github.com/EdouardCourty/wordpress-health-check-plugin
 * Description: Provides a /wp-json/health-check/v1/health endpoint to monitor application and dependency status, with optional auth-gated detailed results and WP-CLI commands.
 * Version: 1.1.0
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * Author: Edouard Courty
 * Author URI: https://github.com/EdouardCourty
 * License: MIT
 * Network: true
 */

declare(strict_types=1);

define('HEALTH_CHECK_VERSION', '1.1.0');
define('HEALTH_CHECK_PLUGIN_FILE', __FILE__);

spl_autoload_register(function (string $class): void {
    $prefix = 'HealthCheck\\';
    $baseDir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

add_action('plugins_loaded', function (): void {
    $plugin = new \HealthCheck\Plugin();
    $plugin->init();
});
