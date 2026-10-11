#!/usr/bin/env bash
# =============================================================================
#  Casino Platform — instalador automático para VPS (Ubuntu 22.04/24.04, Debian 12)
#
#  Instalação completa (Nginx + PHP 8.3 + MariaDB + SSL + cron):
#     curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/scripts/auto-install.sh -o auto-install.sh
#     sudo bash auto-install.sh --domain meusite.com --email voce@meusite.com --ssl
#
#  Atualizar uma instalação existente:
#     sudo bash auto-install.sh --update
#
#  Diagnóstico:
#     sudo bash auto-install.sh --diagnose
# =============================================================================
set -Eeuo pipefail

VERSION="9.0.0"
REPO_URL="${REPO_URL:-https://github.com/Edton180/casino.git}"
BRANCH="${BRANCH:-main}"
APP_DIR="${APP_DIR:-/var/www/casino}"
SRC_DIR="/opt/casino-src"
APP_SUBDIR="extraia para public_html/public_html"
PHP_VERSION="${PHP_VERSION:-8.3}"
CRED_FILE="/root/casino-credenciais.txt"

DOMAIN=""
EMAIL=""
SITE_NAME="Casino"
ADMIN_PASS=""
ENABLE_SSL=0
MODE="install"
ASSUME_YES=0

c_green='\033[1;32m'; c_red='\033[1;31m'; c_yellow='\033[1;33m'; c_cyan='\033[1;36m'; c_off='\033[0m'
log()  { echo -e "${c_cyan}==>${c_off} $*"; }
ok()   { echo -e "${c_green}✔${c_off} $*"; }
warn() { echo -e "${c_yellow}!${c_off} $*"; }
die()  { echo -e "${c_red}✖ $*${c_off}" >&2; exit 1; }
trap 'die "Falha na linha ${LINENO}: ${BASH_COMMAND}"' ERR

usage() {
  cat <<EOF
Casino Platform — instalador para VPS v${VERSION}

Uso: sudo bash auto-install.sh [opções]

  --domain <dominio>      Domínio do site (obrigatório na instalação)
  --email <email>         E-mail do administrador (login do painel) e do SSL
  --name <nome>           Nome do site (padrão: Casino)
  --admin-pass <senha>    Senha do administrador (padrão: gerada automaticamente)
  --ssl                   Emite certificado SSL gratuito (Let's Encrypt)
  --dir <pasta>           Pasta da aplicação (padrão: ${APP_DIR})
  --repo <url>            Repositório (padrão: ${REPO_URL})
  --branch <branch>       Branch (padrão: ${BRANCH})
  --php <versão>          Versão do PHP (padrão: ${PHP_VERSION})
  --update                Atualiza uma instalação existente
  --diagnose              Mostra o diagnóstico do servidor
  -y                      Não pede confirmação
  -h, --help              Ajuda
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --domain) DOMAIN="$2"; shift 2 ;;
    --email) EMAIL="$2"; shift 2 ;;
    --name) SITE_NAME="$2"; shift 2 ;;
    --admin-pass) ADMIN_PASS="$2"; shift 2 ;;
    --ssl) ENABLE_SSL=1; shift ;;
    --dir) APP_DIR="$2"; shift 2 ;;
    --repo) REPO_URL="$2"; shift 2 ;;
    --branch) BRANCH="$2"; shift 2 ;;
    --php) PHP_VERSION="$2"; shift 2 ;;
    --update) MODE="update"; shift ;;
    --diagnose) MODE="diagnose"; shift ;;
    -y|--yes|--non-interactive) ASSUME_YES=1; shift ;;
    -h|--help) usage; exit 0 ;;
    # compatibilidade com a versão anterior
    --mode) case "$2" in diagnose) MODE="diagnose" ;; *) ;; esac; shift 2 ;;
    *) die "Opção desconhecida: $1 (use --help)" ;;
  esac
done

random_secret() { tr -dc 'A-Za-z0-9' </dev/urandom | head -c "${1:-24}"; }

svc() {
  # systemctl quando disponível; "service" em containers/VMs sem systemd
  local action="$1" name="$2"
  if command -v systemctl >/dev/null && [[ -d /run/systemd/system ]]; then
    systemctl "${action}" "${name}"
  else
    service "${name}" "${action}" || true
  fi
}

as_www() {
  if command -v sudo >/dev/null; then sudo -u www-data "$@"; else runuser -u www-data -- "$@"; fi
}

require_root() { [[ ${EUID} -eq 0 ]] || die "Execute como root: sudo bash $0 ..."; }

detect_os() {
  [[ -f /etc/os-release ]] || die "Sistema não suportado (use Ubuntu 22.04/24.04 ou Debian 12)."
  # shellcheck disable=SC1091
  . /etc/os-release
  OS_ID="${ID}"; OS_VERSION="${VERSION_ID:-}"
  case "${OS_ID}" in ubuntu|debian) ;; *) die "Sistema ${PRETTY_NAME:-desconhecido} não suportado. Use Ubuntu ou Debian." ;; esac
}

banner() {
  echo -e "${c_green}"
  cat <<'EOF'
   ____          _               ____  _       _    __
  / ___|__ _ ___(_)_ __   ___   |  _ \| | __ _| |_ / _| ___  _ __ _ __ ___
 | |   / _` / __| | '_ \ / _ \  | |_) | |/ _` | __| |_ / _ \| '__| '_ ` _ \
 | |__| (_| \__ \ | | | | (_) | |  __/| | (_| | |_|  _| (_) | |  | | | | | |
  \____\__,_|___/_|_| |_|\___/  |_|   |_|\__,_|\__|_|  \___/|_|  |_| |_| |_|
EOF
  echo -e "${c_off}  Instalador para VPS v${VERSION}\n"
}

legal_notice() {
  cat <<'EOF'
AVISO: operar apostas/jogos de azar exige licença na maioria dos países (no Brasil,
autorização da SPA/Ministério da Fazenda — Lei 14.790/2023). Você é responsável por
licenças, KYC/PLD, jogo responsável, LGPD e pelo direito de uso dos jogos/provedores.
EOF
  if [[ ${ASSUME_YES} -eq 0 ]]; then
    read -rp "Digite 'ACEITO' para continuar: " answer
    [[ "${answer}" == "ACEITO" ]] || die "Instalação cancelada."
  fi
}

install_packages() {
  log "Instalando pacotes do sistema"
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq
  apt-get install -y -qq ca-certificates curl gnupg lsb-release unzip git rsync cron software-properties-common >/dev/null

  if ! apt-cache show "php${PHP_VERSION}-fpm" >/dev/null 2>&1; then
    log "Adicionando repositório do PHP ${PHP_VERSION}"
    if [[ "${OS_ID}" == "ubuntu" ]]; then
      add-apt-repository -y ppa:ondrej/php >/dev/null
    else
      curl -fsSL https://packages.sury.org/php/apt.gpg | gpg --dearmor -o /usr/share/keyrings/sury-php.gpg
      echo "deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/sury-php.list
    fi
    apt-get update -qq
  fi

  apt-get install -y -qq nginx mariadb-server \
    "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-xml" "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-gd" "php${PHP_VERSION}-bcmath" >/dev/null

  if [[ ${ENABLE_SSL} -eq 1 ]]; then
    apt-get install -y -qq certbot python3-certbot-nginx >/dev/null
  fi

  if ! command -v composer >/dev/null; then
    log "Instalando Composer"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
  fi

  svc start mariadb || svc start mysql
  svc start "php${PHP_VERSION}-fpm"
  ok "Pacotes instalados (PHP $(php -r 'echo PHP_VERSION;'))"
}

fetch_code() {
  log "Baixando o código (${REPO_URL} @ ${BRANCH})"
  if [[ -d "${SRC_DIR}/.git" ]]; then
    git -C "${SRC_DIR}" fetch -q --depth 1 origin "${BRANCH}"
    git -C "${SRC_DIR}" reset -q --hard "origin/${BRANCH}"
  else
    rm -rf "${SRC_DIR}"
    git clone -q --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${SRC_DIR}"
  fi
  [[ -f "${SRC_DIR}/${APP_SUBDIR}/artisan" ]] || die "Aplicação não encontrada em ${SRC_DIR}/${APP_SUBDIR}"

  mkdir -p "${APP_DIR}"
  # preserva .env, uploads e trava de instalação em atualizações
  rsync -a --delete \
    --exclude '.env' --exclude 'storage/app/' --exclude 'storage/logs/' --exclude 'storage/installed.lock' \
    --exclude 'public/storage/' --exclude 'vendor/' --exclude 'node_modules/' \
    "${SRC_DIR}/${APP_SUBDIR}/" "${APP_DIR}/"
  mkdir -p "${APP_DIR}/storage/"{app/public,logs,framework/{cache/data,sessions,views}} "${APP_DIR}/bootstrap/cache"
  ok "Código copiado para ${APP_DIR}"
}

install_dependencies() {
  log "Instalando dependências PHP (composer)"
  cd "${APP_DIR}"
  COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress --quiet
  if [[ ! -f public/build/manifest.json ]]; then
    warn "Frontend não compilado no repositório; compilando com Node.js"
    command -v npm >/dev/null || { curl -fsSL https://deb.nodesource.com/setup_20.x | bash - >/dev/null; apt-get install -y -qq nodejs >/dev/null; }
    npm ci --no-audit --no-fund --silent && npm run build --silent
  fi
  ok "Dependências instaladas"
}

fix_permissions() {
  chown -R www-data:www-data "${APP_DIR}"
  find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod 775 {} +
  [[ -f "${APP_DIR}/.env" ]] && chmod 640 "${APP_DIR}/.env"
}

create_database() {
  DB_NAME="casino"; DB_USER="casino"; DB_PASS="$(random_secret 28)"
  log "Criando banco de dados ${DB_NAME}"
  mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL
  ok "Banco criado"
}

run_app_installer() {
  local scheme="http"; [[ ${ENABLE_SSL} -eq 1 ]] && scheme="https"
  [[ -n "${ADMIN_PASS}" ]] || ADMIN_PASS="$(random_secret 16)"
  log "Instalando a aplicação"
  cd "${APP_DIR}"
  as_www php artisan system:install --no-interaction --overwrite \
    --url="${scheme}://${DOMAIN}" --name="${SITE_NAME}" \
    --db-host=localhost --db-name="${DB_NAME}" --db-user="${DB_USER}" --db-pass="${DB_PASS}" \
    --admin-email="${EMAIL}" --admin-pass="${ADMIN_PASS}"
  ok "Aplicação instalada"
}

configure_nginx() {
  log "Configurando Nginx"
  local sock="/run/php/php${PHP_VERSION}-fpm.sock"
  cat > /etc/nginx/sites-available/casino <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};
    root ${APP_DIR}/public;
    index index.php index.html;
    client_max_body_size 64M;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:${sock};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_read_timeout 300;
    }

    location ~* \.(?:css|js|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|mp3|ogg|json|atlas)\$ {
        expires 30d;
        access_log off;
        try_files \$uri =404;
    }

    location ~ /\.(?!well-known) { deny all; }
}
NGINX
  ln -sf /etc/nginx/sites-available/casino /etc/nginx/sites-enabled/casino
  rm -f /etc/nginx/sites-enabled/default
  nginx -t -q
  svc reload nginx || svc restart nginx
  ok "Nginx configurado (${DOMAIN} → ${APP_DIR}/public)"
}

configure_ssl() {
  [[ ${ENABLE_SSL} -eq 1 ]] || return 0
  log "Emitindo certificado SSL (Let's Encrypt)"
  if certbot --nginx -d "${DOMAIN}" -m "${EMAIL}" --agree-tos --non-interactive --redirect; then
    ok "SSL ativo"
  else
    warn "Não foi possível emitir o SSL agora. Confira se o DNS de ${DOMAIN} aponta para este servidor e rode:"
    warn "  certbot --nginx -d ${DOMAIN} -m ${EMAIL} --agree-tos --redirect"
  fi
}

configure_cron() {
  local line="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
  (crontab -u www-data -l 2>/dev/null | grep -Fv "artisan schedule:run" || true; echo "${line}") | crontab -u www-data -
  svc start cron || true
  ok "Cron do agendador configurado"
}

save_credentials() {
  local scheme="http"; [[ ${ENABLE_SSL} -eq 1 ]] && scheme="https"
  umask 077
  cat > "${CRED_FILE}" <<EOF
Casino Platform — dados da instalação ($(date))

Site:          ${scheme}://${DOMAIN}
Painel admin:  ${scheme}://${DOMAIN}/admin
Login:         ${EMAIL}
Senha:         ${ADMIN_PASS}

Banco MySQL:   ${DB_NAME}
Usuário:       ${DB_USER}
Senha:         ${DB_PASS}

Pasta:         ${APP_DIR}
Logs:          ${APP_DIR}/storage/logs
EOF
  ok "Credenciais salvas em ${CRED_FILE} (somente root pode ler)"
}

do_install() {
  require_root; detect_os; banner; legal_notice
  [[ -n "${DOMAIN}" ]] || read -rp "Domínio (ex: meusite.com): " DOMAIN
  [[ -n "${EMAIL}" ]] || read -rp "E-mail do administrador: " EMAIL
  [[ -n "${DOMAIN}" && "${EMAIL}" == *@* ]] || die "Informe --domain e --email válidos."
  DOMAIN="${DOMAIN#http://}"; DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN%%/*}"

  install_packages
  fetch_code
  install_dependencies
  create_database
  fix_permissions
  run_app_installer
  fix_permissions
  configure_nginx
  configure_ssl
  configure_cron
  save_credentials

  echo
  echo -e "${c_green}════════════════════════════════════════════════════${c_off}"
  echo -e "${c_green}  Instalação concluída!${c_off}"
  cat "${CRED_FILE}" | sed -n '3,6p'
  echo -e "${c_green}════════════════════════════════════════════════════${c_off}"
}

do_update() {
  require_root; detect_os
  [[ -f "${APP_DIR}/.env" ]] || die "Instalação não encontrada em ${APP_DIR}"
  log "Atualizando ${APP_DIR}"
  cd "${APP_DIR}" && as_www php artisan down --retry=30 || true
  fetch_code
  install_dependencies
  fix_permissions
  cd "${APP_DIR}"
  as_www php artisan migrate --force
  as_www php artisan optimize:clear
  as_www php artisan up
  svc reload "php${PHP_VERSION}-fpm" || true
  ok "Atualização concluída"
}

do_diagnose() {
  echo "== Diagnóstico =="
  [[ -f /etc/os-release ]] && . /etc/os-release && echo "Sistema:   ${PRETTY_NAME}"
  echo "PHP:       $(php -r 'echo PHP_VERSION;' 2>/dev/null || echo 'não instalado')"
  for ext in pdo_mysql mbstring xml curl zip intl gd bcmath; do
    php -m 2>/dev/null | grep -qi "^${ext}$" && echo "  ✔ ${ext}" || echo "  ✖ ${ext}"
  done
  echo "Composer:  $(composer --version 2>/dev/null | head -1 || echo 'não instalado')"
  echo "MariaDB:   $(mysql --version 2>/dev/null || echo 'não instalado')"
  echo "Nginx:     $(nginx -v 2>&1 || echo 'não instalado')"
  if [[ -f "${APP_DIR}/artisan" ]]; then
    echo "Aplicação: ${APP_DIR}"
    [[ -f "${APP_DIR}/storage/installed.lock" ]] && echo "  ✔ instalada" || echo "  ✖ não instalada"
    (cd "${APP_DIR}" && php artisan system:install --check 2>/dev/null | tail -n +1) || true
  else
    echo "Aplicação: não encontrada em ${APP_DIR}"
  fi
}

case "${MODE}" in
  install)  do_install ;;
  update)   do_update ;;
  diagnose) do_diagnose ;;
esac
