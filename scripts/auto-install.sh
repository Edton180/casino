#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_VERSION="v8.0.0"
APP_DIR="/opt/casino"
REPO_URL="https://github.com/Edton180/casino.git"
MODE=""
DOMAIN=""
EMAIL=""
ENABLE_SSL=0
NON_INTERACTIVE=0

print_banner() {
echo -e "\e[1;35m"
cat <<'BANNER'
╔════════════════════════════════════════════════════════════╗
║                                                            ║
║   ██████╗  █████╗ ███████╗██╗███╗   ██╗ ██████╗          ║
║  ██╔════╝ ██╔══██╗██╔════╝██║████╗  ██║██╔═══██╗         ║
║  ██║      ███████║███████╗██║██╔██╗ ██║██║   ██║         ║
║  ██║      ██╔══██║╚════██║██║██║╚██╗██║██║   ██║         ║
║  ╚██████╗ ██║  ██║███████║██║██║ ╚████║╚██████╔╝         ║
║   ╚═════╝ ╚═╝  ╚═╝╚══════╝╚═╝╚═╝  ╚═══╝ ╚═════╝          ║
║                                                            ║
║        🎰 CASINO PLATFORM - INSTALLER v8.0.0              ║
║        Sistema Profissional de Apostas Online             ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
BANNER
echo -e "\e[0m"
}

print_legal_notice() {
echo -e "\e[1;33m"
cat <<'LEGAL'

══════════════════════════════════════════════════════════════
                    AVISO LEGAL IMPORTANTE
══════════════════════════════════════════════════════════════

Este software é protegido por direitos autorais.

© 2026 Edton180 - Todos os direitos reservados.

• Proibida revenda não autorizada
• Proibida redistribuição sem licença
• Modificação apenas para uso próprio
• Uso comercial sob responsabilidade do operador

O uso deste sistema deve respeitar as leis locais
sobre apostas e jogos online do seu país.

══════════════════════════════════════════════════════════════

LEGAL
echo -e "\e[0m"
}

require_legal_acceptance() {
  local confirm
  read -rp "Digite 'EU ACEITO' para continuar: " confirm

  if [[ "${confirm}" != "EU ACEITO" ]]; then
    echo "Instalação cancelada."
    exit 1
  fi
}

print_menu() {
echo -e "\e[1;36m"
cat <<'MENU'
╔════════════════════════════════════════════════════════════╗
║                    MENU DE INSTALAÇÃO                     ║
╠════════════════════════════════════════════════════════════╣
║                                                            ║
║  1) 🚀 VPS Produção Completa                              ║
║     PHP 8.2 + MySQL + Nginx + SSL                         ║
║                                                            ║
║  2) 🐳 Instalação Docker                                  ║
║     Ambiente isolado em containers                        ║
║                                                            ║
║  3) 💻 Localhost / Desenvolvimento                        ║
║     Ambiente para desenvolvimento Laravel                 ║
║                                                            ║
║  4) 🌐 Instruções cPanel                                  ║
║     Hosting compartilhado                                 ║
║                                                            ║
║  5) 🧪 VirtualBox / VM                                    ║
║     Ambiente de testes                                    ║
║                                                            ║
║  6) 🔍 Diagnóstico do Sistema                             ║
║     Verificar requisitos                                  ║
║                                                            ║
║  0) ❌ Sair                                               ║
║                                                            ║
╚════════════════════════════════════════════════════════════╝
MENU
echo -e "\e[0m"
}

print_install_identification() {
  local install_date server_ip
  install_date="$(date)"
  server_ip="$(hostname -I | awk '{print $1}')"
  echo "Instalação iniciada em: ${install_date}"
  echo "IP do servidor: ${server_ip:-não detectado}"
}

print_success_footer() {
echo -e "\e[1;32m"
cat <<'FOOTER'

══════════════════════════════════════════════════════════════

🎰 CASINO PLATFORM INSTALADO COM SUCESSO

Desenvolvido por: Edton180
Versão: 8.0.0
Ano: 2026

Para suporte e atualizações oficiais,
utilize apenas o repositório autorizado.

══════════════════════════════════════════════════════════════

FOOTER
echo -e "\e[0m"
}

usage() {
cat <<EOF2
Casino Platform Auto Installer ${SCRIPT_VERSION}

Uso:
  bash scripts/auto-install.sh [opções]

Opções:
  --mode vps|docker|localhost|cpanel|virtualbox|diagnose
  --domain <dominio>
  --email <email>
  --ssl
  --non-interactive
  --help

Exemplo:
  sudo bash auto-install.sh --mode vps --domain casino.com --email admin@casino.com --ssl
EOF2
}

require_root() {
  if [[ ${EUID} -ne 0 ]]; then
    echo "Execute como root (sudo)."
    exit 1
  fi
}

run_cmd() {
  echo "→ $*"
  "$@"
}

install_base_stack() {
  run_cmd apt update
  run_cmd apt upgrade -y
  run_cmd apt install -y software-properties-common curl unzip git nginx mysql-server certbot python3-certbot-nginx supervisor
  run_cmd add-apt-repository -y ppa:ondrej/php
  run_cmd apt update
  run_cmd apt install -y php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  run_cmd apt install -y nodejs
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  run_cmd php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  run_cmd rm -f composer-setup.php
}

install_app() {
  if [[ -d "${APP_DIR}" ]]; then
    echo "Diretório ${APP_DIR} já existe, atualizando..."
    run_cmd git -C "${APP_DIR}" pull --ff-only
  else
    run_cmd git clone "${REPO_URL}" "${APP_DIR}"
  fi

  cd "${APP_DIR}"
  [[ -f .env ]] || run_cmd cp .env.example .env

  run_cmd composer install --no-dev --optimize-autoloader
  run_cmd npm install
  run_cmd npm run build
  run_cmd php artisan key:generate --force
  run_cmd php artisan migrate --force
  run_cmd php artisan storage:link
  run_cmd php artisan optimize

  run_cmd mkdir -p storage
  run_cmd touch storage/installed.lock
  run_cmd chown -R www-data:www-data storage bootstrap/cache || true
  run_cmd chmod -R 775 storage bootstrap/cache || true
}

configure_nginx() {
  [[ -n "${DOMAIN}" ]] || return 0
  cat > /etc/nginx/sites-available/casino <<NGINX
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR}/public;

    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\.ht {
        deny all;
    }
}
NGINX
  run_cmd ln -sf /etc/nginx/sites-available/casino /etc/nginx/sites-enabled/casino
  run_cmd rm -f /etc/nginx/sites-enabled/default
  run_cmd nginx -t
  run_cmd systemctl restart nginx

  if [[ ${ENABLE_SSL} -eq 1 && -n "${EMAIL}" ]]; then
    run_cmd certbot --nginx -d "${DOMAIN}" -m "${EMAIL}" --agree-tos --non-interactive --redirect
  fi
}

configure_cron() {
  local cron_line="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
  (crontab -l 2>/dev/null | grep -Fv "${APP_DIR} && php artisan schedule:run"; echo "${cron_line}") | crontab -
}

create_backup_script() {
  cat > /usr/local/bin/casino-backup <<'BACKUP'
#!/usr/bin/env bash
set -euo pipefail
STAMP=$(date +%F_%H%M%S)
DEST=/opt/casino-backups
mkdir -p "$DEST"
tar -czf "$DEST/casino_${STAMP}.tar.gz" /opt/casino
find "$DEST" -type f -name 'casino_*.tar.gz' -mtime +7 -delete
BACKUP
  chmod +x /usr/local/bin/casino-backup
}

run_diagnose() {
  echo "== Diagnóstico do Sistema =="
  command -v php >/dev/null && php -v | head -n 1 || echo "PHP não encontrado"
  command -v composer >/dev/null && composer --version || echo "Composer não encontrado"
  command -v node >/dev/null && node -v || echo "Node não encontrado"
  command -v npm >/dev/null && npm -v || echo "NPM não encontrado"
  command -v mysql >/dev/null && mysql --version || echo "MySQL não encontrado"
  command -v nginx >/dev/null && nginx -v 2>&1 || echo "Nginx não encontrado"
}

handle_mode() {
  print_install_identification

  case "$MODE" in
    vps)
      require_root
      install_base_stack
      install_app
      configure_nginx
      configure_cron
      create_backup_script
      print_success_footer
      ;;
    docker)
      echo "Modo docker: configure via docker-compose do projeto."
      ;;
    cpanel)
      echo "Modo cPanel: siga o arquivo MANUAL_INSTALL_CPANEL.md"
      ;;
    localhost)
      echo "Modo localhost: use os comandos em INSTALLATION.md"
      ;;
    virtualbox)
      echo "Modo virtualbox: semelhante ao modo VPS em VM Ubuntu."
      ;;
    diagnose)
      run_diagnose
      ;;
    *)
      echo "Modo inválido: ${MODE}"
      usage
      exit 1
      ;;
  esac
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --mode) MODE="$2"; shift 2 ;;
    --domain) DOMAIN="$2"; shift 2 ;;
    --email) EMAIL="$2"; shift 2 ;;
    --ssl) ENABLE_SSL=1; shift ;;
    --non-interactive) NON_INTERACTIVE=1; shift ;;
    --help|-h) usage; exit 0 ;;
    *) echo "Opção desconhecida: $1"; usage; exit 1 ;;
  esac
done

if [[ -z "${MODE}" && ${NON_INTERACTIVE} -eq 0 ]]; then
  clear
  print_banner
  print_legal_notice
  require_legal_acceptance
  print_menu
  read -rp "Selecione uma opção: " option
  case "${option}" in
    1) MODE="vps" ;;
    2) MODE="docker" ;;
    3) MODE="localhost" ;;
    4) MODE="cpanel" ;;
    5) MODE="virtualbox" ;;
    6) MODE="diagnose" ;;
    0) echo "Saindo."; exit 0 ;;
    *) echo "Opção inválida."; exit 1 ;;
  esac
fi

if [[ -z "${MODE}" ]]; then
  echo "--mode é obrigatório com --non-interactive"
  exit 1
fi

handle_mode
