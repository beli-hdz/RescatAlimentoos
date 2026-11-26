#!/bin/sh
set -e

# Render indica el puerto en la variable PORT (por defecto 10000)
PORT="${PORT:-10000}"
sed -i "s/^Listen 80$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Índices y primer super admin (si falla, la app arranca igual y el error queda en los logs)
php /var/www/html/scripts/setup_mongodb.php || echo "[aviso] No se pudo preparar MongoDB. Revisa MONGODB_URI y el acceso de red en Atlas."

exec apache2-foreground
