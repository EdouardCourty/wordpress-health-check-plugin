# Health Check WordPress Plugin

[![CI](https://github.com/EdouardCourty/wordpress-health-check-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/EdouardCourty/wordpress-health-check-plugin/actions/workflows/ci.yml)

A WordPress plugin providing a `/wp-json/health-check/v1/health` endpoint to monitor your application and its dependencies. Inspired by the Symfony [health-check-bundle](https://github.com/LouisGarret/health-check-bundle).

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [REST API endpoint](#rest-api-endpoint)
  - [WP-CLI commands](#wp-cli-commands)
  - [Creating a custom check](#creating-a-custom-check)
- [Built-in checks](#built-in-checks)
- [Development](#development)
  - [Making a release](#making-a-release)
- [License](#license)

---

## Requirements

- PHP **≥ 8.1**
- WordPress **≥ 6.0**

---

## Installation

1. Download `health-check-wordpress.zip` from the [latest release](https://github.com/EdouardCourty/wordpress-health-check-plugin/releases)
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Select the zip file and click **Install Now**
4. **Activate** the plugin

The endpoint is immediately available at `/wp-json/health-check/v1/health`.

### Development installation

```bash
cd wp-content/plugins/
git clone https://github.com/EdouardCourty/wordpress-health-check-plugin.git
cd wordpress-health-check-plugin
composer install
```

---

## Configuration

Configure the plugin by adding constants to your `wp-config.php`:

```php
define('HEALTH_CHECK_SECRET', 'my-secret-token');    // Token for detailed results
define('HEALTH_CHECK_HEADER', 'X-Health-Token');      // Custom header name
define('HEALTH_CHECK_TIMEOUT', 5);                     // Seconds per check
define('HEALTH_CHECK_CACHE_TTL', 300);                 // Seconds (0 = disabled)
```

| Constant | Default | Description |
|---|---|---|
| `HEALTH_CHECK_SECRET` | `null` | Secret token expected in the HTTP header. If `null`, detailed results are never exposed. |
| `HEALTH_CHECK_HEADER` | `Authorization` | Name of the HTTP header used to send the secret token. |
| `HEALTH_CHECK_TIMEOUT` | `5` | Maximum execution time in seconds for each individual check. |
| `HEALTH_CHECK_CACHE_TTL` | `300` | Cache TTL in seconds. Set to `0` to disable caching. |

---

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

### Creating a custom check

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

---

## Built-in checks

| Check | What it does |
|---|---|
| `database` | Runs `SELECT 1` on the WordPress database via `$wpdb->get_results()` |

---

## Development

```bash
composer install
composer test                  # Run tests
composer phpstan               # Static analysis
composer cs-check              # Code style check
composer cs-fix                # Auto-fix code style
```

### Making a release

Trigger a release entirely from the GitHub UI:

1. Go to **Actions → Bump & Release → Run workflow**
2. Enter the version number (e.g. `1.0.2`)
3. Click **Run workflow**

The workflow will:

1. Run quality checks (PHPStan, CS fixer, PHPUnit) on PHP 8.2–8.4
2. Update the version in `health-check.php`
3. Commit and tag
4. Build a production zip via `git archive` (dev files excluded)
5. Create a GitHub Release with the zip attached

Or from the command line:

```bash
composer release 1.0.2
```

---

## License

This plugin is released under the [MIT License](LICENSE).
