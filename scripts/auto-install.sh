#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_NAME=$(basename "$0")
ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
APP_DIR="${ROOT_DIR}/extraia para public_html/public_html"
MODE=""
DOMAIN=""
EMAIL=""
SSL="nao"
DB_CONNECTION="mysql"
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME=""
DB_USER=""
DB_PASS=""
ADMIN_NAME="Administrator"
ADMIN_PASS=""
NON_INTERACTIVE=0

usage() {
cat <<EOF
Auto instalador Casino Platform

Uso:
  ${SCRIPT_NAME} --mode <vps|cpanel|localhost|docker|diagnose|help> [opções]

Opções:
  --domain <dominio>
  --email <admin@email>
  --ssl                     Habilita SSL
  --db-connection <mysql|pgsql>
  --db-host <host>
  --db-port <porta>
  --db-name <nome>
  --db-user <usuario>
  --db-pass <senha>
  --admin-name <nome>
  --admin-pass <senha>
  --non-interactive         Sem perguntas interativas
  -h, --help

Exemplo:
  bash scripts/auto-install.sh --mode vps --domain casino.site --ssl --email admin@casino.site --non-interactive
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --mode) MODE="$2"; shift 2 ;;
    --domain) DOMAIN="$2"; shift 2 ;;
    --email) EMAIL="$2"; shift 2 ;;
    --ssl) SSL="sim"; shift ;;
    --db-connection) DB_CONNECTION="$2"; shift 2 ;;
    --db-host) DB_HOST="$2"; shift 2 ;;
    --db-port) DB_PORT="$2"; shift 2 ;;
    --db-name) DB_NAME="$2"; shift 2 ;;
    --db-user) DB_USER="$2"; shift 2 ;;
    --db-pass) DB_PASS="$2"; shift 2 ;;
    --admin-name) ADMIN_NAME="$2"; shift 2 ;;
    --admin-pass) ADMIN_PASS="$2"; shift 2 ;;
    --non-interactive) NON_INTERACTIVE=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *) echo "Opção desconhecida: $1"; usage; exit 1 ;;
  esac
done

cd "$APP_DIR"

if [[ "${MODE}" == "" ]]; then
  if [[ ${NON_INTERACTIVE} -eq 1 ]]; then
    echo "--mode é obrigatório em modo não interativo"; exit 1
  fi
  read -rp "Modo (vps/cpanel/localhost/docker/diagnose/help): " MODE
fi

if [[ "$MODE" == "help" ]]; then
  php artisan system:install --mode=help
  exit 0
fi

if [[ "$MODE" == "diagnose" ]]; then
  php artisan system:install --mode=diagnose
  exit $?
fi

if [[ ${NON_INTERACTIVE} -eq 1 ]]; then
  : "${DOMAIN:?Informe --domain}"
  : "${EMAIL:?Informe --email}"
  : "${DB_NAME:?Informe --db-name}"
  : "${DB_USER:?Informe --db-user}"
  : "${ADMIN_PASS:?Informe --admin-pass}"
fi

php artisan system:install \
  --mode="$MODE" \
  --domain="$DOMAIN" \
  --email="$EMAIL" \
  --ssl="$SSL" \
  --db-connection="$DB_CONNECTION" \
  --db-host="$DB_HOST" \
  --db-port="$DB_PORT" \
  --db-name="$DB_NAME" \
  --db-user="$DB_USER" \
  --db-pass="$DB_PASS" \
  --admin-name="$ADMIN_NAME" \
  --admin-pass="$ADMIN_PASS"
