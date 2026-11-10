#!/bin/sh
set -eu

PORT="${PORT:-80}"

# Render supplies PORT; make Apache listen on it.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Writable runtime folders (container filesystem is ephemeral; uploads are
# stored in Supabase Storage when the service role key is configured).
mkdir -p /var/www/html/uploads/licenses /var/www/html/uploads/medicines /var/www/html/logs
chown -R www-data:www-data /var/www/html/uploads /var/www/html/logs 2>/dev/null || true

exec apache2-foreground
