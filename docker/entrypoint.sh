#!/usr/bin/env bash
# Prepara e instala a aplicação automaticamente na primeira execução.
set -euo pipefail
cd /var/www/html

echo "[casino] aguardando o banco de dados (${DB_HOST}:${DB_PORT})..."
for i in $(seq 1 60); do
  if mariadb-admin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent 2>/dev/null; then break; fi
  sleep 2
done

if [[ ! -f vendor/autoload.php ]]; then
  echo "[casino] instalando dependências PHP (composer)..."
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress
fi

mkdir -p storage/app/public storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public || true
chmod -R ug+rwX storage bootstrap/cache || true

if [[ ! -f storage/installed.lock ]]; then
  echo "[casino] instalando a aplicação..."
  # roda como root (a pasta montada pode pertencer ao seu usuário) e depois
  # entrega ao Apache os arquivos que ele precisa gravar
  php artisan system:install --no-interaction --overwrite \
    --url="${APP_URL}" --name="${SITE_NAME}" \
    --db-host="${DB_HOST}" --db-port="${DB_PORT}" --db-name="${DB_DATABASE}" \
    --db-user="${DB_USERNAME}" --db-pass="${DB_PASSWORD}" \
    --admin-email="${ADMIN_EMAIL}" --admin-pass="${ADMIN_PASSWORD}"
  chown -R www-data:www-data storage bootstrap/cache public/storage .env || true
  chmod 664 .env || true
  echo "[casino] pronto! Site: ${APP_URL}  |  Painel: ${APP_URL}/admin  |  Login: ${ADMIN_EMAIL} / ${ADMIN_PASSWORD}"
else
  php artisan migrate --force || true
  chown -R www-data:www-data storage bootstrap/cache || true
fi

exec "$@"
