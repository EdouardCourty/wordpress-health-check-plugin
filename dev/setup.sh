#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

SITE_URL="http://localhost:8089"
SITE_TITLE="Health Check Dev"
ADMIN_USER="admin"
ADMIN_PASSWORD="admin"
ADMIN_EMAIL="admin@example.test"

docker compose up -d

echo "Waiting for the database..."
until docker compose exec -T db mariadb-admin ping -h localhost -uwordpress -pwordpress --silent >/dev/null 2>&1; do
    sleep 1
done

echo "Waiting for WordPress core files..."
until docker compose exec -T wpcli wp core version --path=/var/www/html >/dev/null 2>&1; do
    sleep 1
done

if docker compose exec -T wpcli wp core is-installed --path=/var/www/html >/dev/null 2>&1; then
    echo "WordPress already installed."
else
    echo "Installing WordPress..."
    docker compose exec -T wpcli wp core install \
        --path=/var/www/html \
        --url="$SITE_URL" \
        --title="$SITE_TITLE" \
        --admin_user="$ADMIN_USER" \
        --admin_password="$ADMIN_PASSWORD" \
        --admin_email="$ADMIN_EMAIL" \
        --skip-email
fi

echo "Activating the Health Check plugin..."
docker compose exec -T wpcli wp plugin activate health-check-wordpress --path=/var/www/html

cat <<EOF

Ready.
  Site:     $SITE_URL
  Admin:    $SITE_URL/wp-admin (user: $ADMIN_USER / pass: $ADMIN_PASSWORD)
  Settings: Plugins list -> "Health Check" -> "Réglages"

Plugin files are bind-mounted from the repo root: PHP changes are live
immediately, just refresh the browser. Use the composer dev:* scripts for
common commands (dev:cache-clear, dev:cli, dev:logs, dev:down, dev:destroy).
EOF
