#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

PORT="${PORT:-8080}"
sed -ri "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -ri "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# Ensure runtime directories and volume permissions exist
mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache/data storage/app/public storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Fallback APP_KEY if not yet configured in environment
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi

# Fallback to temporary SQLite database if MySQL variables are not yet attached
if [ -z "${DB_HOST:-}" ] && [ -z "${DB_URL:-}" ] && [ "${DB_CONNECTION:-}" != "mysql" ]; then
    export DB_CONNECTION=sqlite
    export DB_DATABASE=/tmp/database.sqlite
    touch /tmp/database.sqlite
fi

# Wait briefly for MySQL if using MySQL
if [ "${DB_CONNECTION:-}" = "mysql" ] || [ -n "${DB_URL:-}" ]; then
    echo "Waiting for database connection..."
    for i in $(seq 1 20); do
        if php -r '
            try {
                $url = getenv("DB_URL");
                if ($url) {
                    $p = parse_url($url);
                    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", $p["host"], $p["port"] ?? 3306, ltrim($p["path"] ?? "", "/"));
                    new PDO($dsn, urldecode($p["user"] ?? ""), urldecode($p["pass"] ?? ""));
                } else {
                    $dsn = sprintf("mysql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: 3306, getenv("DB_DATABASE"));
                    new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD") ?: "");
                }
                exit(0);
            } catch (Throwable $e) {
                exit(1);
            }
        '; then
            echo "Database is ready."
            break
        fi
        sleep 2
    done
fi

# Refresh storage symlink and run idempotent migrations + master seeder
php artisan storage:link --force || true
php artisan migrate --force
php artisan db:seed --force

# Cache configuration, routes, and views for runtime performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure single Apache MPM (prefork for mod_php)
rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*

exec apache2-foreground

