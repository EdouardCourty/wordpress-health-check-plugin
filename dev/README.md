# Local WordPress dev environment

Spins up a real WordPress + MariaDB stack via Docker, with this repository
mounted as the `health-check-wordpress` plugin, so you can click through the
admin settings screen for real instead of guessing from the code.

## Requirements

- Docker (with the `docker compose` CLI plugin)
- Composer (the commands below are `composer` scripts, see `composer.json`)

## Quick start

```bash
composer dev:up
```

This starts the containers, waits for MySQL and WordPress core to be ready,
installs WordPress (first run only) and activates the plugin. It prints the
site URL and admin credentials when done:

- Site: http://localhost:8089
- Admin: http://localhost:8089/wp-admin (`admin` / `admin`)
- Settings screen: **Plugins** list → **Health Check** → **Réglages**

## Live editing

The plugin directory is bind-mounted from the repo root into the container.
PHP changes on your machine are visible immediately — just refresh the
browser, no rebuild or restart needed.

The only thing NOT live is the health check result cache (a WordPress
transient with a TTL, see `HEALTH_CHECK_CACHE_TTL`). Clear it with:

```bash
composer dev:cache-clear
```

## Other commands

```bash
composer dev:cli -- plugin list    # run any wp-cli command
composer dev:logs                  # follow the WordPress container logs
composer dev:restart               # restart the WordPress container
composer dev:down                  # stop the stack, keep the database
composer dev:destroy               # stop the stack and wipe all data
```

## Testing the admin screen's "locked by constant" behavior

To see the read-only/locked state (when `HEALTH_CHECK_SECRET` or
`HEALTH_CHECK_HEADER` is set via `wp-config.php`), add a `define(...)` line
to the WordPress container's `wp-config.php`:

```bash
composer dev:cli -- config set HEALTH_CHECK_SECRET my-test-secret --type=constant
```

Remove it again with:

```bash
composer dev:cli -- config delete HEALTH_CHECK_SECRET
```
