#!/usr/bin/env bash
# =============================================================================
#  Casino Platform — instalação LOCAL automática (localhost)
#
#  Para testar/desenvolver no seu computador: Linux, macOS ou Windows (WSL).
#  Não precisa de root, Nginx nem domínio: usa o PHP e o MySQL/MariaDB que
#  você já tem e sobe o site com o servidor embutido do PHP.
#
#     bash scripts/install-local.sh
#     abra http://127.0.0.1:8000        (painel: http://127.0.0.1:8000/admin)
#
#  Opções (todas opcionais):
#     --port 8000              Porta do site
#     --db-host 127.0.0.1      Servidor MySQL/MariaDB
#     --db-port 3306
#     --db-name casino_local   O banco é criado se não existir
#     --db-user root
#     --db-pass ''             Senha do MySQL (XAMPP/Laragon: vazia)
#     --admin-email admin@casino.local
#     --admin-pass admin12345
#     --name "Meu Cassino"
#     --no-serve               Só instala, não inicia o servidor
#     --serve                  Só inicia o servidor (instalação já feita)
#
#  Prefere Docker? Na raiz do repositório:  docker compose up -d
# =============================================================================
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_DIR="${ROOT_DIR}/extraia para public_html/public_html"

PORT=8000
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=casino_local
DB_USER=root
DB_PASS=""
ADMIN_EMAIL=admin@casino.local
ADMIN_PASS=admin12345
SITE_NAME="Meu Cassino"
DO_INSTALL=1
DO_SERVE=1

c_green='\033[1;32m'; c_red='\033[1;31m'; c_cyan='\033[1;36m'; c_off='\033[0m'
log()  { echo -e "${c_cyan}==>${c_off} $*"; }
ok()   { echo -e "${c_green}✔${c_off} $*"; }
die()  { echo -e "${c_red}✖ $*${c_off}" >&2; exit 1; }

while [[ $# -gt 0 ]]; do
  case "$1" in
    --port) PORT="$2"; shift 2 ;;
    --db-host) DB_HOST="$2"; shift 2 ;;
    --db-port) DB_PORT="$2"; shift 2 ;;
    --db-name) DB_NAME="$2"; shift 2 ;;
    --db-user) DB_USER="$2"; shift 2 ;;
    --db-pass) DB_PASS="$2"; shift 2 ;;
    --admin-email) ADMIN_EMAIL="$2"; shift 2 ;;
    --admin-pass) ADMIN_PASS="$2"; shift 2 ;;
    --name) SITE_NAME="$2"; shift 2 ;;
    --no-serve) DO_SERVE=0; shift ;;
    --serve) DO_INSTALL=0; shift ;;
    -h|--help) sed -n '2,27p' "$0" | sed 's/^# \{0,1\}//'; exit 0 ;;
    *) die "Opção desconhecida: $1 (use --help)" ;;
  esac
done

[[ -f "${APP_DIR}/artisan" ]] || die "Aplicação não encontrada em: ${APP_DIR}"
cd "${APP_DIR}"
URL="http://127.0.0.1:${PORT}"

# ---------------------------------------------------------------- requisitos
command -v php >/dev/null || die "PHP não encontrado. Instale o PHP 8.1+ (Ubuntu: sudo apt install php-cli php-mysql php-mbstring php-xml php-curl php-zip php-intl php-gd php-bcmath; macOS: brew install php; Windows: use o XAMPP/Laragon ou o WSL)."
php -r 'exit(PHP_VERSION_ID >= 80100 ? 0 : 1);' || die "É necessário PHP 8.1 ou superior (encontrado: $(php -r 'echo PHP_VERSION;'))."

missing=()
for ext in pdo_mysql mbstring xml curl zip intl gd bcmath; do
  php -m | grep -qi "^${ext}$" || missing+=("${ext}")
done
[[ ${#missing[@]} -eq 0 ]] || die "Extensões PHP faltando: ${missing[*]}"

if [[ ${DO_INSTALL} -eq 1 ]]; then
  # ------------------------------------------------------------ dependências
  if [[ ! -f vendor/autoload.php ]]; then
    log "Instalando dependências PHP (composer)"
    if command -v composer >/dev/null; then
      COMPOSER=(composer)
    else
      log "Composer não encontrado; baixando composer.phar"
      php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
      php composer-setup.php --quiet && rm -f composer-setup.php
      COMPOSER=(php composer.phar)
    fi
    "${COMPOSER[@]}" install --no-interaction --no-progress --optimize-autoloader
    rm -f composer.phar
  fi
  ok "Dependências PHP prontas"

  [[ -f public/build/manifest.json ]] || {
    command -v npm >/dev/null || die "Frontend não compilado e Node.js ausente. Instale o Node 18+ e rode: npm ci && npm run build"
    log "Compilando o frontend"; npm ci --no-audit --no-fund && npm run build
  }

  # ------------------------------------------------------------ instalação
  log "Instalando a aplicação (banco ${DB_NAME} em ${DB_HOST}:${DB_PORT})"
  php artisan system:install --no-interaction --overwrite \
    --url="${URL}" --name="${SITE_NAME}" \
    --db-host="${DB_HOST}" --db-port="${DB_PORT}" --db-name="${DB_NAME}" \
    --db-user="${DB_USER}" --db-pass="${DB_PASS}" \
    --admin-email="${ADMIN_EMAIL}" --admin-pass="${ADMIN_PASS}"

  # ambiente local: mostra erros detalhados
  sed -i.bak -e 's/^APP_ENV=.*/APP_ENV=local/' -e 's/^APP_DEBUG=.*/APP_DEBUG=true/' .env && rm -f .env.bak
  php artisan optimize:clear >/dev/null

  echo
  echo -e "${c_green}════════════════════════════════════════════════════${c_off}"
  echo -e "${c_green}  Instalação local concluída!${c_off}"
  echo "  Site:   ${URL}"
  echo "  Painel: ${URL}/admin"
  echo "  Login:  ${ADMIN_EMAIL}"
  echo "  Senha:  ${ADMIN_PASS}"
  echo -e "${c_green}════════════════════════════════════════════════════${c_off}"
fi

if [[ ${DO_SERVE} -eq 1 ]]; then
  echo
  log "Iniciando o servidor em ${URL} (Ctrl+C para parar; depois use: bash scripts/install-local.sh --serve)"
  exec php artisan serve --host=127.0.0.1 --port="${PORT}"
fi
