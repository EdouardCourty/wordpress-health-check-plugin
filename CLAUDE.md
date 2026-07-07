# CLAUDE.md

Guidance for Claude Code when working on this project.

## What this is

A WordPress plugin (`lgarret/health-check-wordpress`) that exposes a `/wp-json/health-check/v1/health` REST API endpoint reporting application/dependency status. PHP >=8.1, WordPress >=6.0.

## Commands

```bash
composer test       # vendor/bin/phpunit
composer test-coverage  # phpunit --coverage-text
```

CI (`.github/workflows/ci.yml`) runs on PHP 8.1/8.2/8.3/8.4: `composer validate --strict`, then `composer test`.

## Architecture

**Request flow**: On plugin load, `Plugin` hooks into `rest_api_init` to register a single GET route at `/wp-json/health-check/v1/health` pointing to `HealthCheckEndpoint::handle()`. The endpoint calls `HealthCheckService::runAll()`, then decides response shape based on auth:
- No/invalid secret header → only `{"status": "ok"|"ko"}` is exposed (200/503).
- Valid secret header (compared via `hash_equals`, header name configurable via `HEALTH_CHECK_HEADER` constant, default `Authorization`) → full per-check breakdown is included.

**Check discovery**: Any plugin can add checks through the `health_check_checks` filter. The callback receives an array of objects implementing `HealthCheckInterface` (`getName()`, `check(): HealthCheckResult`). A built-in `DatabaseCheck` is always registered (runs `SELECT 1` via `$wpdb`). The `Plugin::collectChecks()` method collects via the filter and validates the interface.

**Execution & caching**: `HealthCheckService::executeChecks()` runs every registered check, catching `\Throwable` per-check (a failing check never aborts the others) and enforcing the configured per-check `timeout` by measuring elapsed wall-clock time (note: this does not actually interrupt a hanging check, it only flags it as KO after the fact). The aggregate result (global status + per-check status/error) is cached under the transient key `health_check_result`. Caching is skipped if `cacheTtl` is 0 (or `HEALTH_CHECK_CACHE_TTL=0`).

**Status values**: `HealthStatus` is a string-backed enum (`Ok = 'ok'`, `Ko = 'ko'`) used everywhere instead of raw strings.

**Configuration**: All via `wp-config.php` constants:
- `HEALTH_CHECK_SECRET` (default: null — details never exposed)
- `HEALTH_CHECK_HEADER` (default: `Authorization`)
- `HEALTH_CHECK_TIMEOUT` (default: 5 — seconds per check)
- `HEALTH_CHECK_CACHE_TTL` (default: 300 — seconds, 0 disables caching)

## Conventions

- `declare(strict_types=1)` everywhere, PHP 8.1 features used (enums, readonly classes, union types, named arguments, match).
- Final classes/readonly DTOs throughout — match this when adding new checks or features.
- Tests use Brain Monkey (`brain/monkey`) to mock WordPress global functions, and PHP stubs for `WP_REST_Request`, `WP_REST_Response`, and `WP_CLI`.
- New built-in checks: add a class implementing `HealthCheckInterface`, register it in `Plugin::collectChecks()`, and document it in README.
