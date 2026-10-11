#!/usr/bin/env bash
# Prepara e instala a aplicação automaticamente na primeira execução (Docker).
set -euo pipefail
cd /var/www/html

G=$'\033[38;5;41m'; B=$'\033[38;5;39m'; Y=$'\033[38;5;220m'; D=$'\033[38;5;244m'; N=$'\033[0m'; BD=$'\033[1m'
ok()   { echo -e "  ${G}✔${N} $*"; }
step() { echo -e "\n${B}▸${N} ${BD}$*${N}"; }
echo -e "${G}  Casino Platform${N} ${D}— Docker${N}"

step "Aguardando o banco de dados (${DB_HOST}:${DB_PORT})"
for i in $(seq 1 60); do
  if mariadb-admin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent 2>/dev/null; then ok "Banco respondendo"; break; fi
  [[ $i -eq 60 ]] && { echo "O banco não respondeu em 2 minutos."; exit 1; }
  sleep 2
done

if [[ ! -f vendor/autoload.php ]]; then
  step "Instalando as dependências do PHP (só na primeira vez, alguns minutos)"
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress
  ok "Dependências instaladas"
fi

mkdir -p storage/app/public storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache public || true
chmod -R ug+rwX storage bootstrap/cache || true

if [[ ! -f storage/installed.lock ]]; then
  step "Instalando o site"
  # roda como root (a pasta montada pode pertencer ao seu usuário) e depois
  # entrega ao Apache os arquivos que ele precisa gravar
  php artisan system:install --no-interaction --overwrite \
    --url="${APP_URL}" --name="${SITE_NAME}" \
    --db-host="${DB_HOST}" --db-port="${DB_PORT}" --db-name="${DB_DATABASE}" \
    --db-user="${DB_USERNAME}" --db-pass="${DB_PASSWORD}" \
    --admin-email="${ADMIN_EMAIL}" --admin-pass="${ADMIN_PASSWORD}" >/tmp/install.log 2>&1 || { tail -20 /tmp/install.log; exit 1; }
  chown -R www-data:www-data storage bootstrap/cache public/storage .env || true
  chmod 664 .env || true
  ok "Site instalado"
  echo
  echo -e "  ${BD}Pronto!${N}"
  echo -e "    Site     ${G}${APP_URL}${N}"
  echo -e "    Painel   ${G}${APP_URL}/admin${N}"
  echo -e "    Login    ${ADMIN_EMAIL}   Senha: ${ADMIN_PASSWORD}"
  echo -e "  ${D}Acompanhe com: docker compose logs -f app${N}\n"
else
  step "Atualizando o banco de dados"
  php artisan migrate --force && ok "Banco atualizado" || true
  chown -R www-data:www-data storage bootstrap/cache || true
fi

exec "$@"
