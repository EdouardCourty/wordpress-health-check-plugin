# Health Check WordPress Plugin

A WordPress plugin providing a `/wp-json/health-check/v1/health` endpoint to monitor your application and its dependencies. Inspired by the Symfony [health-check-bundle](https://github.com/LouisGarret/health-check-bundle).

## Requirements

- PHP 8.1 or higher
- WordPress 6.0 or higher

## Installation

### Standard installation

1. Upload the `health-check-wordpress` folder to `/wp-content/plugins/`
2. Activate the "Health Check" plugin from the WordPress admin
3. The endpoint is immediately available at `/wp-json/health-check/v1/health`

### Development installation

```bash
cd wp-content/plugins/
git clone https://github.com/LouisGarret/health-check-wordpress.git
cd health-check-wordpress
composer install
```

## Configuration

Configure the plugin by adding constants to your `wp-config.php`:

```php
define('HEALTH_CHECK_SECRET', 'my-secret-token');    // Token for detailed results
define('HEALTH_CHECK_HEADER', 'X-Health-Token');      // Custom header name
define('HEALTH_CHECK_TIMEOUT', 5);                     // Seconds per check
define('HEALTH_CHECK_CACHE_TTL', 300);                 // Seconds (0 = disabled)
```

### Reference

| Constant | Default | Description |
|---|---|---|
| `HEALTH_CHECK_SECRET` | `null` | Secret token expected in the HTTP header. If `null`, detailed results are never exposed. |
| `HEALTH_CHECK_HEADER` | `Authorization` | Name of the HTTP header used to send the secret token. |
| `HEALTH_CHECK_TIMEOUT` | `5` | Maximum execution time in seconds for each individual check. |
| `HEALTH_CHECK_CACHE_TTL` | `300` | Cache TTL in seconds. Set to `0` to disable caching. |

## Usage

### REST API endpoint

**Without auth header** (or without a configured secret):

```
GET /wp-json/health-check/v1/health
→ 200 {"status":"ok"}
→ 503 {"status":"ko"}
```

**With a valid auth header**:

```
GET /wp-json/health-check/v1/health
X-Health-Token: my-secret-token

→ 200 {"status":"ok","checks":{"database":{"status":"ok"}}}
→ 503 {"status":"ko","checks":{"database":{"status":"ok"},"redis":{"status":"ko","error":"Connection refused"}}}
```

### WP-CLI commands

Run all checks from the command line:

```bash
wp health check
```

Output:
```
Health Check
============

+----------+--------+-------------------+
| Check    | Status | Error             |
+----------+--------+-------------------+
| database | OK     |                   |
| redis    | KO     | Connection refused|
+----------+--------+-------------------+

Error: 1 of 2 check(s) failed.
```

Clear the cached health check results:

```bash
wp health cache clear
```

## Creating a custom check

Other plugins can add checks through the `health_check_checks` filter:

```php
<?php
/**
 * Plugin Name: My Health Check
 */

add_filter('health_check_checks', function (array $checks): array {
    $checks[] = new class implements \HealthCheck\Check\HealthCheckInterface {
        public function getName(): string
        {
            return 'redis';
        }

        public function check(): \HealthCheck\Dto\HealthCheckResult
        {
            try {
                // Your check logic here
                return \HealthCheck\Dto\HealthCheckResult::ok();
            } catch (\Throwable $e) {
                return \HealthCheck\Dto\HealthCheckResult::ko($e->getMessage());
            }
        }
    };

    return $checks;
});
```

### Built-in checks

| Check | What it does |
|---|---|
| `database` | Runs `SELECT 1` on the WordPress database via `$wpdb->get_results()` |

## Development

```bash
composer install
composer test                  # Run tests
composer phpstan               # Static analysis
composer cs-check              # Code style check
```

### Making a release

```bash
composer release 1.0.1
```

This bumps the version in `health-check.php`, commits, tags, and pushes. CI then:

1. Runs quality checks (PHPStan, CS fixer, PHPUnit) on PHP 8.1–8.4
2. Builds a production zip via `git archive` (excluding dev files)
3. Creates a GitHub Release with the zip attached

## License

MIT
