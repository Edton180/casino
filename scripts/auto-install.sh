#!/usr/bin/env bash
# =============================================================================
#  Casino Platform — assistente de instalação (seu computador ou servidor VPS)
#
#  O jeito mais simples (com o repositório já baixado):
#     bash instalar.sh                  → abre o assistente e pergunta o que for preciso
#
#  Direto, sem menu:
#     bash scripts/auto-install.sh --local                       # no seu computador
#     sudo bash scripts/auto-install.sh --domain meusite.com --email voce@meusite.com --ssl   # VPS
#     bash scripts/auto-install.sh --update                      # atualizar
#     bash scripts/auto-install.sh --help                        # todas as opções
#
#  Identifica o sistema (Ubuntu/Debian e derivados, WSL, macOS), mostra o que falta,
#  instala PHP, banco e dependências, instala o site e sabe atualizá-lo depois.
#  Repositório privado? Use --token (veja --help).
# =============================================================================
set -Eeuo pipefail

SCRIPT_VERSION="9.4.0"   # trocado pelo arquivo VERSION quando o assistente roda de dentro do projeto
REPO_SLUG="${REPO_SLUG:-Edton180/casino}"
REPO_URL="${REPO_URL:-https://github.com/${REPO_SLUG}.git}"
BRANCH="${BRANCH:-main}"
TOKEN="${GITHUB_TOKEN:-${GH_TOKEN:-}}"
ORIG_ARGS=("$@")
T0=$SECONDS
# o Composer pergunta "continuar como root?" em terminais interativos: nunca perguntar
export COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1 COMPOSER_DISABLE_XDEBUG_WARN=1

# ---- onde estou? (dentro de uma cópia do repositório ou baixado via curl) ----
SELF="${BASH_SOURCE[0]:-}"
SCRIPT_DIR=""; CHECKOUT=""
if [[ -n "${SELF}" && -f "${SELF}" ]]; then
  SCRIPT_DIR="$(cd "$(dirname "${SELF}")" && pwd)"
  SELF="${SCRIPT_DIR}/$(basename "${SELF}")"
  if [[ -f "${SCRIPT_DIR}/../artisan" && -f "${SCRIPT_DIR}/../composer.json" ]]; then
    CHECKOUT="$(cd "${SCRIPT_DIR}/.." && pwd)"
  fi
  if [[ -f "${SCRIPT_DIR}/../VERSION" ]]; then SCRIPT_VERSION="$(tr -d ' \r\n' < "${SCRIPT_DIR}/../VERSION")"; fi
fi

# ---- opções ----
MODE=""                 # install | update | diagnose | start | stop | status
TARGET=""               # local | server
APP_DIR=""
SOURCE=""
SRC_DIR="/opt/casino-src"
PHP_VERSION="${PHP_VERSION:-8.3}"
DOMAIN=""; ADMIN_EMAIL=""; ADMIN_PASS=""; SITE_NAME=""
AUTO=0; UN_ALL=0; PURGE=0; PURGE_PACKAGES=0; NO_BACKUP=0; KEEP_DB=0; RESTORE_FILE=""; ENABLE_SSL=0; STACK=0; LAN=0; NO_SERVE=0; REINSTALL=0; ASSUME_YES=0; SYS_UPGRADE=0; NO_COLOR_OPT=0
HTTP_PORT=""
DB_HOST=""; DB_PORT="3306"; DB_NAME=""; DB_USER=""; DB_PASS=""
CRED_FILE=""
INTERACTIVE=0
LOG="${TMPDIR:-/tmp}/casino-install-$(date +%Y%m%d-%H%M%S).log"
STEP=0; STEPS=0
SUDO=""; SUDO_KEEPALIVE_PID=""
# o que esta execução instalou/criou (anotado no registro para a desinstalação não deixar rastros)
INSTALLED_PKGS=""; COMPOSER_DOWNLOADED=""; SWAP_CREATED=""; DB_CREATED=0
BACKUP_DIR="${HOME:-/root}/casino-backups"

# =============================================================================
#  Interface (cores, ícones, caixas, barra de progresso, animação)
# =============================================================================
COLOR=0; UTF=0
[[ -t 1 && -z "${NO_COLOR:-}" && "${TERM:-dumb}" != "dumb" ]] && COLOR=1
case "${LC_ALL:-${LC_CTYPE:-${LANG:-}}}" in *[Uu][Tt][Ff]-8*|*[Uu][Tt][Ff]8*) UTF=1 ;; esac

setup_ui() {
  if [[ ${COLOR} -eq 1 ]]; then
    local n; n="$(tput colors 2>/dev/null || echo 8)"
    if [[ "${n}" -ge 256 ]]; then
      c_accent=$'\033[38;5;42m'; c_blue=$'\033[38;5;39m'; c_green=$'\033[38;5;41m'; c_red=$'\033[38;5;203m'
      c_yellow=$'\033[38;5;220m'; c_dim=$'\033[38;5;244m'; c_line=$'\033[38;5;240m'; c_hi=$'\033[48;5;236m'
    else
      c_accent=$'\033[1;32m'; c_blue=$'\033[1;36m'; c_green=$'\033[1;32m'; c_red=$'\033[1;31m'
      c_yellow=$'\033[1;33m'; c_dim=$'\033[2m'; c_line=$'\033[2m'; c_hi=$'\033[7m'
    fi
    c_bold=$'\033[1m'; c_off=$'\033[0m'
  else
    c_accent=""; c_blue=""; c_green=""; c_red=""; c_yellow=""; c_dim=""; c_line=""; c_hi=""; c_bold=""; c_off=""
  fi
  if [[ ${UTF} -eq 1 ]]; then
    I_OK="✔"; I_FAIL="✖"; I_WARN="!"; I_INFO="●"; I_ARROW="▸"; DOT="·"
    B_TL="╭"; B_TR="╮"; B_BL="╰"; B_BR="╯"; B_H="─"; B_V="│"; P_ON="█"; P_OFF="░"
    SPIN=("⠋" "⠙" "⠹" "⠸" "⠼" "⠴" "⠦" "⠧" "⠇" "⠏")
  else
    I_OK="OK"; I_FAIL="X"; I_WARN="!"; I_INFO="*"; I_ARROW=">"; DOT="-"
    B_TL="+"; B_TR="+"; B_BL="+"; B_BR="+"; B_H="-"; B_V="|"; P_ON="#"; P_OFF="-"
    SPIN=("|" "/" "-" "\\")
  fi
  COLS="$(tput cols 2>/dev/null || echo 80)"
  [[ "${COLS}" -gt 78 ]] && COLS=78
  [[ "${COLS}" -lt 50 ]] && COLS=50
  return 0
}

rep() { local i out=""; for ((i = 0; i < $2; i++)); do out+="$1"; done; printf '%s' "${out}"; }
vlen() {   # largura visível (sem cores, contando caracteres e não bytes)
  local n
  n="$(printf '%s' "$1" | sed $'s/\033\\[[0-9;]*[A-Za-z]//g' | LC_ALL=C.UTF-8 wc -m 2>/dev/null | tr -d ' ')" || n=""
  [[ -n "${n}" ]] || n="${#1}"
  echo "${n}"
}

log()  { echo -e "${c_blue}${I_ARROW}${c_off} $*"; }
ok()   { echo -e "  ${c_green}${I_OK}${c_off} $*"; }
warn() { echo -e "  ${c_yellow}${I_WARN}${c_off} $*"; }
info() { echo -e "  ${c_dim}${I_INFO} $*${c_off}"; }
die()  { echo -e "\n${c_red}${I_FAIL} $*${c_off}" >&2; exit 1; }

step() {
  STEP=$((STEP + 1))
  local done=$(( STEPS > 0 ? STEP * 20 / STEPS : 0 ))
  echo
  echo -e "${c_accent}$(rep "${P_ON}" "${done}")${c_dim}$(rep "${P_OFF}" $((20 - done)))${c_off} ${c_dim}${STEP}/${STEPS}${c_off}  ${c_bold}$*${c_off}"
}

row() {   # row ok|warn|fail|info "rótulo" "valor"
  local icon col
  case "$1" in
    ok) icon="${I_OK}"; col="${c_green}" ;; warn) icon="${I_WARN}"; col="${c_yellow}" ;;
    fail) icon="${I_FAIL}"; col="${c_red}" ;; *) icon="${I_INFO}"; col="${c_dim}" ;;
  esac
  local pad=$((24 - $(vlen "$2"))); [[ ${pad} -lt 1 ]] && pad=1
  printf "  %s%s%s %s%*s %s\n" "${col}" "${icon}" "${c_off}" "$2" "${pad}" "" "${3:-}"
}

box() {   # box "Título" "linha 1" "linha 2" ...
  local title="$1"; shift
  local inner=$((COLS - 4)) l pad w tcols
  for l in "$@"; do w=$(( $(vlen "${l}") + 1 )); [[ ${w} -gt ${inner} ]] && inner=${w}; done
  tcols="$(tput cols 2>/dev/null || echo 0)"; [[ "${tcols}" =~ ^[0-9]+$ ]] || tcols=0
  [[ ${tcols} -gt 0 && ${inner} -gt $((tcols - 4)) ]] && inner=$((tcols - 4))
  [[ ${inner} -lt $((COLS - 4)) ]] && inner=$((COLS - 4))
  printf "%s%s%s%s %s%s%s %s%s%s\n" "${c_line}" "${B_TL}" "$(rep "${B_H}" 1)" "${c_off}" "${c_bold}" "${title}" "${c_off}" "${c_line}" "$(rep "${B_H}" $((inner - $(vlen "${title}") - 1)))" "${B_TR}${c_off}"
  local part
  for l in "$@"; do
    if [[ $(vlen "${l}") -le ${inner} ]]; then
      pad=$((inner - $(vlen "${l}")))
      printf "%s%s%s %s%*s %s%s%s\n" "${c_line}" "${B_V}" "${c_off}" "${l}" "${pad}" "" "${c_line}" "${B_V}" "${c_off}"
      continue
    fi
    # linha maior que a tela: quebra nos espaços (caminhos longos são cortados), sem as cores
    while IFS= read -r part; do
      pad=$((inner - $(vlen "${part}"))); [[ ${pad} -lt 0 ]] && pad=0
      printf "%s%s%s %s%*s %s%s%s\n" "${c_line}" "${B_V}" "${c_off}" "${part}" "${pad}" "" "${c_line}" "${B_V}" "${c_off}"
    done < <(wrap_text "${inner}" "$(printf '%s' "${l}" | sed 's/\x1b\[[0-9;]*m//g')")
  done
  printf "%s%s%s%s\n" "${c_line}" "${B_BL}" "$(rep "${B_H}" $((inner + 2)))" "${B_BR}${c_off}"
}
wrap_text() {   # wrap_text LARGURA "texto" → linhas de até LARGURA caracteres; continuações recuadas como a 1ª linha (+2)
  local LC_ALL=C.UTF-8
  local width="$1" text="$2" indent next cur="" word words=()
  indent="${text%%[! ]*}"; text="${text#"${indent}"}"; next="${indent}  "
  read -ra words <<<"${text}"
  for word in ${words[@]+"${words[@]}"}; do
    while [[ $(( ${#indent} + ${#word} )) -gt ${width} ]]; do   # palavra maior que a linha (ex.: um caminho)
      if [[ -n "${cur}" ]]; then printf '%s%s\n' "${indent}" "${cur}"; cur=""; indent="${next}"; fi
      printf '%s%s\n' "${indent}" "${word:0:$((width - ${#indent}))}"; word="${word:$((width - ${#indent}))}"; indent="${next}"
    done
    if [[ -z "${cur}" ]]; then cur="${word}"
    elif [[ $(( ${#indent} + ${#cur} + 1 + ${#word} )) -le ${width} ]]; then cur+=" ${word}"
    else printf '%s%s\n' "${indent}" "${cur}"; cur="${word}"; indent="${next}"; fi
  done
  if [[ -n "${cur}" ]]; then printf '%s%s\n' "${indent}" "${cur}"; fi
  return 0
}

banner() {
  local g1=$'\033[38;5;46m' g2=$'\033[38;5;47m' g3=$'\033[38;5;48m' g4=$'\033[38;5;49m' g5=$'\033[38;5;44m'
  [[ ${COLOR} -eq 1 ]] || { g1=""; g2=""; g3=""; g4=""; g5=""; }
  echo
  echo -e "${g1}   ____          _               ____  _       _    __${c_off}"
  echo -e "${g2}  / ___|__ _ ___(_)_ __   ___   |  _ \\| | __ _| |_ / _| ___  _ __ _ __ ___${c_off}"
  echo -e "${g3} | |   / _\` / __| | '_ \\ / _ \\  | |_) | |/ _\` | __| |_ / _ \\| '__| '_ \` _ \\${c_off}"
  echo -e "${g4} | |__| (_| \\__ \\ | | | | (_) | |  __/| | (_| | |_|  _| (_) | |  | | | | | |${c_off}"
  echo -e "${g5}  \\____\\__,_|___/_|_| |_|\\___/  |_|   |_|\\__,_|\\__|_|  \\___/|_|  |_| |_| |_|${c_off}"
  echo -e "  ${c_dim}Assistente de instalação v${SCRIPT_VERSION}${c_off}"
  echo
}

# ---- executa um comando com animação; a saída completa vai para o log ----
cleanup() {
  [[ ${COLOR} -eq 1 ]] && tput cnorm 2>/dev/null || true
  [[ -n "${SUDO_KEEPALIVE_PID}" ]] && kill "${SUDO_KEEPALIVE_PID}" 2>/dev/null || true
}
trap cleanup EXIT
trap 'cleanup; echo; echo -e "${c_yellow}Interrompido.${c_off}"; exit 130' INT TERM
trap 'cleanup; echo -e "\n${c_red}${I_FAIL} Erro inesperado na linha ${LINENO}: ${BASH_COMMAND}${c_off}\n  Detalhes em ${LOG}" >&2' ERR

fmt_time() { local s=$1; if [[ ${s} -ge 60 ]]; then printf '%dm%02ds' $((s / 60)) $((s % 60)); else printf '%ds' "${s}"; fi; }

run() {   # run "descrição" comando args...
  local desc="$1"; shift
  local start=${SECONDS} rc=0 pid i=0
  if [[ ${COLOR} -eq 1 ]]; then
    tput civis 2>/dev/null || true
    "$@" >>"${LOG}" 2>&1 &
    pid=$!
    while kill -0 "${pid}" 2>/dev/null; do
      printf "\r  %s%s%s %s %s%s%s\033[K" "${c_accent}" "${SPIN[$((i % ${#SPIN[@]}))]}" "${c_off}" "${desc}" "${c_dim}" "$(fmt_time $((SECONDS - start)))" "${c_off}"
      i=$((i + 1)); sleep 0.1
    done
    if wait "${pid}"; then rc=0; else rc=$?; fi
    tput cnorm 2>/dev/null || true
  else
    if "$@" >>"${LOG}" 2>&1; then rc=0; else rc=$?; fi
  fi
  local el=$((SECONDS - start)) t=""
  [[ ${el} -ge 3 ]] && t=" ${c_dim}($(fmt_time "${el}"))${c_off}"
  if [[ ${rc} -eq 0 ]]; then
    printf "\r  %s%s%s %s%s\033[K\n" "${c_green}" "${I_OK}" "${c_off}" "${desc}" "${t}"
  else
    printf "\r  %s%s%s %s\033[K\n" "${c_red}" "${I_FAIL}" "${c_off}" "${desc}"
    show_failure
    exit "${rc}"
  fi
}

run_try() {   # run_try "descrição" comando...  → como o run, mas uma falha só avisa (devolve o código)
  local desc="$1"; shift
  local start=${SECONDS} rc=0 pid i=0
  if [[ ${COLOR} -eq 1 ]]; then
    tput civis 2>/dev/null || true
    "$@" >>"${LOG}" 2>&1 &
    pid=$!
    while kill -0 "${pid}" 2>/dev/null; do
      printf "\r  %s%s%s %s %s%s%s\033[K" "${c_accent}" "${SPIN[$((i % ${#SPIN[@]}))]}" "${c_off}" "${desc}" "${c_dim}" "$(fmt_time $((SECONDS - start)))" "${c_off}"
      i=$((i + 1)); sleep 0.1
    done
    if wait "${pid}"; then rc=0; else rc=$?; fi
    tput cnorm 2>/dev/null || true
  else
    if "$@" >>"${LOG}" 2>&1; then rc=0; else rc=$?; fi
  fi
  if [[ ${rc} -eq 0 ]]; then printf "\r  %s%s%s %s\033[K\n" "${c_green}" "${I_OK}" "${c_off}" "${desc}"
  else printf "\r  %s%s%s %s %s(não deu; tentando outro caminho)%s\033[K\n" "${c_yellow}" "${I_WARN}" "${c_off}" "${desc}" "${c_dim}" "${c_off}"; fi
  return "${rc}"
}

show_failure() {
  echo
  echo -e "${c_red}Falhou. Últimas linhas do registro:${c_off}"
  tail -n 14 "${LOG}" | sed "s/^/    ${c_dim}/; s/\$/${c_off}/"
  echo
  if grep -qiE "Allowed memory size|Killed|cannot allocate memory" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} faltou memória. Em servidor pequeno crie um swap:\n      sudo fallocate -l 2G /swapfile && sudo chmod 600 /swapfile && sudo mkswap /swapfile && sudo swapon /swapfile"
  elif grep -qiE "Could not resolve|Temporary failure|timed out|Connection refused|Failed to connect|unable to access" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} problema de internet/DNS. Confira a conexão e rode o assistente de novo."
  elif grep -qiE "dubious ownership" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} rode: git config --global --add safe.directory '*'"
  elif grep -qiE "Access denied|SQLSTATE" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} o MySQL recusou o acesso. Rode de novo e deixe o assistente criar o banco."
  elif grep -qiE "Authentication failed|Repository not found|could not read Username" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} o repositório é privado. Use --token SEU_TOKEN (veja --help)."
  elif grep -qiE "dpkg was interrupted|Could not get lock|lock-frontend" "${LOG}"; then
    echo -e "${c_yellow}Dica:${c_off} o apt está ocupado por outro processo. Aguarde alguns minutos (ou: sudo dpkg --configure -a) e rode de novo."
  fi
  echo -e "Registro completo: ${c_bold}${LOG}${c_off}"
}

usage() {
  cat <<EOF
Casino Platform — assistente de instalação v${SCRIPT_VERSION}

Sem opções, abre o assistente com menu. Opções:

 O QUE FAZER
  --local                  Instala no seu computador (http://127.0.0.1:PORTA)
  --domain <dominio>       Instala em servidor (VPS) para esse domínio
  --update                 Atualiza sem perder dados (backup automático antes)
  --backup                 Faz um backup completo (banco, .env e arquivos enviados)
  --restore [arquivo.zip]  Restaura um backup (sem arquivo: escolhe entre os existentes)
  --uninstall              Desinstala: mostra as instalações encontradas (inclusive restos
                           de instalações antigas) e remove as escolhidas, com backup final
  --diagnose               Mostra o diagnóstico do ambiente e da instalação
  --start | --stop | --status   Liga, desliga ou consulta o site local

 DESINSTALAR (com --uninstall)
  --dir <pasta>            Só esta instalação          --all   Todas as encontradas
  --purge                  Apaga também as pastas do código, sem perguntar
  --purge-packages         Remove também os programas que o assistente instalou (PHP, MariaDB...)
  --keep-database          Não apaga o banco de dados
  --no-backup              Não faz o backup final (cuidado)

 DADOS DO SITE
  --name <nome>            Nome do site            (padrão: Meu Cassino)
  --email <email>          E-mail do administrador (login do painel)
  --admin-pass <senha>     Senha do administrador  (VPS: gerada; local: admin12345)
  --ssl                    VPS: certificado SSL gratuito (Let's Encrypt)
  --port <porta>           Porta HTTP (VPS: 80; local: primeira livre a partir da 8000)

 MODO LOCAL
  --stack                  Usa Nginx + PHP-FPM (igual a um servidor) em vez do servidor embutido do PHP
  --lan                    Libera acesso de outros aparelhos da sua rede
  --no-serve               Instala mas não liga o site
  --db-host/--db-port/--db-name/--db-user/--db-pass
                           Usa um MySQL específico (padrão: cria banco e usuário sozinho)

 ORIGEM DO CÓDIGO
  --source <pasta|zip>     Usa esta pasta ou arquivo .zip em vez de baixar
  --repo <url>             Repositório (padrão: ${REPO_URL})
  --branch <branch>        Branch (padrão: ${BRANCH})
  --token <token>          Token do GitHub (repositório privado). Também lê GITHUB_TOKEN.
                           Crie em: GitHub → Settings → Developer settings → Personal access tokens
                           (permissão "Contents: Read" no repositório)

 OUTROS
  --dir <pasta>            Pasta da aplicação (local: a própria cópia; VPS: /var/www/casino)
  --php <versão>           Versão do PHP a instalar (8.2, 8.3 ou 8.4; padrão ${PHP_VERSION})
  --system-upgrade         Também atualiza os pacotes do sistema (apt upgrade) ao instalar/atualizar
  --reinstall              Apaga a instalação existente e instala de novo
  --no-color               Sem cores nem animações
  --auto                   100% automático: instala no seu computador sem perguntar nada
  -y, --yes                Não pergunta nada (usa os padrões)
  -h, --help               Esta ajuda
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --auto) AUTO=1; ASSUME_YES=1; shift ;;
    --local) TARGET="local"; shift ;;
    --server) TARGET="server"; shift ;;
    --domain) DOMAIN="$2"; TARGET="${TARGET:-server}"; shift 2 ;;
    --email|--admin-email) ADMIN_EMAIL="$2"; shift 2 ;;
    --name) SITE_NAME="$2"; shift 2 ;;
    --admin-pass) ADMIN_PASS="$2"; shift 2 ;;
    --ssl) ENABLE_SSL=1; shift ;;
    --port) HTTP_PORT="$2"; shift 2 ;;
    --stack) STACK=1; shift ;;
    --lan) LAN=1; shift ;;
    --no-serve) NO_SERVE=1; shift ;;
    --db-host) DB_HOST="$2"; shift 2 ;;
    --db-port) DB_PORT="$2"; shift 2 ;;
    --db-name) DB_NAME="$2"; shift 2 ;;
    --db-user) DB_USER="$2"; shift 2 ;;
    --db-pass) DB_PASS="$2"; shift 2 ;;
    --source) SOURCE="$2"; shift 2 ;;
    --repo) REPO_URL="$2"; shift 2 ;;
    --branch) BRANCH="$2"; shift 2 ;;
    --token) TOKEN="$2"; shift 2 ;;
    --dir) APP_DIR="$2"; shift 2 ;;
    --php) PHP_VERSION="$2"; shift 2 ;;
    --system-upgrade) SYS_UPGRADE=1; shift ;;
    --reinstall) REINSTALL=1; shift ;;
    --no-color) COLOR=0; shift ;;
    --update) MODE="update"; shift ;;
    --backup) MODE="backup"; shift ;;
    --restore) MODE="restore"; if [[ $# -ge 2 && "$2" != --* ]]; then RESTORE_FILE="$2"; shift; fi; shift ;;
    --uninstall|--desinstalar) MODE="uninstall"; shift ;;
    --all) UN_ALL=1; shift ;;
    --purge) PURGE=1; shift ;;
    --purge-packages) PURGE_PACKAGES=1; shift ;;
    --no-backup) NO_BACKUP=1; shift ;;
    --keep-database) KEEP_DB=1; shift ;;
    --diagnose) MODE="diagnose"; shift ;;
    --start|--serve) MODE="start"; TARGET="${TARGET:-local}"; shift ;;
    --stop) MODE="stop"; TARGET="${TARGET:-local}"; shift ;;
    --status) MODE="status"; TARGET="${TARGET:-local}"; shift ;;
    -y|--yes|--non-interactive) ASSUME_YES=1; shift ;;
    -h|--help) setup_ui; usage; exit 0 ;;
    --mode) [[ "$2" == "diagnose" ]] && MODE="diagnose"; shift 2 ;;   # compatibilidade
    *) setup_ui; die "Opção desconhecida: $1  (use --help)" ;;
  esac
done
setup_ui
[[ -t 0 && -t 1 && ${ASSUME_YES} -eq 0 ]] && INTERACTIVE=1
[[ -z "${MODE}" ]] && MODE="install"
[[ ${AUTO} -eq 1 && -z "${TARGET}" ]] && TARGET="local"   # --auto sem destino = seu computador

# =============================================================================
#  Perguntas
# =============================================================================
ask() {      # ask VAR "pergunta" "padrão"
  local __var="$1" __q="$2" __def="${3:-}" __ans=""
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    read -rp "$(echo -e "  ${c_accent}?${c_off} ${__q}${__def:+ ${c_dim}[${__def}]${c_off}}: ")" __ans || true
  fi
  printf -v "${__var}" '%s' "${__ans:-${__def}}"
}

ask_secret() {   # ask_secret VAR "pergunta" "padrão"
  local __var="$1" __q="$2" __def="${3:-}" __ans=""
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    read -rsp "$(echo -e "  ${c_accent}?${c_off} ${__q}${__def:+ ${c_dim}[ENTER = ${__def}]${c_off}}: ")" __ans || true; echo
  fi
  printf -v "${__var}" '%s' "${__ans:-${__def}}"
}

confirm() {      # confirm "pergunta" s|n   → 0 = sim
  local q="$1" def="${2:-s}" ans=""
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    read -rp "$(echo -e "  ${c_accent}?${c_off} ${q} ${c_dim}[$([[ ${def} == s ]] && echo 'S/n' || echo 's/N')]${c_off}: ")" ans || true
  fi
  ans="$(echo "${ans:-${def}}" | tr 'A-Z' 'a-z')"
  [[ "${ans}" == s* || "${ans}" == y* ]]
}

choose() {   # choose VAR "título" "opção 1" "opção 2" ...   → índice 1..n (setas ↑/↓ + Enter, ou número)
  local __var="$1" title="$2"; shift 2
  local opts=("$@") n=$# sel=0 key k2 i
  if [[ ${INTERACTIVE} -eq 0 ]]; then printf -v "${__var}" '%s' 1; return; fi
  echo -e "  ${c_bold}${title}${c_off}  ${c_dim}(↑/↓ e Enter, ou digite o número)${c_off}\n"
  draw() {
    for i in "${!opts[@]}"; do
      if [[ ${i} -eq ${sel} ]]; then printf "  %s%s %s%s %s\033[K%s\n" "${c_hi}" "${c_accent}" "${I_ARROW}" "${c_off}${c_hi}${c_bold}" "${opts[${i}]}" "${c_off}"
      else printf "    %s%s\033[K\n" "${c_dim}" "${opts[${i}]}${c_off}"; fi
    done
  }
  tput civis 2>/dev/null || true
  draw
  while true; do
    IFS= read -rsn1 key || break
    if [[ "${key}" == $'\033' ]]; then
      read -rsn2 -t 0.05 k2 || true
      case "${k2}" in '[A') sel=$(((sel + n - 1) % n)) ;; '[B') sel=$(((sel + 1) % n)) ;; esac
    elif [[ -z "${key}" ]]; then break
    elif [[ "${key}" =~ ^[0-9]$ && "${key}" -ge 1 && "${key}" -le ${n} ]]; then sel=$((key - 1)); printf '\033[%dA' "${n}"; draw; break
    elif [[ "${key}" == "q" || "${key}" == "0" ]]; then sel=$((n - 1)); break; fi
    printf '\033[%dA' "${n}"; draw
  done
  tput cnorm 2>/dev/null || true
  echo
  printf -v "${__var}" '%s' $((sel + 1))
}

random_secret() { local s; s="$(openssl rand -hex 32 2>/dev/null || head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')"; echo "${s:0:${1:-24}}"; }
have() { command -v "$1" >/dev/null 2>&1; }

has_tty() { [[ -t 0 ]] || { [[ -r /dev/tty ]] && (: </dev/tty) 2>/dev/null; }; }

setup_sudo() {   # setup_sudo ["motivo"] [soft] → pede a senha do sudo uma única vez e a mantém válida
  [[ ${EUID} -eq 0 || -n "${SUDO}" ]] && return 0
  local soft="${2:-}"
  if ! have sudo; then [[ -n "${soft}" ]] && return 1; die "Preciso de permissão de administrador, mas o 'sudo' não existe. Rode como root."; fi
  if ! sudo -n true 2>/dev/null; then
    if ! has_tty; then
      [[ -n "${soft}" ]] && return 1
      die "Preciso da senha de administrador (sudo) para ${1:-instalar pacotes do sistema}, mas não há terminal para digitá-la. Rode em um terminal."
    fi
    echo -e "  ${c_dim}Vou pedir a senha do seu usuário do computador (sudo) para ${1:-instalar pacotes do sistema}.${c_off}"
    if ! sudo -v; then
      [[ -n "${soft}" ]] && { warn "O sudo não foi liberado (senha incorreta ou usuário sem permissão)."; return 1; }
      die "Sem permissão de administrador (senha do sudo incorreta?)."
    fi
  fi
  SUDO="sudo"
  # mantém a autorização viva durante instalações longas
  ( while true; do sudo -n true 2>/dev/null; sleep 40; kill -0 "$$" 2>/dev/null || exit; done ) </dev/null >/dev/null 2>&1 &
  SUDO_KEEPALIVE_PID=$!
}

require_root() {   # reabre com sudo se necessário
  [[ ${EUID} -eq 0 ]] && return 0
  if have sudo && [[ -n "${SELF}" ]]; then
    echo -e "  ${c_dim}Esta instalação precisa de administrador; reabrindo com sudo...${c_off}"
    # sem "sudo -E": o sudo-rs (padrão no Ubuntu 26.04) não aceita; repassa só o necessário
    local envs=("TERM=${TERM:-xterm}") v
    for v in GITHUB_TOKEN GH_TOKEN REPO_SLUG REPO_URL BRANCH PHP_VERSION CASINO_NO_UPDATE NO_COLOR; do
      if [[ -n "${!v:-}" ]]; then envs+=("${v}=${!v}"); fi
    done
    exec sudo env "${envs[@]}" bash "${SELF}" "${ORIG_ARGS[@]}"
  fi
  die "Execute como root:  sudo bash scripts/auto-install.sh ..."
}

# =============================================================================
#  Ambiente
# =============================================================================
OS_ID=""; OS_NAME="desconhecido"; OS_CODENAME=""; IS_APT=0; IS_WSL=0; IS_MAC=0; ARCH="$(uname -m)"; IS_DESKTOP=0
detect_env() {
  case "$(uname -s)" in Darwin) IS_MAC=1; OS_NAME="macOS $(sw_vers -productVersion 2>/dev/null || true)" ;; esac
  if [[ -f /etc/os-release ]]; then
    OS_ID="$(. /etc/os-release && echo "${ID:-}")"
    OS_NAME="$(. /etc/os-release && echo "${PRETTY_NAME:-${ID:-linux}}")"
    OS_CODENAME="$(. /etc/os-release && echo "${UBUNTU_CODENAME:-${VERSION_CODENAME:-}}")"
    local like; like="$(. /etc/os-release && echo "${ID_LIKE:-}")"
    if [[ "${OS_ID}" == ubuntu || "${OS_ID}" == debian || "${like}" == *debian* || "${like}" == *ubuntu* ]]; then IS_APT=1; fi
  fi
  grep -qi microsoft /proc/version 2>/dev/null && IS_WSL=1
  [[ -n "${XDG_CURRENT_DESKTOP:-}${DISPLAY:-}${WAYLAND_DISPLAY:-}" ]] && IS_DESKTOP=1
  return 0
}

php_ok() { have php && php -r 'exit(PHP_VERSION_ID >= 80200 ? 0 : 1);' 2>/dev/null; }
php_ver() { php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || echo "${PHP_VERSION}"; }
missing_exts() {
  local m=() e mods
  mods="$(php -m 2>/dev/null | tr 'A-Z' 'a-z')"
  for e in pdo_mysql mbstring xml curl zip intl gd bcmath fileinfo openssl tokenizer ctype dom; do
    printf '%s\n' "${mods}" | grep -qx "${e}" || m+=("${e}")
  done
  echo "${m[*]:-}"
}
db_server_present() { have mysqld || have mariadbd || [[ -x /usr/sbin/mariadbd || -x /usr/sbin/mysqld ]] || [[ -x /opt/homebrew/bin/mariadbd ]]; }
free_gb() {   # GB livres na pasta (ou no primeiro pai que já exista)
  local d="${1:-.}"
  while [[ ! -d "${d}" && "${d}" != "/" ]]; do d="$(dirname "${d}")"; done
  df -Pk "${d}" 2>/dev/null | awk 'NR==2{printf "%d", $4/1024/1024}' || true
}
ram_gb() { if have free; then free -m | awk '/Mem:/{printf "%.1f", $2/1024}'; fi; }

# ---- diagnóstico visual do ambiente (antes de instalar) ----
NEED_PHP=0; NEED_DB=0; NEED_TOOLS=0
preflight() {   # preflight local|server
  local mode="$1" v e
  box "Diagnóstico do ambiente" \
      "$(printf '%-12s %s' 'Sistema' "${OS_NAME}$([[ ${IS_WSL} -eq 1 ]] && echo ' (WSL)' || true)  ${DOT}  ${ARCH}")" \
      "$(printf '%-12s %s' 'Tipo' "$([[ ${IS_DESKTOP} -eq 1 ]] && echo 'computador com interface gráfica' || echo 'servidor / terminal')  ${DOT}  usuário $(id -un)")"
  echo
  [[ ${IS_APT} -eq 1 ]] && row ok "Gerenciador de pacotes" "apt (instalação automática disponível)" \
    || { [[ ${IS_MAC} -eq 1 ]] && row warn "Gerenciador de pacotes" "Homebrew (instalação manual dos itens que faltarem)" || row warn "Gerenciador de pacotes" "não suportado para instalação automática"; }
  local code; code="$(curl -s -m 6 -o /dev/null -w '%{http_code}' https://github.com 2>/dev/null || true)"
  if [[ "${code}" =~ ^[23][0-9][0-9]$ ]]; then row ok "Internet" "conectado (GitHub acessível)"; else row warn "Internet" "não consegui acessar o GitHub (necessário para baixar pacotes)"; fi
  local fg; fg="$(free_gb "${APP_DIR:-${PWD}}")"
  if [[ -n "${fg}" && "${fg}" -lt 3 ]]; then row warn "Espaço em disco" "${fg} GB livres (recomendado: 3 GB)"; else row ok "Espaço em disco" "${fg:-?} GB livres"; fi
  local rg; rg="$(ram_gb)"
  [[ -n "${rg}" ]] && { if awk "BEGIN{exit !(${rg} < 1.0)}"; then row warn "Memória" "${rg} GB (pouca: pode precisar de swap)"; else row ok "Memória" "${rg} GB"; fi; }
  have curl && row ok "curl" "$(curl --version 2>/dev/null | head -1 | cut -d' ' -f2 || true)" || { row fail "curl" "não instalado"; NEED_TOOLS=1; }
  have git && row ok "git" "$(git --version 2>/dev/null | cut -d' ' -f3 || true)" || { row warn "git" "não instalado"; NEED_TOOLS=1; }
  have unzip && have rsync || { row warn "unzip / rsync" "faltando"; NEED_TOOLS=1; }
  NEED_PHP=0
  if php_ok; then
    v="$(php -r 'echo PHP_VERSION;')"; e="$(missing_exts)"
    if [[ -z "${e}" ]]; then row ok "PHP" "${v} com todas as extensões"; else row warn "PHP" "${v} — faltam extensões: ${e}"; NEED_PHP=1; fi
  elif have php; then row warn "PHP" "$(php -r 'echo PHP_VERSION;') (precisa de 8.2 ou mais novo)"; NEED_PHP=1
  else row warn "PHP" "não instalado"; NEED_PHP=1; fi
  NEED_DB=0
  if [[ -n "${DB_USER}" ]]; then row info "MySQL/MariaDB" "usando o informado (${DB_USER}@${DB_HOST:-127.0.0.1})"
  elif db_server_present; then
    if mysqladmin ping >/dev/null 2>&1 || ${SUDO:-} mysqladmin ping >/dev/null 2>&1; then row ok "MySQL/MariaDB" "instalado e respondendo"; else row ok "MySQL/MariaDB" "instalado (será iniciado)"; fi
  else row warn "MySQL/MariaDB" "não instalado"; NEED_DB=1; fi
  if have composer && php_ok; then row ok "Composer" "$(composer --version 2>/dev/null | head -1 | cut -d' ' -f3 || true)"
  elif have composer; then row info "Composer" "instalado (vai funcionar depois do PHP)"
  elif [[ -x "${HOME}/.local/bin/composer" ]] && php_ok; then row ok "Composer" "$(php "${HOME}/.local/bin/composer" --version 2>/dev/null | head -1 | cut -d' ' -f3 || true) (~/.local/bin)"
  else row info "Composer" "será baixado (não precisa de administrador)"; fi
  if [[ "${mode}" == "server" ]]; then
    have nginx && row ok "Nginx" "instalado" || row info "Nginx" "será instalado"
    if have ss && ss -ltnH "sport = :80" 2>/dev/null | grep -q .; then row warn "Porta 80" "em uso por $(ss -ltnpH 'sport = :80' 2>/dev/null | grep -o 'users:(("[^"]*' | head -1 | cut -d'"' -f2)"; else row ok "Porta 80" "livre"; fi
  fi
  echo
}

php_plan_label() {   # que PHP vai ser instalado (a 8.3 não existe em sistemas novos, ex.: Ubuntu 26.04)
  if [[ ${IS_APT} -eq 1 ]] && ! apt-cache show "php${PHP_VERSION}-cli" >/dev/null 2>&1; then
    local v; for v in 8.4 8.3 8.2 8.5; do
      if apt-cache show "php${v}-cli" >/dev/null 2>&1; then echo "PHP ${PHP_VERSION} (ou o ${v} do seu sistema)"; return 0; fi
    done
  fi
  echo "PHP ${PHP_VERSION}"
}

show_plan() {   # lista o que será instalado e pede confirmação
  local items=()
  [[ ${NEED_TOOLS} -eq 1 ]] && items+=("git, curl, unzip e rsync")
  [[ ${NEED_PHP} -eq 1 ]] && items+=("$(php_plan_label) + extensões")
  [[ ${NEED_DB} -eq 1 ]] && items+=("MariaDB (banco de dados)")
  if [[ ${#items[@]} -eq 0 ]]; then ok "Seu ambiente já tem tudo o que é necessário."; return 0; fi
  local l=(); local it; for it in "${items[@]}"; do l+=("  ${I_ARROW} ${it}"); done
  box "O assistente vai instalar" "${l[@]}"
  echo
  if [[ ${IS_APT} -ne 1 ]]; then
    if [[ ${IS_MAC} -eq 1 ]]; then die "Instale com Homebrew:  brew install php composer mariadb && brew services start mariadb   (e rode este assistente de novo)"; fi
    die "Instale PHP 8.2+ (pdo_mysql mbstring xml curl zip intl gd bcmath) e MySQL/MariaDB pelo gerenciador do seu sistema e rode de novo."
  fi
  confirm "Posso instalar agora? (vai pedir sua senha de administrador)" s || die "Instalação cancelada. Instale os itens acima e rode de novo."
}

legal_notice() {
  [[ ${INTERACTIVE} -eq 1 ]] || return 0
  box "Aviso legal" \
    "Operar apostas/jogos de azar exige licença na maioria dos países" \
    "(no Brasil: autorização da SPA/Ministério da Fazenda — Lei 14.790/2023)." \
    "Você é responsável por licenças, KYC/PLD, jogo responsável, LGPD e" \
    "pelo direito de uso dos jogos e provedores que ativar."
  local a; read -rp "$(echo -e "  ${c_accent}?${c_off} Digite ${c_bold}ACEITO${c_off} para continuar: ")" a || true
  [[ "${a}" == "ACEITO" ]] || die "Instalação cancelada."
}

# =============================================================================
#  Código-fonte
# =============================================================================
git_auth_args() {   # token só em memória (cabeçalho), nunca gravado no .git/config
  GIT_AUTH=()
  if [[ -n "${TOKEN}" ]]; then
    local b; b="$(printf 'x-access-token:%s' "${TOKEN}" | base64 | tr -d '\n')"
    GIT_AUTH=(-c "http.extraHeader=Authorization: Basic ${b}")
  fi
}

explain_private_repo() {
  echo
  box "Não consegui baixar o código" \
    "${REPO_URL}" \
    "" \
    "Se o repositório for PRIVADO, o GitHub responde \"404\" para quem" \
    "não está autenticado. Escolha um caminho:" \
    "" \
    "1) Pelo navegador: abra o repositório logado no GitHub → botão" \
    "   verde \"Code\" → \"Download ZIP\". Extraia e rode dentro da pasta:" \
    "       bash instalar.sh" \
    "2) Com git:   git clone https://github.com/${REPO_SLUG}.git" \
    "3) Com token: bash scripts/auto-install.sh --token SEU_TOKEN" \
    "   (GitHub → Settings → Developer settings → Personal access tokens" \
    "    → Fine-grained → o repositório → \"Contents: Read-only\")"
  echo
}

git_fetch_repo() {   # git_fetch_repo <destino>
  local dest="$1"
  git_auth_args
  export GIT_TERMINAL_PROMPT=0
  if [[ -d "${dest}/.git" ]]; then
    run "Atualizando o código (${BRANCH})" git "${GIT_AUTH[@]}" -C "${dest}" fetch -q --depth 1 origin "${BRANCH}"
    run "Aplicando a versão nova" git -C "${dest}" reset -q --hard FETCH_HEAD
  else
    if ! git "${GIT_AUTH[@]}" clone -q --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${dest}" >>"${LOG}" 2>&1; then
      if [[ -z "${TOKEN}" && ${INTERACTIVE} -eq 1 ]]; then
        explain_private_repo
        ask_secret TOKEN "Cole seu token do GitHub (ou ENTER para cancelar)" ""
        [[ -n "${TOKEN}" ]] || die "Instalação cancelada."
        git_auth_args
        rm -rf "${dest}"
        run "Baixando o código" git "${GIT_AUTH[@]}" clone -q --depth 1 --branch "${BRANCH}" "${REPO_URL}" "${dest}"
      else
        explain_private_repo; die "Não foi possível baixar o código."
      fi
    else
      ok "Código baixado"
    fi
  fi
  git -C "${dest}" remote set-url origin "${REPO_URL}" 2>/dev/null || true   # URL fica sem credenciais
}

SRC=""
resolve_source() {
  local tmp
  if [[ -n "${SOURCE}" ]]; then
    if [[ -f "${SOURCE}" && "${SOURCE}" == *.zip ]]; then
      have unzip || die "Preciso do 'unzip' para abrir ${SOURCE}."
      tmp="$(mktemp -d)"; run "Extraindo ${SOURCE##*/}" unzip -q "${SOURCE}" -d "${tmp}"
      SRC="$(dirname "$(find "${tmp}" -maxdepth 3 -name artisan | head -1)")"
    elif [[ -d "${SOURCE}" ]]; then SRC="$(cd "${SOURCE}" && pwd)"
    else die "--source: '${SOURCE}' não é uma pasta nem um .zip."; fi
    [[ -f "${SRC}/artisan" ]] || die "Não encontrei a aplicação (arquivo artisan) em ${SOURCE}."
    ok "Usando o código de ${SRC}"; return
  fi
  if [[ -n "${CHECKOUT}" ]]; then
    SRC="${CHECKOUT}"; ok "Usando o código desta pasta (${SRC}) — nada para baixar"; return
  fi
  have git || die "Preciso do 'git' para baixar o código (ou use --source com uma pasta/zip já baixado)."
  if [[ "${TARGET}" == "server" ]]; then
    $SUDO mkdir -p "${SRC_DIR}"; [[ -n "${SUDO}" ]] && $SUDO chown "$(id -u):$(id -g)" "${SRC_DIR}"
    git_fetch_repo "${SRC_DIR}"; SRC="${SRC_DIR}"
  else
    local dest="${APP_DIR:-${HOME}/casino}"
    if [[ -f "${dest}/artisan" && -d "${dest}/.git" ]]; then git_fetch_repo "${dest}"
    elif [[ -e "${dest}" && -n "$(ls -A "${dest}" 2>/dev/null)" ]]; then die "A pasta ${dest} já existe e não é uma cópia do projeto. Use --dir outra-pasta."
    else git_fetch_repo "${dest}"; fi
    SRC="${dest}"; APP_DIR="${dest}"
  fi
  [[ -f "${SRC}/artisan" ]] || SRC="${SRC}/extraia para public_html/public_html"   # estrutura antiga
  [[ -f "${SRC}/artisan" ]] || die "Aplicação não encontrada no código baixado."
}

refresh_checkout() {   # update: puxa as novidades quando o código é uma cópia git
  [[ -n "${CHECKOUT}" && -d "${CHECKOUT}/.git" && -z "${SOURCE}" ]] || return 0
  have git || return 0
  git_auth_args; export GIT_TERMINAL_PROMPT=0
  if git "${GIT_AUTH[@]}" -C "${CHECKOUT}" pull -q --ff-only origin "${BRANCH}" >>"${LOG}" 2>&1; then ok "Código atualizado (git pull)"
  else warn "Não consegui atualizar pelo git (sem acesso ou há alterações locais). Seguindo com o que está na pasta."; fi
}

deploy_code() {   # copia SRC → APP_DIR (se forem pastas diferentes)
  mkdir -p "${APP_DIR}"
  if [[ "$(cd "${APP_DIR}" && pwd)" != "$(cd "${SRC}" && pwd)" ]]; then
    have rsync || die "Preciso do 'rsync' (sudo apt install rsync)."
    run "Copiando arquivos para ${APP_DIR}" rsync -a --delete \
      --exclude '.git' --exclude '/.github' --exclude '/docs' --exclude '/scripts' --exclude '/docker' \
      --exclude '/docker-compose.yml' --exclude '/.dockerignore' --exclude '/instalar.sh' --exclude '/tests' --exclude '/dist' \
      --exclude '/.env' --exclude '/storage/local-server.pid' --exclude '/storage/app/' --exclude '/storage/logs/' --exclude '/storage/installed.lock' \
      --exclude '/public/storage/' --exclude '/vendor/' --exclude '/node_modules/' \
      "${SRC}/" "${APP_DIR}/"
  fi
  mkdir -p "${APP_DIR}/storage/"{app/public,logs,framework/{cache/data,sessions,views}} "${APP_DIR}/bootstrap/cache"
}

# =============================================================================
#  PHP, Composer, banco de dados
# =============================================================================
pkg_installed() { dpkg-query -W -f='${Status}' "$1" 2>/dev/null | grep -q "install ok installed"; }

apt_install() {   # apt_install "descrição" pacotes...  (anota os que não existiam, para a desinstalação)
  local desc="$1"; shift
  local p new=()
  for p in "$@"; do pkg_installed "${p}" || new+=("${p}"); done
  run "${desc}" ${SUDO} env DEBIAN_FRONTEND=noninteractive apt-get install -y -qq "$@"
  if [[ ${#new[@]} -gt 0 ]]; then INSTALLED_PKGS+=" ${new[*]}"; fi
  return 0
}

# Repositório de PHP (PPA ondrej / sury) que não tem pacotes para esta versão do sistema derruba o "apt update":
# remove só quando o servidor responde 404 para o codinome desta versão (ex.: Ubuntu 26.04 "resolute").
drop_broken_php_repos() {
  local f url code="${CODENAME}" http
  for f in /etc/apt/sources.list.d/*ondrej* /etc/apt/sources.list.d/*sury*; do
    [ -f "$f" ] || continue
    case "$f" in
      *sury*) url="https://packages.sury.org/php/dists/${code}/Release" ;;
      *) url="https://ppa.launchpadcontent.net/ondrej/php/ubuntu/dists/${code}/Release" ;;
    esac
    http="$(curl -s -o /dev/null -m 20 -w '%{http_code}' "$url" || true)"
    if [ "${http}" = "404" ]; then rm -f "$f"; echo "removido repositório sem suporte para ${code}: $f"; fi
  done
}
apt_refresh() {
  if apt-get update -qq; then return 0; fi
  drop_broken_php_repos
  apt-get update -qq
}
apt_update() {
  run "Atualizando a lista de pacotes" ${SUDO} bash -c "CODENAME='${OS_CODENAME}'; $(declare -f drop_broken_php_repos apt_refresh); apt_refresh"
}

http_code() { curl -s -o /dev/null -m 20 -w '%{http_code}' "$1" || true; }

apt_php_repo() {   # garante que o apt conhece o PHP desejado; se o sistema for novo demais, usa a melhor versão disponível
  if ! apt-cache show "php${PHP_VERSION}-cli" >/dev/null 2>&1; then
    run "Preparando repositórios" ${SUDO} env DEBIAN_FRONTEND=noninteractive apt-get install -y -qq ca-certificates curl gnupg lsb-release software-properties-common
    local code="${OS_CODENAME:-$(lsb_release -sc 2>/dev/null)}"
    local sury="https://packages.sury.org/php/dists/${code}/Release" ppa="https://ppa.launchpadcontent.net/ondrej/php/ubuntu/dists/${code}/Release"
    local add_sury="curl -fsSL https://packages.sury.org/php/apt.gpg | ${SUDO} gpg --dearmor --yes -o /usr/share/keyrings/sury-php.gpg && echo 'deb [signed-by=/usr/share/keyrings/sury-php.gpg] https://packages.sury.org/php/ ${code} main' | ${SUDO} tee /etc/apt/sources.list.d/sury-php.list >/dev/null"
    if [[ "${OS_ID}" == "ubuntu" && "$(http_code "${ppa}")" == "200" ]]; then
      run "Adicionando o repositório do PHP (ondrej/php)" ${SUDO} add-apt-repository -y ppa:ondrej/php
    elif [[ "$(http_code "${sury}")" == "200" ]]; then
      run "Adicionando o repositório do PHP (sury.org)" bash -c "${add_sury}"
    else
      info "Não há repositório extra de PHP para ${code}; usando o PHP do próprio sistema."
    fi
    apt_update
    if ! apt-cache show "php${PHP_VERSION}-cli" >/dev/null 2>&1; then
      local v
      for v in 8.4 8.3 8.2 8.5; do
        if apt-cache show "php${v}-cli" >/dev/null 2>&1; then
          warn "PHP ${PHP_VERSION} não existe para ${OS_NAME}; usando o PHP ${v} (compatível)."
          PHP_VERSION="${v}"; return 0
        fi
      done
      die "Nenhum PHP 8.2+ disponível nos repositórios do ${OS_NAME}. Instale o PHP manualmente e rode de novo."
    fi
  fi
}

PHP_PKGS=(cli mysql mbstring xml curl zip intl gd bcmath)
install_php() {   # PHP (+ extensões) via apt; mantém a versão instalada se já for >= 8.2
  local v="${PHP_VERSION}"
  if php_ok; then v="$(php_ver)"; else apt_php_repo; v="${PHP_VERSION}"; fi
  local pk=() p; for p in "${PHP_PKGS[@]}"; do pk+=("php${v}-${p}"); done
  apt_install "Instalando PHP ${v} e extensões" "${pk[@]}"
  have php || die "O PHP não ficou disponível no PATH."
  php_ok || die "O PHP instalado é antigo ($(php -r 'echo PHP_VERSION;')). Use --php 8.3 para instalar uma versão nova."
}

system_upgrade() {
  [[ ${SYS_UPGRADE} -eq 1 && ${IS_APT} -eq 1 ]] || return 0
  run "Atualizando os pacotes do sistema" ${SUDO} env DEBIAN_FRONTEND=noninteractive apt-get upgrade -y -qq
}

ensure_composer() {   # 1) o que já existe  2) getcomposer.org  3) GitHub  4) pacote do sistema (apt)
  if have composer; then COMPOSER="composer"; return 0; fi
  COMPOSER="${HOME}/.local/bin/composer"
  if [[ -x "${COMPOSER}" ]] && php "${COMPOSER}" --version >/dev/null 2>&1; then return 0; fi
  mkdir -p "${HOME}/.local/bin"
  local t; t="$(mktemp -d)"
  if run_try "Baixando o Composer (getcomposer.org)" bash -c "cd '${t}' && curl -fsSL --retry 2 https://getcomposer.org/installer -o setup.php && php setup.php --quiet --install-dir='${HOME}/.local/bin' --filename=composer"; then
    COMPOSER_DOWNLOADED="${COMPOSER}"
  elif run_try "Baixando o Composer (GitHub)" bash -c "curl -fsSL --retry 2 -o '${t}/composer.phar' https://github.com/composer/composer/releases/latest/download/composer.phar && php '${t}/composer.phar' --version && install -m 755 '${t}/composer.phar' '${COMPOSER}'"; then
    COMPOSER_DOWNLOADED="${COMPOSER}"
  elif [[ ${IS_APT} -eq 1 ]] && { [[ ${EUID} -eq 0 ]] || setup_sudo "instalar o Composer do sistema" soft; }; then
    apt_install "Instalando o Composer (pacote do sistema)" composer
    COMPOSER="composer"
  else
    rm -rf "${t}"
    die "Não consegui obter o Composer (sem acesso a getcomposer.org nem ao GitHub). Instale-o e rode de novo: https://getcomposer.org/download"
  fi
  rm -rf "${t}"
  return 0
}

composer_install() {
  local args=(install --no-dev --optimize-autoloader --no-interaction --no-progress)
  # token do GitHub (--token/GITHUB_TOKEN): evita o limite de downloads anônimos do GitHub
  if [[ -n "${TOKEN}" && -z "${COMPOSER_AUTH:-}" ]]; then export COMPOSER_AUTH="{\"github-oauth\":{\"github.com\":\"${TOKEN}\"}}"; fi
  ( cd "${APP_DIR}"
    export COMPOSER_ALLOW_SUPERUSER=1
    if ! "${COMPOSER}" "${args[@]}" --prefer-dist --dry-run >/dev/null 2>&1 && [[ -d vendor ]]; then rm -rf vendor; fi
  ) || true
  if ! run_try "Instalando as dependências do PHP (pode levar alguns minutos)" bash -c "cd '${APP_DIR}' && COMPOSER_ALLOW_SUPERUSER=1 '${COMPOSER}' ${args[*]} --prefer-dist"; then
    # o GitHub limita downloads anônimos (erro "Could not authenticate against github.com"): baixa pelo git
    have git || die "Não consegui baixar as dependências e o git não está instalado. Instale o git e rode de novo."
    if ! run_try "Baixando as dependências pelo git (alternativa)" bash -c "cd '${APP_DIR}' && rm -rf vendor && COMPOSER_ALLOW_SUPERUSER=1 '${COMPOSER}' ${args[*]} --prefer-source"; then
      show_failure
      if grep -q "Could not authenticate against github.com\|API rate limit" "${LOG}"; then
        info "O GitHub limitou os downloads deste endereço de internet. Espere 1 hora ou rode com um token:"
        info "  bash instalar.sh --token SEU_TOKEN   (GitHub → Settings → Developer settings → Personal access tokens)"
      fi
      die "Não consegui instalar as dependências do PHP."
    fi
  fi
  if [[ ! -f "${APP_DIR}/public/build/manifest.json" ]]; then
    warn "O frontend não está compilado nesta cópia."
    have npm || die "Instale o Node.js 20+ e rode: cd ${APP_DIR} && npm ci && npm run build"
    run "Compilando o frontend" bash -c "cd '${APP_DIR}' && npm ci --no-audit --no-fund && npm run build"
  fi
}

start_db_service() {
  mysqladmin ping >/dev/null 2>&1 && return 0          # responde 0 com o servidor no ar (mesmo sem permissão)
  if [[ ${EUID} -ne 0 ]] && have sudo; then setup_sudo "ligar o MySQL/MariaDB" soft || true; fi
  ${SUDO} service mariadb start >/dev/null 2>&1 || ${SUDO} service mysql start >/dev/null 2>&1 \
    || ${SUDO} systemctl start mariadb >/dev/null 2>&1 || ${SUDO} systemctl start mysql >/dev/null 2>&1 \
    || { have brew && brew services start mariadb >/dev/null 2>&1; } || true
  local i; for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do
    mysqladmin ping >/dev/null 2>&1 && return 0
    sleep 1
  done
  return 1
}

DB_ADMIN=()
db_admin_ok() {   # db_admin_ok comando... → conectou E tem cara de administrador (lê mysql.user)
  "$@" -N -e 'SELECT COUNT(*) FROM mysql.user' >/dev/null 2>&1
}
db_admin_detect() {   # descobre como falar com o MySQL como administrador, do jeito mais simples que funcionar
  [[ ${#DB_ADMIN[@]} -gt 0 ]] && return 0
  # 1) root sem senha, ou já somos root no Linux (o root do MariaDB entra pelo socket): XAMPP, contêineres, sudo bash
  if db_admin_ok mysql -uroot; then DB_ADMIN=(mysql -uroot); return 0; fi
  # 2) macOS/Homebrew: o usuário que instalou o MariaDB é administrador pelo socket
  if [[ ${IS_MAC} -eq 1 ]] && db_admin_ok mysql; then DB_ADMIN=(mysql); return 0; fi
  # 3) Ubuntu/Debian: o root do MariaDB/MySQL não tem senha própria; entra pelo socket com sudo
  if [[ ${EUID} -ne 0 ]] && have sudo; then
    if [[ -n "${SUDO}" ]] || sudo -n true 2>/dev/null || has_tty; then
      if setup_sudo "criar o banco de dados (o administrador do MySQL entra pelo sudo)" soft \
         && db_admin_ok sudo mysql; then DB_ADMIN=(sudo mysql); return 0; fi
    fi
  fi
  # 4) MySQL do Debian/Ubuntu: conta de manutenção
  local cnf=/etc/mysql/debian.cnf
  if ${SUDO} test -r "${cnf}" 2>/dev/null && db_admin_ok ${SUDO} mysql --defaults-file="${cnf}"; then
    DB_ADMIN=(${SUDO} mysql --defaults-file="${cnf}"); return 0
  fi
  # 5) senha do root do MySQL (quem definiu uma senha na instalação)
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    info "Não consegui entrar no MySQL como administrador automaticamente."
    local p tries
    for tries in 1 2 3; do
      ask_secret p "Senha do usuário root do MySQL (ENTER para desistir)" ""
      [[ -n "${p}" ]] || break
      if MYSQL_PWD="${p}" db_admin_ok mysql -uroot; then export MYSQL_PWD="${p}"; DB_ADMIN=(mysql -uroot); return 0; fi
      warn "Senha incorreta (tentativa ${tries} de 3)."
    done
  fi
  return 1
}

db_admin_help() {   # explica como resolver quando nenhuma forma de entrar como administrador funcionou
  echo
  box "Não consegui criar o banco de dados sozinho" \
    "O MySQL/MariaDB está ligado, mas não consegui entrar como administrador" \
    "(nem pelo sudo, nem como root). Crie o banco e um usuário para o site:" \
    "" \
    "  sudo mysql" \
    "  CREATE DATABASE ${1}" \
    "    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" \
    "  CREATE USER '${2}'@'localhost' IDENTIFIED BY 'UmaSenhaForte123';" \
    "  GRANT ALL PRIVILEGES ON ${1}.* TO '${2}'@'localhost';" \
    "  exit" \
    "" \
    "Depois rode de novo informando esse usuário:" \
    "  bash instalar.sh --local --db-name ${1} \\" \
    "      --db-user ${2} --db-pass UmaSenhaForte123"
}

provision_db() {   # provision_db <banco> <usuario>  → define DB_NAME/DB_USER/DB_PASS
  DB_NAME="$1"; DB_USER="$2"; DB_PASS="$(random_secret 28)"; DB_HOST="127.0.0.1"; DB_CREATED=1
  start_db_service || die "Não consegui ligar o MySQL/MariaDB. Veja o motivo com:  sudo systemctl status mariadb"
  db_admin_detect || { db_admin_help "${DB_NAME}" "${DB_USER}"; die "Não consegui acessar o MySQL como administrador."; }
  run "Criando o banco de dados '${DB_NAME}' e o usuário '${DB_USER}'" "${DB_ADMIN[@]}" -e "
    CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
    ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
    ALTER USER '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
    GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
    FLUSH PRIVILEGES;"
}

# =============================================================================
#  Aplicação
# =============================================================================
APP_USER_CMD=()
artisan_install() {
  [[ -n "${ADMIN_PASS}" ]] || ADMIN_PASS="$(random_secret 16)"
  ( cd "${APP_DIR}"
    run "Criando tabelas, jogos iniciais e o administrador" ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan system:install --no-interaction --overwrite \
      --url="${APP_URL}" --name="${SITE_NAME}" \
      --db-host="${DB_HOST}" --db-port="${DB_PORT}" --db-name="${DB_NAME}" --db-user="${DB_USER}" --db-pass="${DB_PASS}" \
      --admin-email="${ADMIN_EMAIL}" --admin-pass="${ADMIN_PASS}" )
  verify_admin
}

verify_admin() {   # confere o login do administrador pelo mesmo caminho do formulário; repara se preciso
  local args=(--email="${ADMIN_EMAIL}" --password="${ADMIN_PASS}")
  if ( cd "${APP_DIR}" && ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan system:admin "${args[@]}" --check >>"${LOG}" 2>&1 ); then
    ok "Login do administrador conferido (${ADMIN_EMAIL})"; return 0
  fi
  warn "O login do administrador não conferiu; recriando o usuário..."
  if ( cd "${APP_DIR}" && ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan system:admin "${args[@]}" >>"${LOG}" 2>&1 ); then
    ok "Administrador recriado e conferido (${ADMIN_EMAIL})"
  else
    tail -n 8 "${LOG}" | sed "s/^/    /"
    die "Não consegui garantir o login do administrador. Veja ${LOG}"
  fi
}

set_env_value() {
  local f="${APP_DIR}/.env"
  [[ -f "${f}" ]] || return 0
  if grep -q "^$1=" "${f}"; then sed -i.bak "s#^$1=.*#$1=$2#" "${f}" && rm -f "${f}.bak"; else echo "$1=$2" >> "${f}"; fi
}

port_in_use() { (echo >"/dev/tcp/127.0.0.1/$1") >/dev/null 2>&1; }
free_port() { local p="$1"; while port_in_use "${p}"; do p=$((p + 1)); done; echo "${p}"; }

existing_install_check() {   # retorna 1 se o usuário escolheu atualizar
  [[ -f "${APP_DIR}/storage/installed.lock" ]] || return 0
  echo
  warn "Já existe uma instalação em ${APP_DIR}."
  if [[ ${REINSTALL} -eq 1 ]]; then warn "Reinstalando (--reinstall): os dados atuais serão APAGADOS."; return 0; fi
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    local c; choose c "O que fazer?" "Atualizar (mantém todos os dados)" "Reinstalar (APAGA usuários, saldos e histórico)" "Cancelar"
    case "${c}" in
      2) confirm "Tem certeza? Isso APAGA tudo." n || die "Cancelado."; REINSTALL=1 ;;
      3) die "Cancelado." ;;
      *) MODE="update"; return 1 ;;
    esac
  else
    die "Já instalado. Use --update para atualizar ou --reinstall para apagar e instalar de novo."
  fi
  return 0
}

# =============================================================================
#  MODO LOCAL (seu computador)
# =============================================================================
lan_ip() { hostname -I 2>/dev/null | awk '{print $1}'; }
local_url() { if [[ ${LAN} -eq 1 && -n "$(lan_ip)" ]]; then echo "http://$(lan_ip):${HTTP_PORT}"; else echo "http://127.0.0.1:${HTTP_PORT}"; fi; }
local_pidfile() { echo "${APP_DIR}/storage/local-server.pid"; }
local_kill_orphans() {   # servidores "php -S" desta pasta que ficaram órfãos (ex.: pidfile apagado numa reinstalação)
  pkill -f "^php -S .*${APP_DIR}/vendor" 2>/dev/null || true
}
local_running() { local pf; pf="$(local_pidfile)"; [[ -f "${pf}" ]] && kill -0 "$(cat "${pf}")" 2>/dev/null; }

health_check() {   # health_check URL [log] [host:porta:ip] → confirma que o site responde (HTTP 2xx/3xx)
  local url="$1" logf="${2:-}" resolve="${3:-}" code="" i
  local ra=(); [[ -n "${resolve}" ]] && ra=(--resolve "${resolve}")
  for i in 1 2 3 4 5 6; do
    code="$(curl -s -m 15 "${ra[@]}" -o /dev/null -w '%{http_code}' "${url}" 2>/dev/null || true)"
    [[ "${code}" =~ ^[23][0-9][0-9]$ ]] && { ok "O site respondeu corretamente (HTTP ${code})"; return 0; }
    sleep 1
  done
  warn "O site respondeu HTTP ${code:-sem resposta} em ${url}"
  if [[ -n "${logf}" && -f "${logf}" ]]; then echo -e "${c_dim}$(grep -iE 'error|exception|warning' "${logf}" | tail -4 | cut -c1-200)${c_off}"; fi
  local la="${APP_DIR}/storage/logs/laravel.log"
  [[ -f "${la}" ]] && echo -e "${c_dim}$(grep -E '^\[20' "${la}" | tail -2 | cut -c1-220 || true)${c_off}"
  return 1
}

local_start() {
  [[ -f "${APP_DIR}/artisan" ]] || die "Não encontrei a instalação em ${APP_DIR} (use --dir)."
  [[ -f "${APP_DIR}/.env" ]] || die "Instalação incompleta (sem .env). Rode a instalação primeiro."
  if local_running; then ok "O site já está ligado (PID $(cat "$(local_pidfile)"))"; return 0; fi
  [[ -n "${HTTP_PORT}" ]] || HTTP_PORT="$(grep -E '^APP_URL=' "${APP_DIR}/.env" | sed -E 's#.*:([0-9]+)/?$#\1#' || true)"
  [[ "${HTTP_PORT}" =~ ^[0-9]+$ ]] || HTTP_PORT=8000
  if port_in_use "${HTTP_PORT}"; then
    local np; np="$(free_port "${HTTP_PORT}")"; warn "A porta ${HTTP_PORT} está ocupada; usando ${np}."
    HTTP_PORT="${np}"; set_env_value APP_URL "http://127.0.0.1:${HTTP_PORT}"
  fi
  have php || die "PHP não encontrado."
  local router="${APP_DIR}/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
  [[ -f "${router}" ]] || die "Dependências não instaladas (pasta vendor)."
  local bind="127.0.0.1"; [[ ${LAN} -eq 1 ]] && bind="0.0.0.0"
  # o roteador do Laravel precisa rodar de dentro de public/ (igual ao "artisan serve")
  ( cd "${APP_DIR}/public" || exit 1
    export PHP_CLI_SERVER_WORKERS=4
    nohup php -S "${bind}:${HTTP_PORT}" "${router}" >"${APP_DIR}/storage/logs/local-server.log" 2>&1 </dev/null &
    echo $! > "$(local_pidfile)" )
  local i; for i in $(seq 1 20); do port_in_use "${HTTP_PORT}" && break; sleep 0.5; done
  port_in_use "${HTTP_PORT}" || die "O servidor não subiu. Veja ${APP_DIR}/storage/logs/local-server.log"
  ok "Servidor ligado na porta ${HTTP_PORT}"
  health_check "http://127.0.0.1:${HTTP_PORT}/" "${APP_DIR}/storage/logs/local-server.log" || die "O servidor subiu, mas o site não responde direito. Rode:  bash scripts/auto-install.sh --diagnose"
  return 0
}

local_stop() {
  if local_running; then
    local pid; pid="$(cat "$(local_pidfile)")"
    pkill -P "${pid}" 2>/dev/null || true      # processos filhos (workers)
    kill "${pid}" 2>/dev/null || true
    rm -f "$(local_pidfile)"; local_kill_orphans; ok "Site desligado."
  else
    if pgrep -f "^php -S .*${APP_DIR}/vendor" >/dev/null 2>&1; then local_kill_orphans; ok "Site desligado (servidor sem registro encerrado)."
    else warn "O site local não está ligado."; fi
    rm -f "$(local_pidfile)" 2>/dev/null || true
  fi
  return 0
}

local_status() {
  if local_running; then ok "Ligado (PID $(cat "$(local_pidfile)")) — $(grep -E '^APP_URL=' "${APP_DIR}/.env" | cut -d= -f2-)"
  else warn "Desligado. Para ligar:  bash scripts/auto-install.sh --start"; fi
}

install_missing() {
  setup_sudo
  apt_update
  system_upgrade
  [[ ${NEED_TOOLS} -eq 1 ]] && apt_install "Instalando git, curl, unzip e rsync" git curl unzip rsync ca-certificates openssl
  [[ ${NEED_PHP} -eq 1 ]] && install_php
  [[ ${NEED_DB} -eq 1 ]] && apt_install "Instalando o MariaDB" mariadb-server
  return 0
}

do_local_install() {
  if [[ ${STACK} -eq 1 ]]; then   # "local com stack" = servidor Nginx em localhost (precisa de root)
    TARGET="server"; DOMAIN="${DOMAIN:-localhost}"; ADMIN_EMAIL="${ADMIN_EMAIL:-admin@casino.local}"; ADMIN_PASS="${ADMIN_PASS:-admin12345}"
    do_server_install; return
  fi
  if [[ ${EUID} -eq 0 && -n "${SUDO_USER:-}" && "${SUDO_USER}" != "root" ]]; then
    die "Não rode o modo local com sudo (os arquivos ficariam do root). Use:  bash instalar.sh  — ele pede a senha de administrador só quando precisa."
  fi
  STEPS=5; [[ ${NO_SERVE} -eq 1 ]] && STEPS=4
  [[ -n "${APP_DIR}" ]] || APP_DIR="${CHECKOUT:-${HOME}/casino}"
  CRED_FILE="${HOME}/casino-credenciais-local.txt"; APP_USER_CMD=()

  step "Diagnóstico do ambiente"
  detect_env; preflight local
  [[ -n "${SITE_NAME}" ]] || ask SITE_NAME "Nome do site" "Meu Cassino"
  [[ -n "${ADMIN_EMAIL}" ]] || ask ADMIN_EMAIL "E-mail do administrador (login do painel)" "admin@casino.local"
  [[ -n "${ADMIN_PASS}" ]] || ask_secret ADMIN_PASS "Senha do administrador (mín. 8 caracteres)" "admin12345"
  [[ -n "${SITE_NAME}" ]] || SITE_NAME="Meu Cassino"; [[ -n "${ADMIN_EMAIL}" ]] || ADMIN_EMAIL="admin@casino.local"; [[ -n "${ADMIN_PASS}" ]] || ADMIN_PASS="admin12345"
  [[ "${ADMIN_EMAIL}" == *@*.* ]] || die "E-mail inválido: ${ADMIN_EMAIL}"
  [[ ${#ADMIN_PASS} -ge 8 ]] || die "A senha precisa ter pelo menos 8 caracteres."
  if [[ -z "${HTTP_PORT}" ]]; then HTTP_PORT="$(free_port 8000)"; ask HTTP_PORT "Porta do site" "${HTTP_PORT}"; fi
  [[ "${HTTP_PORT}" =~ ^[0-9]+$ ]] || die "Porta inválida: ${HTTP_PORT}"

  step "Instalando o que falta"
  show_plan
  if [[ ${NEED_TOOLS} -eq 1 || ${NEED_PHP} -eq 1 || ${NEED_DB} -eq 1 ]]; then install_missing; else system_upgrade_if_asked; fi
  ensure_composer

  step "Obtendo o código"
  resolve_source
  [[ -n "${APP_DIR}" ]] || APP_DIR="${SRC}"
  existing_install_check || { do_update; return; }
  local_kill_orphans
  deploy_code

  step "Instalando o site"
  composer_install
  if [[ -n "${DB_USER}" ]]; then
    DB_HOST="${DB_HOST:-127.0.0.1}"; DB_NAME="${DB_NAME:-casino_local}"
    ok "Usando o MySQL informado (${DB_USER}@${DB_HOST}); o banco '${DB_NAME}' é criado se não existir"
  else provision_db "${DB_NAME:-casino_local}" "casino_local"; fi
  APP_URL="$(local_url)"
  artisan_install
  set_env_value APP_ENV local; set_env_value APP_DEBUG true
  ( cd "${APP_DIR}" && php artisan optimize:clear >>"${LOG}" 2>&1 || true )

  if [[ ${NO_SERVE} -eq 0 ]]; then step "Ligando o site"; local_start; APP_URL="$(local_url)"; fi

  umask 077
  cat > "${CRED_FILE}" <<EOF
Casino Platform — instalação local ($(date))

Site:          ${APP_URL}
Painel admin:  ${APP_URL}/admin
Login:         ${ADMIN_EMAIL}
Senha:         ${ADMIN_PASS}

Banco:         ${DB_NAME}  (usuário ${DB_USER}, senha ${DB_PASS}, host ${DB_HOST})
Pasta:         ${APP_DIR}
EOF
  registry_write
  local tilde='~' rel="${APP_DIR}/scripts/"; rel="${rel/#${HOME}/${tilde}}"
  echo
  box "Instalação local concluída em $(fmt_time $((SECONDS - T0)))" \
    "" \
    "  Site       ${c_accent}${APP_URL}${c_off}" \
    "  Painel     ${c_accent}${APP_URL}/admin${c_off}" \
    "  Login      ${ADMIN_EMAIL}" \
    "  Senha      ${ADMIN_PASS}" \
    "" \
    "  ${c_dim}Dados salvos em ${CRED_FILE}${c_off}" \
    "  ${c_dim}Desligar:  bash ${rel}auto-install.sh --stop${c_off}" \
    "  ${c_dim}Ligar:     bash ${rel}auto-install.sh --start${c_off}" \
    "  ${c_dim}Atualizar: bash ${rel}auto-install.sh --update${c_off}" \
    "  ${c_dim}Backup:    bash ${rel}auto-install.sh --backup${c_off}" \
    "  ${c_dim}Remover:   bash ${rel}auto-install.sh --uninstall${c_off}"
  echo
  info "Próximo passo: leia docs/MANUAL-DE-USO.md (gateways de pagamento, jogos e rodapé)."
  [[ ${IS_WSL} -eq 1 ]] && info "WSL: abra o endereço acima no navegador do Windows."
  return 0
}

system_upgrade_if_asked() { if [[ ${SYS_UPGRADE} -eq 1 && ${IS_APT} -eq 1 ]]; then setup_sudo; apt_update; system_upgrade; fi; return 0; }

# =============================================================================
#  MODO SERVIDOR (VPS)
# =============================================================================
as_www() { if have sudo; then sudo -u www-data "$@"; else runuser -u www-data -- "$@"; fi; }
public_ip() { curl -fsS -m 6 https://api.ipify.org 2>/dev/null || curl -fsS -m 6 https://ifconfig.me 2>/dev/null || true; }

check_dns() {
  [[ "${DOMAIN}" == "localhost" || "${DOMAIN}" =~ ^[0-9.]+$ ]] && return 0
  local ip dns
  ip="$(public_ip)"; dns="$(getent hosts "${DOMAIN}" 2>/dev/null | awk '{print $1; exit}' || true)"
  if [[ -z "${dns}" ]]; then
    row warn "DNS de ${DOMAIN}" "ainda não resolve — crie um registro A para ${ip:-o IP deste servidor}"
    [[ ${ENABLE_SSL} -eq 1 ]] && info "Sem DNS apontado o SSL falha agora (dá para emitir depois; o assistente mostra o comando)."
    confirm "Continuar mesmo assim?" s || die "Cancelado. Aponte o DNS e rode de novo."
  elif [[ -n "${ip}" && "${dns}" != "${ip}" ]]; then
    row warn "DNS de ${DOMAIN}" "aponta para ${dns}, mas este servidor é ${ip}"
    confirm "Continuar mesmo assim?" n || die "Cancelado. Ajuste o registro A do domínio."
  else row ok "DNS de ${DOMAIN}" "${dns} (este servidor)"; fi
}

check_port80() {
  have ss || return 0
  local owner
  owner="$(ss -ltnpH "sport = :${HTTP_PORT}" 2>/dev/null | grep -v nginx | grep -o 'users:(("[^"]*' | head -1 | cut -d'"' -f2 || true)"
  [[ -n "${owner}" ]] || return 0
  warn "A porta ${HTTP_PORT} está em uso por '${owner}'."
  if [[ "${owner}" == apache2 || "${owner}" == httpd ]] && confirm "Parar e desativar o ${owner} para usar o Nginx?" s; then
    svc stop "${owner}" >/dev/null 2>&1 || true; systemctl disable "${owner}" >/dev/null 2>&1 || true
    sleep 1; ok "${owner} desativado"; return 0
  fi
  local np; np="$(free_port 8080)"
  if [[ ${INTERACTIVE} -eq 1 ]] && confirm "Instalar na porta ${np} (sem SSL)?" s; then HTTP_PORT="${np}"; ENABLE_SSL=0
  else die "Pare esse serviço (systemctl disable --now ${owner}) ou use --port ${np}."; fi
}

server_url() {
  local scheme="http"; [[ ${ENABLE_SSL} -eq 1 ]] && scheme="https"
  if [[ "${HTTP_PORT}" != "80" && ${ENABLE_SSL} -eq 0 ]]; then echo "${scheme}://${DOMAIN}:${HTTP_PORT}"; else echo "${scheme}://${DOMAIN}"; fi
}

svc() {   # systemctl quando disponível; "service" em containers/VMs sem systemd
  local action="$1" name="$2"
  if command -v systemctl >/dev/null && [[ -d /run/systemd/system ]]; then systemctl "${action}" "${name}"; else service "${name}" "${action}" || true; fi
}

server_packages() {
  export DEBIAN_FRONTEND=noninteractive
  apt_update
  system_upgrade
  apt_install "Ferramentas básicas (git, curl, unzip, rsync, cron)" ca-certificates curl gnupg lsb-release unzip git rsync cron software-properties-common openssl
  apt_php_repo
  local pk=() p; for p in "${PHP_PKGS[@]}" fpm; do pk+=("php${PHP_VERSION}-${p}"); done
  apt_install "Instalando Nginx, MariaDB e PHP ${PHP_VERSION}" nginx mariadb-server "${pk[@]}"
  [[ ${ENABLE_SSL} -eq 1 ]] && apt_install "Instalando o Certbot (SSL)" certbot python3-certbot-nginx
  run "Iniciando os serviços" bash -c "$(declare -f svc); svc start mariadb || svc start mysql; svc start php${PHP_VERSION}-fpm"
}

fix_permissions() {
  chown -R www-data:www-data "${APP_DIR}"
  find "${APP_DIR}/storage" "${APP_DIR}/bootstrap/cache" -type d -exec chmod 775 {} +
  [[ -f "${APP_DIR}/.env" ]] && chmod 640 "${APP_DIR}/.env"
  return 0
}

configure_nginx() {
  local v; v="$(php_ver)"
  local sock="/run/php/php${v}-fpm.sock"
  local listen6="" names="${DOMAIN} www.${DOMAIN}"
  [[ -f /proc/net/if_inet6 ]] && listen6="listen [::]:${HTTP_PORT};"
  [[ "${DOMAIN}" == "localhost" || "${DOMAIN}" =~ ^[0-9.]+$ ]] && names="${DOMAIN} 127.0.0.1 _"
  cat > /etc/nginx/sites-available/casino <<NGINX
server {
    listen ${HTTP_PORT};
    ${listen6}
    server_name ${names};
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
        # arquivos gerados por rotas do Laravel (ex.: /livewire/livewire.min.js) caem no index.php
        try_files \$uri /index.php?\$query_string;
    }

    location ~ /\.(?!well-known) { deny all; }
}
NGINX
  ln -sf /etc/nginx/sites-available/casino /etc/nginx/sites-enabled/casino
  rm -f /etc/nginx/sites-enabled/default
  nginx -t -q 2>>"${LOG}" || { nginx -t 2>&1 | tail -5; die "A configuração do Nginx está inválida."; }
  svc restart nginx >>"${LOG}" 2>&1
  sleep 1
  pgrep -x nginx >/dev/null || die "O Nginx não iniciou. Veja: journalctl -u nginx  (ou /var/log/nginx/error.log)"
}

configure_ssl() {
  [[ ${ENABLE_SSL} -eq 1 ]] || return 0
  if certbot --nginx -d "${DOMAIN}" -m "${ADMIN_EMAIL}" --agree-tos --non-interactive --redirect >>"${LOG}" 2>&1; then ok "SSL ativo (https://${DOMAIN})"
  else
    warn "Não foi possível emitir o SSL agora. Confira se o DNS de ${DOMAIN} aponta para este servidor e rode depois:"
    info "certbot --nginx -d ${DOMAIN} -m ${ADMIN_EMAIL} --agree-tos --redirect  (depois troque APP_URL no .env para https)"
    ENABLE_SSL=0; APP_URL="$(server_url)"; set_env_value APP_URL "${APP_URL}"
    (cd "${APP_DIR}" && as_www php artisan config:clear >>"${LOG}" 2>&1) || true
  fi
}

# --- ajustes de produção -----------------------------------------------------
tune_php_fpm() {   # limites de upload (banners/logos), memória e opcache
  local v; v="$(php_ver)"
  local dir="/etc/php/${v}/fpm/conf.d"
  [[ -d "${dir}" ]] || return 0
  cat > "${dir}/99-casino.ini" <<INI
; Casino Platform — ajustes de produção
upload_max_filesize = 64M
post_max_size = 64M
memory_limit = 512M
max_execution_time = 300
expose_php = Off
opcache.enable = 1
opcache.memory_consumption = 192
opcache.max_accelerated_files = 20000
opcache.validate_timestamps = 1
opcache.revalidate_freq = 10
INI
  svc reload "php${v}-fpm" >>"${LOG}" 2>&1 || svc restart "php${v}-fpm" >>"${LOG}" 2>&1 || true
  ok "PHP ajustado para produção (uploads de 64 MB, 512 MB de memória, opcache)"
}

ensure_swap() {   # servidor com pouca memória: cria 2 GB de swap (o Composer precisa)
  have free || return 0
  local mb swap; mb="$(free -m | awk '/Mem:/{print $2}')"; swap="$(free -m | awk '/Swap:/{print $2}')"
  [[ -n "${mb}" && "${mb}" -lt 1600 && "${swap:-0}" -lt 512 ]] || return 0
  [[ -d /run/systemd/system || -f /proc/swaps ]] || return 0
  warn "Pouca memória (${mb} MB) e sem swap: o Composer pode falhar."
  confirm "Criar 2 GB de swap em /swapfile?" s || return 0
  if [[ ! -f /swapfile ]]; then
    run "Criando o swap de 2 GB" bash -c "fallocate -l 2G /swapfile || dd if=/dev/zero of=/swapfile bs=1M count=2048; chmod 600 /swapfile; mkswap /swapfile; swapon /swapfile; grep -q '^/swapfile' /etc/fstab || echo '/swapfile none swap sw 0 0' >> /etc/fstab"
    SWAP_CREATED="/swapfile"
  else
    swapon /swapfile >>"${LOG}" 2>&1 || true
  fi
}

configure_firewall() {   # se o ufw estiver ativo, libera SSH, HTTP e HTTPS
  have ufw || return 0
  ufw status 2>/dev/null | grep -q "Status: active" || return 0
  ufw allow OpenSSH >>"${LOG}" 2>&1 || ufw allow 22/tcp >>"${LOG}" 2>&1 || true
  ufw allow 80/tcp >>"${LOG}" 2>&1 || true
  ufw allow 443/tcp >>"${LOG}" 2>&1 || true
  [[ "${HTTP_PORT}" != "80" ]] && { ufw allow "${HTTP_PORT}/tcp" >>"${LOG}" 2>&1 || true; }
  ok "Firewall (ufw): portas 22, 80 e 443 liberadas"
  return 0
}

configure_logrotate() {
  cat > /etc/logrotate.d/casino <<ROT
${APP_DIR}/storage/logs/*.log {
    daily
    rotate 14
    compress
    missingok
    notifempty
    copytruncate
}
ROT
}

configure_cron() {
  local line="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
  (crontab -u www-data -l 2>/dev/null | grep -Fv "artisan schedule:run" || true; echo "${line}") | crontab -u www-data -
  svc start cron >>"${LOG}" 2>&1 || true
}

do_server_install() {
  require_root
  STEPS=7; CRED_FILE="/root/casino-credenciais.txt"; APP_DIR="${APP_DIR:-/var/www/casino}"
  APP_USER_CMD=(as_www); [[ -n "${HTTP_PORT}" ]] || HTTP_PORT=80
  TARGET="server"

  step "Diagnóstico do servidor"
  detect_env
  [[ ${IS_APT} -eq 1 ]] || die "${OS_NAME} não é suportado para instalação automática em servidor (use Ubuntu 22.04/24.04 ou Debian 12)."
  preflight server
  if [[ -z "${DOMAIN}" ]]; then ask DOMAIN "Domínio do site (ex.: meusite.com)" ""; fi
  DOMAIN="${DOMAIN#http://}"; DOMAIN="${DOMAIN#https://}"; DOMAIN="${DOMAIN%%/*}"
  [[ "${DOMAIN}" == "localhost" || ( "${DOMAIN}" =~ ^[A-Za-z0-9.-]+$ && "${DOMAIN}" == *.* ) ]] || die "Domínio inválido: '${DOMAIN}'. Exemplo: meusite.com"
  [[ -n "${ADMIN_EMAIL}" ]] || ask ADMIN_EMAIL "E-mail do administrador (login do painel e aviso do SSL)" ""
  [[ "${ADMIN_EMAIL}" == *@*.* ]] || die "E-mail inválido: '${ADMIN_EMAIL}'."
  [[ -n "${SITE_NAME}" ]] || ask SITE_NAME "Nome do site" "Meu Cassino"
  [[ -n "${SITE_NAME}" ]] || SITE_NAME="Meu Cassino"
  if [[ ${ENABLE_SSL} -eq 0 && "${DOMAIN}" != "localhost" && ! "${DOMAIN}" =~ ^[0-9.]+$ && "${HTTP_PORT}" == "80" ]]; then
    confirm "Ativar HTTPS gratuito (Let's Encrypt)?" s && ENABLE_SSL=1
  fi
  if [[ -z "${ADMIN_PASS}" ]]; then ask_secret ADMIN_PASS "Senha do administrador (ENTER gera uma forte)" ""; [[ -n "${ADMIN_PASS}" ]] || ADMIN_PASS="$(random_secret 16)"; fi
  [[ ${#ADMIN_PASS} -ge 8 ]] || die "A senha precisa ter pelo menos 8 caracteres."
  check_port80; check_dns
  APP_URL="$(server_url)"
  existing_install_check || { do_update; return; }
  legal_notice

  local php_label; if php_ok; then php_label="PHP $(php_ver)"; else php_label="$(php_plan_label)"; fi
  step "Instalando Nginx, ${php_label} e MariaDB"
  ensure_swap
  server_packages
  tune_php_fpm
  ensure_composer

  step "Obtendo o código"
  resolve_source
  deploy_code

  step "Instalando as dependências"
  composer_install; fix_permissions

  step "Banco de dados"
  provision_db "casino" "casino"; DB_HOST="localhost"

  step "Instalando o site"
  fix_permissions; artisan_install; fix_permissions

  step "Servidor web, SSL e tarefas agendadas"
  configure_nginx; ok "Nginx configurado (${DOMAIN} → ${APP_DIR}/public)"
  configure_firewall; configure_logrotate
  configure_ssl; configure_cron; ok "Agendador (cron) e rotação de logs configurados"
  # testa direto neste servidor (funciona mesmo antes do DNS propagar; 301 para https conta como ok)
  health_check "http://${DOMAIN}:${HTTP_PORT}/" "" "${DOMAIN}:${HTTP_PORT}:127.0.0.1" || warn "O site não respondeu no teste final; confira  journalctl -u nginx  e ${APP_DIR}/storage/logs/laravel.log"

  umask 077
  cat > "${CRED_FILE}" <<EOF
Casino Platform — dados da instalação ($(date))

Site:          ${APP_URL}
Painel admin:  ${APP_URL}/admin
Login:         ${ADMIN_EMAIL}
Senha:         ${ADMIN_PASS}

Banco MySQL:   ${DB_NAME}
Usuário:       ${DB_USER}
Senha:         ${DB_PASS}

Pasta:         ${APP_DIR}
Logs:          ${APP_DIR}/storage/logs
EOF
  registry_write
  echo
  box "Instalação concluída em $(fmt_time $((SECONDS - T0)))" \
    "" \
    "  Site       ${c_accent}${APP_URL}${c_off}" \
    "  Painel     ${c_accent}${APP_URL}/admin${c_off}" \
    "  Login      ${ADMIN_EMAIL}" \
    "  Senha      ${ADMIN_PASS}" \
    "" \
    "  ${c_dim}Dados salvos em ${CRED_FILE} (só o root lê)${c_off}" \
    "  ${c_dim}Atualizar:  sudo bash ${SRC}/instalar.sh --update${c_off}" \
    "  ${c_dim}Backup:     sudo bash ${SRC}/instalar.sh --backup${c_off}" \
    "  ${c_dim}Remover:    sudo bash ${SRC}/instalar.sh --uninstall${c_off}"
  echo
  info "Próximo passo: configure os gateways em ${APP_URL}/admin (docs/PAGAMENTOS.md)."
}

# =============================================================================
#  Atualizar
# =============================================================================
update_target() {   # qual instalação manter: a pasta informada (--dir) > a cópia de onde o assistente roda > o servidor
  [[ -n "${TARGET}" ]] && return 0
  if [[ -z "${APP_DIR}" && -n "${CHECKOUT}" && -f "${CHECKOUT}/.env" ]]; then APP_DIR="${CHECKOUT}"; fi
  if [[ -n "${APP_DIR}" && -f "${APP_DIR}/.env" ]]; then
    if [[ -n "$(server_site_for "${APP_DIR}")" ]]; then TARGET="server"; else TARGET="local"; fi
    return 0
  fi
  if [[ -n "${APP_DIR}" ]]; then die "Não há instalação em ${APP_DIR} (sem .env)."; fi
  if [[ -f "${HOME}/casino/.env" ]]; then   # pasta padrão da instalação no computador (assistente via curl | bash)
    APP_DIR="${HOME}/casino"
    if [[ -n "$(server_site_for "${APP_DIR}")" ]]; then TARGET="server"; else TARGET="local"; fi
  elif [[ -f /var/www/casino/.env ]]; then TARGET="server"; APP_DIR="/var/www/casino"
  else die "Não encontrei uma instalação. Use --dir para informar a pasta (ou --local / --domain para instalar)."; fi
}

do_update() {
  update_target
  [[ "${TARGET}" == "server" ]] && require_root
  STEPS=5; STEP=0
  if [[ "${TARGET}" == "server" ]]; then APP_DIR="${APP_DIR:-/var/www/casino}"; APP_USER_CMD=(as_www); else APP_DIR="${APP_DIR:-${CHECKOUT}}"; APP_USER_CMD=(); fi
  [[ -f "${APP_DIR}/.env" ]] || die "Instalação não encontrada em ${APP_DIR}"
  detect_env
  local from; from="$( { grep -o '"version": *"[^"]*"' "${APP_DIR}/storage/installed.lock" 2>/dev/null || true; } | head -1 | sed 's/.*"\([^"]*\)"$/\1/')"

  step "Backup antes de atualizar (nada é apagado)"
  if [[ ${NO_BACKUP} -eq 1 ]]; then warn "Backup pulado (--no-backup)."; else make_backup "${APP_DIR}"; fi
  [[ -n "${LAST_BACKUP}" ]] && info "Backup: ${LAST_BACKUP}"

  step "Obtendo a versão nova do código"
  refresh_checkout
  if [[ "${TARGET}" == "server" ]]; then resolve_source
  elif [[ -n "${SOURCE}" || -z "${CHECKOUT}" || "${CHECKOUT}" != "${APP_DIR}" ]]; then resolve_source
  else SRC="${CHECKOUT}"; ok "Usando o código desta pasta"; fi
  if [[ "${TARGET}" == "server" ]]; then setup_sudo; apt_update; system_upgrade
  else system_upgrade_if_asked; fi

  step "Atualizando arquivos e dependências (site em manutenção)"
  ( cd "${APP_DIR}" && ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan down --retry=30 >>"${LOG}" 2>&1 || true )
  ok "Site em manutenção durante a atualização"
  trap '( cd "${APP_DIR}" && ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan up >/dev/null 2>&1 ); cleanup; echo -e "\n${c_red}${I_FAIL} A atualização falhou (linha ${LINENO}). O site foi reativado e nenhum dado foi apagado; veja ${LOG}${c_off}" >&2; [[ -n "${LAST_BACKUP}" ]] && echo "  Para voltar ao estado anterior: bash instalar.sh --restore \"${LAST_BACKUP}\"" >&2' ERR
  deploy_code; ensure_composer; composer_install
  [[ "${TARGET}" == "server" ]] && fix_permissions

  step "Atualizando o banco de dados"
  ( cd "${APP_DIR}"
    if artisan_has system:update "${APP_DIR}"; then
      run "Migrações, arquivos padrão, caches e versão" ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan system:update --force --no-backup
    else
      run "Rodando as migrações" ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan migrate --force
      run "Limpando o cache" ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan optimize:clear
    fi )
  if [[ "${TARGET}" == "server" && -f /etc/nginx/sites-available/casino ]] && grep -q 'try_files \$uri =404;' /etc/nginx/sites-available/casino; then
    sed -i 's#try_files \$uri =404;#try_files $uri /index.php?$query_string;#' /etc/nginx/sites-available/casino
    nginx -t -q && svc reload nginx || true
  fi

  step "Reativando o site"
  ( cd "${APP_DIR}" && ${APP_USER_CMD[@]+"${APP_USER_CMD[@]}"} php artisan up >>"${LOG}" 2>&1 )
  trap 'cleanup; echo -e "\n${c_red}${I_FAIL} Erro inesperado na linha ${LINENO}: ${BASH_COMMAND}${c_off}\n  Detalhes em ${LOG}" >&2' ERR
  if [[ "${TARGET}" == "server" ]]; then svc reload "php$(php_ver)-fpm" >/dev/null 2>&1 || true
  elif local_running; then local_stop; local_start; fi
  local to; to="$( { grep -o '"version": *"[^"]*"' "${APP_DIR}/storage/installed.lock" 2>/dev/null || true; } | head -1 | sed 's/.*"\([^"]*\)"$/\1/')"
  echo
  local lines=("" "  Versão: ${from:-anterior} → ${c_accent}${to:-${SCRIPT_VERSION}}${c_off}" "  Banco migrado, caches limpos; dados, .env e arquivos enviados mantidos.")
  [[ -n "${LAST_BACKUP}" ]] && lines+=("  ${c_dim}Backup feito antes: ${LAST_BACKUP}${c_off}")
  lines+=("")
  box "Atualização concluída em $(fmt_time $((SECONDS - T0)))" "${lines[@]}"
}

# =============================================================================
#  Registro do que o assistente instalou (para desinstalar sem deixar rastros)
# =============================================================================
registry_dir() { if [[ ${EUID} -eq 0 ]]; then echo "/etc/casino-platform/installs"; else echo "${XDG_CONFIG_HOME:-${HOME}/.config}/casino-platform/installs"; fi; }
registry_files() {
  local f
  for f in /etc/casino-platform/installs/*.conf "${XDG_CONFIG_HOME:-${HOME}/.config}"/casino-platform/installs/*.conf; do
    if [[ -f "${f}" ]]; then echo "${f}"; fi
  done
  return 0
}
registry_id() { printf '%s' "$1" | md5sum | cut -c1-12; }
registry_get() { ( set +u; source "$1" >/dev/null 2>&1; printf '%s' "${!2:-}" ) || true; }
registry_file_for() {   # arquivo de registro de uma pasta (ou vazio)
  local f
  while IFS= read -r f; do
    if [[ "$(registry_get "${f}" DIR)" == "$1" ]]; then echo "${f}"; return 0; fi
  done < <(registry_files)
  return 0
}
merge_words() { echo "$@" | tr ' ' '\n' | awk 'NF && !seen[$0]++' | tr '\n' ' ' | sed 's/ $//'; }

registry_write() {
  local d f old_pkgs="" old_composer="" old_swap="" old_created=0
  d="$(registry_dir)"
  mkdir -p "${d}" 2>/dev/null || return 0
  f="${d}/$(registry_id "${APP_DIR}").conf"
  if [[ -f "${f}" ]]; then   # reinstalação: não perde o que foi instalado antes
    old_pkgs="$(registry_get "${f}" PKGS)"; old_composer="$(registry_get "${f}" COMPOSER_LOCAL)"
    old_swap="$(registry_get "${f}" SWAPFILE)"; old_created="$(registry_get "${f}" DB_CREATED)"
  fi
  {
    printf 'DIR=%q\n' "${APP_DIR}"
    printf 'TYPE=%q\n' "${TARGET}"
    printf 'DB_NAME=%q\n' "${DB_NAME}"
    printf 'DB_USER=%q\n' "${DB_USER}"
    printf 'DB_CREATED=%q\n' "$(( DB_CREATED || ${old_created:-0} ))"
    printf 'DOMAIN=%q\n' "${DOMAIN}"
    printf 'PORT=%q\n' "${HTTP_PORT}"
    printf 'SRC_DIR=%q\n' "$(if [[ "${TARGET}" == "server" && "${SRC}" == "${SRC_DIR}" ]]; then echo "${SRC_DIR}"; fi)"
    printf 'CRED_FILE=%q\n' "${CRED_FILE}"
    printf 'PKGS=%q\n' "$(merge_words ${old_pkgs} ${INSTALLED_PKGS})"
    printf 'COMPOSER_LOCAL=%q\n' "${COMPOSER_DOWNLOADED:-${old_composer}}"
    printf 'SWAPFILE=%q\n' "${SWAP_CREATED:-${old_swap}}"
    printf 'INSTALLED_AT=%q\n' "$(date -Iseconds)"
    printf 'VERSION=%q\n' "${SCRIPT_VERSION}"
  } > "${f}"
  chmod 600 "${f}" 2>/dev/null || true
  return 0
}

# =============================================================================
#  Backup e restauração
# =============================================================================
is_casino_dir() { [[ -f "$1/artisan" && -f "$1/app/Install/CasinoInstaller.php" ]]; }
env_value() {   # env_value PASTA CHAVE
  [[ -f "$1/.env" ]] || return 0
  { grep -E "^$2=" "$1/.env" 2>/dev/null || true; } | tail -1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'\$//"
}
artisan_has() { ( cd "$2" 2>/dev/null && php artisan list --raw 2>/dev/null | grep -q "^$1 " ); }
owned_by_www() { [[ ${EUID} -eq 0 && "$(stat -c %U "$1" 2>/dev/null)" == "www-data" ]]; }
app_cmd() { APP_CMD=(); if owned_by_www "$1"; then APP_CMD=(as_www); fi; return 0; }
backup_dir_for() { if owned_by_www "$1"; then echo "/var/backups/casino"; else echo "${BACKUP_DIR}"; fi; }

LAST_BACKUP=""
make_backup() {   # make_backup PASTA → backup completo; define LAST_BACKUP
  local dir="$1" out; out="$(backup_dir_for "${dir}")"
  app_cmd "${dir}"
  mkdir -p "${out}"
  if [[ ${#APP_CMD[@]} -gt 0 ]]; then chown www-data:www-data "${out}"; chmod 750 "${out}"; fi
  if artisan_has system:backup "${dir}"; then
    run "Backup do banco, do .env e dos arquivos enviados" ${APP_CMD[@]+"${APP_CMD[@]}"} php "${dir}/artisan" system:backup --output="${out}"
  else
    legacy_backup "${dir}" "${out}"
  fi
  LAST_BACKUP="$(ls -1t "${out}"/casino-backup-* 2>/dev/null | head -1 || true)"
  return 0
}

legacy_backup() {   # instalações anteriores à 9.4 (sem o comando system:backup): mysqldump + arquivos
  local dir="$1" out="$2" stamp tmp port; stamp="$(date +%Y%m%d-%H%M%S)"; tmp="$(mktemp -d)"
  port="$(env_value "${dir}" DB_PORT)"
  if have mysqldump; then
    MYSQL_PWD="$(env_value "${dir}" DB_PASSWORD)" mysqldump --single-transaction -h "$(env_value "${dir}" DB_HOST)" \
      -P "${port:-3306}" -u "$(env_value "${dir}" DB_USERNAME)" "$(env_value "${dir}" DB_DATABASE)" \
      > "${tmp}/database.sql" 2>>"${LOG}" && DUMPED_DBS+="$(env_value "${dir}" DB_DATABASE)|" \
      || warn "O mysqldump falhou (veja ${LOG}); o backup terá só os arquivos."
  fi
  local args=(-czf "${out}/casino-backup-${stamp}-antigo.tar.gz" -C "${tmp}" .) i first=1
  for i in .env public/storage storage/app; do
    if [[ -e "${dir}/${i}" ]]; then
      if [[ ${first} -eq 1 ]]; then args+=(-C "${dir}"); first=0; fi
      args+=("${i}")
    fi
  done
  tar "${args[@]}" 2>>"${LOG}" || true
  chmod 600 "${out}/casino-backup-${stamp}-antigo.tar.gz" 2>/dev/null || true   # contém o .env (senhas)
  rm -rf "${tmp}"
  ok "Backup (formato antigo: banco em SQL + arquivos) em ${out}"
}

do_backup() {
  update_target
  [[ "${TARGET}" == "server" ]] && require_root
  STEPS=1; step "Backup de ${APP_DIR}"
  make_backup "${APP_DIR}"
  echo
  box "Backup concluído" "" "  Arquivo   ${c_accent}${LAST_BACKUP}${c_off}" "" \
    "  ${c_dim}Restaurar:  bash instalar.sh --restore \"${LAST_BACKUP}\"${c_off}" \
    "  ${c_dim}Guarde uma cópia fora deste computador/servidor.${c_off}"
}

do_restore() {
  update_target
  [[ "${TARGET}" == "server" ]] && require_root
  artisan_has system:restore "${APP_DIR}" || die "Esta instalação é anterior à 9.4: atualize primeiro (bash instalar.sh --update)."
  STEPS=2; step "Escolhendo o backup"
  local file="${RESTORE_FILE}" out; out="$(backup_dir_for "${APP_DIR}")"
  if [[ -z "${file}" ]]; then
    local list=() f
    while IFS= read -r f; do list+=("${f}"); done < <(ls -1t "${out}"/casino-backup-*.zip "${APP_DIR}"/storage/app/backups/casino-backup-*.zip 2>/dev/null | head -8 || true)
    [[ ${#list[@]} -gt 0 ]] || die "Nenhum backup encontrado em ${out} nem em ${APP_DIR}/storage/app/backups. Informe o arquivo: --restore /caminho/backup.zip"
    local labels=() c
    for f in "${list[@]}"; do labels+=("$(basename "${f}")  ($(du -h "${f}" | cut -f1))"); done
    choose c "Qual backup restaurar? (o mais novo primeiro)" "${labels[@]}"
    file="${list[$((c - 1))]}"
  fi
  [[ -f "${file}" ]] || die "Backup não encontrado: ${file}"
  ok "Backup: ${file}"
  warn "Os dados atuais do banco serão substituídos pelos do backup (antes, um backup do estado atual é feito)."
  [[ ${ASSUME_YES} -eq 1 ]] || confirm "Restaurar agora?" n || die "Cancelado."
  step "Restaurando"
  app_cmd "${APP_DIR}"
  if [[ ${#APP_CMD[@]} -gt 0 ]]; then chmod 644 "${file}" 2>/dev/null || true; fi
  run "Restaurando banco e arquivos (e aplicando migrações novas)" ${APP_CMD[@]+"${APP_CMD[@]}"} php "${APP_DIR}/artisan" system:restore "${file}" --force
  if [[ "${TARGET}" == "local" ]] && local_running; then local_stop >/dev/null; local_start; fi
  echo
  box "Backup restaurado" "" "  ${file}" ""
}

# =============================================================================
#  Desinstalar (uma ou várias instalações, inclusive restos de instalações antigas)
# =============================================================================
has_install_traces() { [[ -f "$1/.env" || -f "$1/storage/installed.lock" || -d "$1/vendor" || -f "$1/storage/local-server.pid" ]]; }
UN_DIRS=()
add_candidate() {
  local d x; d="$(cd "$1" 2>/dev/null && pwd -P)" || return 0
  for x in ${UN_DIRS[@]+"${UN_DIRS[@]}"}; do [[ "${x}" == "${d}" ]] && return 0; done
  UN_DIRS+=("${d}")
}
find_installs() {
  UN_DIRS=()
  local f d
  if [[ -n "${APP_DIR}" ]]; then
    is_casino_dir "${APP_DIR}" || die "${APP_DIR} não é uma pasta do Casino Platform."
    add_candidate "${APP_DIR}"; return 0
  fi
  while IFS= read -r f; do d="$(registry_get "${f}" DIR)"; if [[ -n "${d}" ]] && is_casino_dir "${d}"; then add_candidate "${d}"; fi; done < <(registry_files)
  if [[ -n "${CHECKOUT}" ]] && is_casino_dir "${CHECKOUT}"; then add_candidate "${CHECKOUT}"; fi
  for d in /var/www/casino* /var/www/html/casino* /opt/casino-src; do if is_casino_dir "${d}"; then add_candidate "${d}"; fi; done
  # cópias antigas na pasta pessoal (inclusive uma dentro da outra: ~/casino/casino/casino)
  while IFS= read -r f; do
    d="${f%/app/Install/CasinoInstaller.php}"
    if is_casino_dir "${d}"; then add_candidate "${d}"; fi
  done < <(find "${HOME}" -maxdepth 7 -type f -name CasinoInstaller.php -path '*/app/Install/*' \
             -not -path '*/node_modules/*' -not -path '*/vendor/*' -not -path '*/.cache/*' -not -path '*/.local/share/Trash/*' 2>/dev/null || true)
  return 0
}
install_state() {   # instalado | parcial | só o código
  if [[ -f "$1/storage/installed.lock" ]]; then echo "instalado"
  elif has_install_traces "$1"; then echo "parcial"
  else echo "só o código"; fi
}
server_site_for() { if [[ -f /etc/nginx/sites-available/casino ]] && grep -q "root $1/public;" /etc/nginx/sites-available/casino 2>/dev/null; then echo /etc/nginx/sites-available/casino; fi; return 0; }
dir_running() { pgrep -f "^php -S .*$1/vendor" >/dev/null 2>&1 || { [[ -f "$1/storage/local-server.pid" ]] && kill -0 "$(cat "$1/storage/local-server.pid" 2>/dev/null)" 2>/dev/null; }; }
safe_to_delete() {   # nunca apaga /, a pasta pessoal ou pastas de sistema
  local d; d="$(cd "$1" 2>/dev/null && pwd -P)" || return 1
  case "${d}" in /|/home|/root|/var|/var/www|/var/www/html|/opt|/srv|/usr|/usr/*|/etc|/etc/*|/tmp|"${HOME}") return 1 ;; esac
  is_casino_dir "${d}"
}

db_exists() { "${DB_ADMIN[@]}" -N -e "SELECT 1 FROM information_schema.schemata WHERE schema_name='$1'" 2>/dev/null | grep -q 1; }
db_tables() {   # db_tables BANCO → "nome<TAB>tipo" de cada tabela/view do banco
  "${DB_ADMIN[@]}" -N -e "SELECT table_name, table_type FROM information_schema.tables WHERE table_schema='$1'" 2>/dev/null || true
}
app_tables() {   # app_tables PASTA → tabelas do cassino (base.sql + migrações da própria pasta), uma por linha, em minúsculas
  [[ -f "$1/database/install/base.sql" ]] && have php || return 0
  php -r '$d = $argv[1]; $n = ["migrations"];
    if (preg_match_all("/CREATE TABLE `([^`]+)`/i", (string) @file_get_contents("$d/database/install/base.sql"), $m)) { $n = array_merge($n, $m[1]); }
    foreach (glob("$d/database/migrations/*.php") ?: [] as $f) { if (preg_match_all("/Schema::create\(\s*[\x27\"]([^\x27\"]+)[\x27\"]/", (string) file_get_contents($f), $m)) { $n = array_merge($n, $m[1]); } }
    echo implode("\n", array_unique(array_map("strtolower", $n))), "\n";' "$1" 2>/dev/null || true
}
DUMPED_DBS="|"
db_dump() {   # db_dump BANCO → cópia do banco (mysqldump com a mesma conta administradora) em BACKUP_DIR
  local db="$1" cmd=() x swapped=0 file
  [[ "${DUMPED_DBS}" == *"|${db}|"* ]] && return 0
  have mysqldump || return 1
  for x in "${DB_ADMIN[@]}"; do
    if [[ ${swapped} -eq 0 && ( "${x}" == mysql || "${x}" == mariadb ) ]]; then cmd+=(mysqldump); swapped=1; else cmd+=("${x}"); fi
  done
  mkdir -p "${BACKUP_DIR}" 2>/dev/null || return 1
  file="${BACKUP_DIR}/casino-banco-${db}-$(date +%Y%m%d-%H%M%S).sql"
  if "${cmd[@]}" --single-transaction --routines "${db}" > "${file}" 2>>"${LOG}"; then
    chmod 600 "${file}" 2>/dev/null || true
    if have gzip && gzip -f "${file}" 2>>"${LOG}"; then file="${file}.gz"; fi
    DUMPED_DBS+="${db}|"; ok "Cópia do banco '${db}' salva em ${file}"; return 0
  fi
  rm -f "${file}"; return 1
}
db_user_elsewhere() {   # o usuário do MySQL tem acesso a outros bancos? (então não é apagado)
  local n; n="$("${DB_ADMIN[@]}" -N -e "SELECT COUNT(*) FROM mysql.db WHERE User='$1' AND REPLACE(Db,'\\\\_','_') <> '$2'" 2>/dev/null || echo 0)"
  [[ "${n}" =~ ^[0-9]+$ && "${n}" -gt 0 ]]
}
drop_db_leftovers() {   # drop_db_leftovers BANCO USUARIO [PASTA] [criado-pelo-assistente 0|1]
  # Apaga o banco do site e o usuário do MySQL. Tabelas são reconhecidas pelo código da pasta (base.sql + migrações):
  # se o banco tiver tabelas de outro sistema, só as do cassino saem e o banco fica.
  local db="$1" user="$2" dir="${3:-}" created="${4:-0}" line t type ours="" mine=() views=() foreign=()
  [[ "${db}" =~ ^[A-Za-z0-9_-]+$ ]] || return 0
  case "${db}" in mysql|information_schema|performance_schema|sys) return 0 ;; esac
  db_admin_detect || { warn "Sem acesso de administrador ao MySQL: o banco '${db}' e o usuário '${user}' ficaram (apague pelo painel do MySQL)."; return 0; }
  if db_exists "${db}"; then
    [[ -n "${dir}" ]] && ours="$(app_tables "${dir}")"
    while IFS=$'\t' read -r t type; do
      [[ -n "${t}" ]] || continue
      if { [[ -n "${ours}" ]] && grep -qxiF -- "${t}" <<<"${ours}"; } || { [[ -z "${ours}" && "${created}" == "1" ]]; }; then
        if [[ "${type}" == "VIEW" ]]; then views+=("${t}"); else mine+=("${t}"); fi
      else
        foreign+=("${t}")
      fi
    done < <(db_tables "${db}")
    if [[ $(( ${#mine[@]} + ${#views[@]} )) -gt 0 && ${NO_BACKUP} -eq 0 ]]; then
      db_dump "${db}" || warn "Não consegui copiar o banco '${db}' antes de apagar (veja ${LOG})."
    fi
    if [[ ${#foreign[@]} -eq 0 ]]; then
      "${DB_ADMIN[@]}" -e "DROP DATABASE IF EXISTS \`${db}\`" >>"${LOG}" 2>&1 && ok "Banco '${db}' apagado"
    else
      local sql="SET FOREIGN_KEY_CHECKS=0;"
      for t in ${views[@]+"${views[@]}"}; do sql+=" DROP VIEW IF EXISTS \`${db}\`.\`${t}\`;"; done
      for t in ${mine[@]+"${mine[@]}"}; do sql+=" DROP TABLE IF EXISTS \`${db}\`.\`${t}\`;"; done
      if [[ $(( ${#mine[@]} + ${#views[@]} )) -gt 0 ]]; then
        "${DB_ADMIN[@]}" -e "${sql}" >>"${LOG}" 2>&1 && ok "$(( ${#mine[@]} + ${#views[@]} )) tabela(s) do cassino apagadas do banco '${db}'"
      fi
      warn "O banco '${db}' tem tabelas de outro sistema (${foreign[*]:0:5}$([[ ${#foreign[@]} -gt 5 ]] && echo '...' || true)): ele foi mantido."
      return 0
    fi
  fi
  case "${user}" in ""|root|mysql|mariadb.sys|debian-sys-maint|admin|phpmyadmin) return 0 ;; esac
  [[ "${user}" =~ ^[A-Za-z0-9_.-]+$ ]] || return 0
  if db_user_elsewhere "${user}" "${db}"; then warn "O usuário '${user}' do MySQL tem acesso a outros bancos: ele foi mantido."; return 0; fi
  if "${DB_ADMIN[@]}" -N -e "SELECT COUNT(*) FROM mysql.user WHERE User='${user}'" 2>/dev/null | grep -qx 0; then return 0; fi
  "${DB_ADMIN[@]}" -e "DROP USER IF EXISTS '${user}'@'localhost'; DROP USER IF EXISTS '${user}'@'127.0.0.1'; DROP USER IF EXISTS '${user}'@'%'; FLUSH PRIVILEGES;" >>"${LOG}" 2>&1 \
    && ok "Usuário '${user}' do MySQL apagado"
  return 0
}

server_cleanup() {   # Nginx, SSL, agendador, logrotate, PHP-FPM e código-fonte de uma instalação de servidor
  local dir="$1" site domain
  site="$(server_site_for "${dir}")"
  if [[ -n "${site}" ]]; then
    domain="$(awk '/server_name/{print $2; exit}' "${site}" | tr -d ';')"
    rm -f /etc/nginx/sites-enabled/casino "${site}"
    # devolve o site padrão do Nginx, se não sobrar nenhum outro
    if [[ -f /etc/nginx/sites-available/default && -z "$(ls -A /etc/nginx/sites-enabled 2>/dev/null)" ]]; then
      ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
      # servidor sem IPv6: o site padrão (listen [::]:80) derrubaria o Nginx
      if have nginx && ! nginx -t >>"${LOG}" 2>&1; then rm -f /etc/nginx/sites-enabled/default; fi
    fi
    if have nginx && nginx -t >>"${LOG}" 2>&1; then svc reload nginx >>"${LOG}" 2>&1 || true; fi
    ok "Site do Nginx removido"
    if [[ -n "${domain}" && "${domain}" != "localhost" && -d "/etc/letsencrypt/live/${domain}" ]] && have certbot; then
      if confirm "Apagar também o certificado SSL de ${domain}?" s; then
        certbot delete --cert-name "${domain}" --non-interactive >>"${LOG}" 2>&1 && ok "Certificado SSL de ${domain} apagado" || warn "Não consegui apagar o certificado (certbot delete --cert-name ${domain})."
      fi
    fi
  fi
  if have crontab && crontab -u www-data -l >/dev/null 2>&1; then
    { crontab -u www-data -l 2>/dev/null | grep -Fv "cd ${dir} && php artisan schedule:run" || true; } | crontab -u www-data - && ok "Agendador (cron) removido"
  fi
  if [[ -f /etc/logrotate.d/casino ]] && grep -q "${dir}/storage/logs" /etc/logrotate.d/casino; then rm -f /etc/logrotate.d/casino; ok "Rotação de logs removida"; fi
  return 0
}

pick_installs() {   # mostra as instalações encontradas e define UN_SELECTED
  UN_SELECTED=()
  local i d state db extra
  echo
  for i in "${!UN_DIRS[@]}"; do
    d="${UN_DIRS[${i}]}"; state="$(install_state "${d}")"; db="$(env_value "${d}" DB_DATABASE)"; extra=""
    [[ -n "${db}" ]] && extra+=" · banco ${db}"
    dir_running "${d}" && extra+=" · ${c_green}ligado${c_off}"
    [[ -n "$(server_site_for "${d}")" ]] && extra+=" · servidor Nginx"
    [[ "${d}" == "${CHECKOUT}" ]] && extra+=" · ${c_dim}(esta pasta)${c_off}"
    printf "  %s%2d)%s %s  %s%s%s%s\n" "${c_bold}" $((i + 1)) "${c_off}" "${d}" "${c_dim}" "${state}" "${c_off}" "${extra}"
  done
  echo
  if [[ ${UN_ALL} -eq 1 || ${#UN_DIRS[@]} -eq 1 && -n "${APP_DIR}" ]]; then UN_SELECTED=("${UN_DIRS[@]}"); return 0; fi
  if [[ ${INTERACTIVE} -eq 0 ]]; then die "Diga o que remover: --dir PASTA (uma) ou --all (todas as listadas)."; fi
  local answer n
  ask answer "Quais remover? Números separados por espaço, T = todas, ENTER = cancelar" ""
  answer="$(echo "${answer}" | tr 'a-z,' 'A-Z ')"
  [[ -n "${answer// /}" ]] || die "Cancelado."
  if [[ "${answer}" == *T* ]]; then UN_SELECTED=("${UN_DIRS[@]}"); return 0; fi
  for n in ${answer}; do
    if [[ "${n}" =~ ^[0-9]+$ && ${n} -ge 1 && ${n} -le ${#UN_DIRS[@]} ]]; then UN_SELECTED+=("${UN_DIRS[$((n - 1))]}"); else die "Opção inválida: ${n}"; fi
  done
  return 0
}

uninstall_one() {   # uninstall_one PASTA
  local dir="$1" db host user reg is_server=0 delete_dir=0 out
  db="$(env_value "${dir}" DB_DATABASE)"; host="$(env_value "${dir}" DB_HOST)"; user="$(env_value "${dir}" DB_USERNAME)"
  reg="$(registry_file_for "${dir}")"
  if [[ -n "$(server_site_for "${dir}")" || "${dir}" == /var/www/* || "$(registry_get "${reg:-/dev/null}" TYPE)" == "server" ]]; then is_server=1; fi
  echo; echo -e "  ${c_bold}${dir}${c_off}"

  # 1. servidor local (php -S) desta pasta
  if dir_running "${dir}"; then
    local pf="${dir}/storage/local-server.pid"
    if [[ -f "${pf}" ]]; then pkill -P "$(cat "${pf}")" 2>/dev/null || true; kill "$(cat "${pf}")" 2>/dev/null || true; fi
    pkill -f "^php -S .*${dir}/vendor" 2>/dev/null || true
    ok "Servidor local desligado"
  fi
  if have docker && [[ -n "$(docker ps -aq --filter "label=com.docker.compose.project.working_dir=${dir}" 2>/dev/null)" ]]; then
    run "Removendo os contêineres Docker (e os volumes)" docker compose --project-directory "${dir}" down -v --remove-orphans
  fi

  # 2. backup final + tabelas, .env, uploads, logs e caches (pelo próprio sistema, quando possível)
  if [[ -f "${dir}/.env" && -f "${dir}/vendor/autoload.php" ]] && artisan_has system:uninstall "${dir}"; then
    app_cmd "${dir}"
    out="$(backup_dir_for "${dir}")"; mkdir -p "${out}"
    if [[ ${#APP_CMD[@]} -gt 0 ]]; then chown www-data:www-data "${out}"; chmod 750 "${out}"; fi
    local args=(--force)
    if [[ ${NO_BACKUP} -eq 1 ]]; then args+=(--no-backup); else args+=(--backup-dir="${out}"); fi
    if [[ ${KEEP_DB} -eq 1 ]]; then args+=(--keep-database); fi
    run "Backup final e limpeza do sistema (tabelas, .env, uploads, logs)" ${APP_CMD[@]+"${APP_CMD[@]}"} php "${dir}/artisan" system:uninstall "${args[@]}"
    if [[ ${NO_BACKUP} -eq 0 ]]; then LAST_BACKUP="$(ls -1t "${out}"/casino-backup-* 2>/dev/null | head -1 || true)"; [[ -n "${LAST_BACKUP}" ]] && info "Backup final: ${LAST_BACKUP}"; fi
  elif [[ -f "${dir}/.env" ]]; then
    if [[ ${NO_BACKUP} -eq 0 && -n "${db}" ]]; then make_backup "${dir}"; fi
    rm -f "${dir}/.env" "${dir}/storage/installed.lock" "${dir}/storage/install.token" "${dir}/storage/local-server.pid"
    rm -rf "${dir}/public/storage"
    ok "Arquivos de configuração removidos"
  fi

  # 3. banco e usuário do MySQL (só os do site; bancos com tabelas de outros sistemas ficam)
  if [[ ${KEEP_DB} -eq 0 && -n "${db}" ]] && [[ -z "${host}" || "${host}" == "localhost" || "${host}" == "127.0.0.1" || "${host}" == "::1" ]]; then
    drop_db_leftovers "${db}" "${user}" "${dir}" "$(registry_get "${reg:-/dev/null}" DB_CREATED)"
  elif [[ -n "${db}" && ${KEEP_DB} -eq 0 ]]; then
    info "Banco em outro servidor (${host}): apague '${db}' por lá, se quiser."
  fi

  # 4. servidor web, agendador etc.
  if [[ ${is_server} -eq 1 && ${EUID} -eq 0 ]]; then server_cleanup "${dir}"; fi

  # 5. a pasta
  if [[ ${is_server} -eq 1 || ${PURGE} -eq 1 ]]; then delete_dir=1
  elif [[ ${INTERACTIVE} -eq 1 ]]; then
    if [[ "${dir}" == "${CHECKOUT}" ]]; then
      confirm "Apagar também a pasta com o código (${dir})?" n && delete_dir=1
    else
      confirm "Apagar a pasta ${dir} inteira?" s && delete_dir=1
    fi
  fi
  if [[ ${delete_dir} -eq 1 ]]; then
    if safe_to_delete "${dir}"; then
      ${SUDO} rm -rf "${dir}" && ok "Pasta apagada"
      # cópia extraída dentro de outra pasta (casino-main/casino-main): a de fora ficou vazia
      local parent; parent="$(dirname "${dir}")"
      if [[ "$(basename "${parent}")" == casino* ]]; then ${SUDO} rmdir "${parent}" 2>/dev/null || true; fi
    else
      warn "Por segurança, não apago ${dir} automaticamente; apague à mão se quiser."
    fi
  elif [[ -d "${dir}/vendor" ]]; then
    rm -rf "${dir}/vendor" && ok "Dependências (vendor) removidas; o código ficou em ${dir}"
  fi

  if [[ -n "${reg}" ]]; then
    UN_PKGS="$(merge_words ${UN_PKGS} "$(registry_get "${reg}" PKGS)")"
    UN_COMPOSER="$(registry_get "${reg}" COMPOSER_LOCAL)${UN_COMPOSER:+ ${UN_COMPOSER}}"
    UN_SWAP="$(registry_get "${reg}" SWAPFILE)${UN_SWAP:+ ${UN_SWAP}}"
    UN_SRC="$(registry_get "${reg}" SRC_DIR)${UN_SRC:+ ${UN_SRC}}"
    rm -f "${reg}"
  fi
  return 0
}

do_uninstall() {
  detect_env
  STEPS=3
  step "Procurando instalações"
  find_installs
  if [[ ${#UN_DIRS[@]} -eq 0 ]]; then
    ok "Nenhuma instalação do Casino Platform encontrada${APP_DIR:+ em ${APP_DIR}}."
    cleanup_leftovers
    return 0
  fi
  info "Encontradas ${#UN_DIRS[@]} pasta(s) do Casino Platform:"
  pick_installs
  local d
  if [[ ${EUID} -ne 0 ]]; then
    for d in "${UN_SELECTED[@]}"; do
      if [[ -n "$(server_site_for "${d}")" || "${d}" == /var/www/* || ! -w "${d}" ]]; then
        die "Remover ${d} precisa de administrador (é uma instalação de servidor). Rode:  sudo bash ${SELF:-scripts/auto-install.sh} --uninstall --dir ${d}"
      fi
    done
  fi

  step "O que será feito"
  local lines=()
  for d in "${UN_SELECTED[@]}"; do lines+=("  ${c_red}${I_FAIL}${c_off} ${d}  (${c_dim}$(install_state "${d}")${c_off})"); done
  lines+=("" "  Para cada uma: backup final$([[ ${NO_BACKUP} -eq 1 ]] && echo ' (desligado: --no-backup)' || true), desligar o site, apagar o banco e o usuário")
  lines+=("  do MySQL$([[ ${KEEP_DB} -eq 1 ]] && echo ' (mantidos: --keep-database)' || true), .env, uploads, logs, e o servidor web/agendador.")
  lines+=("  Backups ficam em ${BACKUP_DIR} (servidor: /var/backups/casino).")
  box "Desinstalar" "${lines[@]}"
  if [[ ${INTERACTIVE} -eq 1 ]]; then
    local answer; ask answer "Para confirmar, digite DESINSTALAR" ""
    [[ "$(echo "${answer}" | tr 'a-z' 'A-Z')" == "DESINSTALAR" ]] || die "Cancelado."
  elif [[ ${ASSUME_YES} -eq 0 ]]; then
    die "Sem terminal para confirmar: rode com -y para desinstalar sem perguntas."
  fi

  # o administrador do MySQL e o sudo são pedidos uma vez só, antes de começar
  if [[ ${KEEP_DB} -eq 0 && ${EUID} -ne 0 ]] && have sudo && have_local_db_installs; then
    setup_sudo "apagar os bancos de dados" soft || true
  fi

  step "Removendo"
  UN_PKGS=""; UN_COMPOSER=""; UN_SWAP=""; UN_SRC=""
  for d in "${UN_SELECTED[@]}"; do uninstall_one "${d}"; done
  cleanup_leftovers

  echo
  local summary=("" "  Removidas: ${#UN_SELECTED[@]} instalação(ões).")
  if [[ -n "${LAST_BACKUP}" ]]; then
    summary+=("  Último backup: ${c_accent}${LAST_BACKUP}${c_off}"
              "  ${c_dim}Para reinstalar com os dados: instale e rode  bash instalar.sh --restore ARQUIVO${c_off}"
              "  ${c_dim}Os backups ficam guardados de propósito; se não precisar mais, apague a pasta${c_off}"
              "  ${c_dim}$(dirname "${LAST_BACKUP}")${c_off}")
  fi
  summary+=("")
  box "Desinstalação concluída" "${summary[@]}"
}

have_local_db_installs() {
  local d h
  for d in "${UN_SELECTED[@]}"; do
    h="$(env_value "${d}" DB_HOST)"
    if [[ -n "$(env_value "${d}" DB_DATABASE)" ]] && [[ -z "${h}" || "${h}" == "localhost" || "${h}" == "127.0.0.1" ]]; then return 0; fi
  done
  return 1
}

cleanup_leftovers() {   # restos que não pertencem a uma pasta: credenciais, registros órfãos, código-fonte, programas
  local f d remaining=0
  # registros de instalações cujas pastas já não existem (instalações antigas apagadas à mão)
  while IFS= read -r f; do
    d="$(registry_get "${f}" DIR)"
    if [[ -n "${d}" ]] && is_casino_dir "${d}"; then remaining=$((remaining + 1)); continue; fi
    local odb ouser; odb="$(registry_get "${f}" DB_NAME)"; ouser="$(registry_get "${f}" DB_USER)"
    if [[ -n "${odb}" && "$(registry_get "${f}" DB_CREATED)" == "1" && ${KEEP_DB} -eq 0 ]]; then
      info "Restos de uma instalação apagada (${d:-?}): banco '${odb}'"
      drop_db_leftovers "${odb}" "${ouser}" "" 1
    fi
    UN_PKGS="$(merge_words ${UN_PKGS:-} "$(registry_get "${f}" PKGS)")"
    UN_COMPOSER="$(registry_get "${f}" COMPOSER_LOCAL)${UN_COMPOSER:+ ${UN_COMPOSER}}"
    UN_SRC="$(registry_get "${f}" SRC_DIR)${UN_SRC:+ ${UN_SRC}}"
    rm -f "${f}"
  done < <(registry_files)
  if [[ ${remaining} -eq 0 ]]; then
    for f in "${HOME}/casino-credenciais-local.txt" /root/casino-credenciais.txt; do
      if [[ -f "${f}" ]] && rm -f "${f}" 2>/dev/null; then ok "Arquivo de senhas removido: ${f}"; fi
    done
    for d in ${UN_SRC:-} /opt/casino-src; do
      if [[ -d "${d}" ]] && is_casino_dir "${d}" && [[ ${EUID} -eq 0 ]]; then rm -rf "${d}" && ok "Código-fonte do servidor removido: ${d}"; fi
    done
    if [[ ${EUID} -eq 0 ]]; then
      for f in /etc/php/*/fpm/conf.d/99-casino.ini; do
        if [[ -f "${f}" ]]; then rm -f "${f}"; svc reload "php$(basename "$(dirname "$(dirname "$(dirname "${f}")")")")-fpm" >>"${LOG}" 2>&1 || true; ok "Ajustes do PHP-FPM removidos (${f})"; fi
      done
      rmdir /etc/casino-platform/installs /etc/casino-platform 2>/dev/null || true
    fi
    rmdir "${XDG_CONFIG_HOME:-${HOME}/.config}/casino-platform/installs" "${XDG_CONFIG_HOME:-${HOME}/.config}/casino-platform" 2>/dev/null || true
    purge_programs
  fi
  return 0
}

purge_programs() {   # programas que o PRÓPRIO assistente instalou (nunca os que já existiam)
  local c s d size removed_composer=0 cache_dirs=()
  # o assistente sempre baixa o Composer para ~/.local/bin (instalações antigas não tinham o registro)
  if [[ -f "${HOME}/.local/bin/composer" ]]; then UN_COMPOSER="$(merge_words ${UN_COMPOSER:-} "${HOME}/.local/bin/composer")"; fi
  for c in ${UN_COMPOSER:-}; do
    # sem perguntas (-y) vale o padrão de cada pergunta: Composer e cache saem; pacotes e swap ficam
    if [[ -f "${c}" ]] && { [[ ${PURGE_PACKAGES} -eq 1 ]] || confirm "Remover o Composer que o assistente baixou (${c})?" s; }; then
      rm -f "${c}" && ok "Composer removido" && removed_composer=1
      rmdir "$(dirname "${c}")" 2>/dev/null || true
    fi
  done
  # cache do Composer (com a alternativa pelo git pode passar de 500 MB) e a configuração dele
  for d in "${HOME}/.cache/composer" "${HOME}/.config/composer" "${HOME}/.local/share/composer"; do
    if [[ -d "${d}" ]]; then cache_dirs+=("${d}"); fi
  done
  if [[ ${#cache_dirs[@]} -gt 0 ]]; then
    size="$(du -sch "${cache_dirs[@]}" 2>/dev/null | tail -1 | cut -f1 || true)"
    if [[ ${removed_composer} -eq 1 || ${PURGE_PACKAGES} -eq 1 ]] \
       || confirm "Apagar o cache do Composer (${size:-?})? Outros projetos PHP só baixam de novo o que precisarem" s; then
      rm -rf "${cache_dirs[@]}" && ok "Cache e configuração do Composer apagados (${size:-?})"
    fi
  fi
  if [[ -n "${UN_PKGS:-}" ]] && have apt-get; then
    local pk=() p; for p in ${UN_PKGS}; do pkg_installed "${p}" && pk+=("${p}"); done
    if [[ ${#pk[@]} -gt 0 ]]; then
      info "Programas que o assistente instalou: ${pk[*]}"
      if [[ ${PURGE_PACKAGES} -eq 1 ]] || confirm "Remover esses programas também? (o MariaDB leva TODOS os bancos dele junto)" n; then
        setup_sudo "remover os programas" soft || true
        run "Removendo ${#pk[@]} pacote(s)" ${SUDO} env DEBIAN_FRONTEND=noninteractive apt-get purge -y -qq "${pk[@]}"
        run "Limpando dependências que sobraram" ${SUDO} env DEBIAN_FRONTEND=noninteractive apt-get autoremove -y -qq
      else
        info "Programas mantidos. Para remover depois:  sudo apt purge ${pk[*]} && sudo apt autoremove"
      fi
    fi
  fi
  for s in ${UN_SWAP:-}; do
    if [[ -f "${s}" && ${EUID} -eq 0 ]] && { [[ ${PURGE_PACKAGES} -eq 1 ]] || confirm "Remover o swap ${s} criado pelo assistente?" n; }; then
      swapoff "${s}" >>"${LOG}" 2>&1 || true; rm -f "${s}"; sed -i "\#^${s} #d" /etc/fstab; ok "Swap ${s} removido"
    fi
  done
  return 0
}

# =============================================================================
#  Diagnóstico
# =============================================================================
do_diagnose() {
  detect_env; banner; preflight server 2>/dev/null || true
  local dirs=("${APP_DIR}" "${CHECKOUT}" /var/www/casino) d seen=""
  for d in "${dirs[@]}"; do
    [[ -n "${d}" && -f "${d}/artisan" && "${seen}" != *"|${d}|"* ]] || continue; seen+="|${d}|"
    local lines=()
    [[ -f "${d}/storage/installed.lock" ]] && lines+=("  ${c_green}${I_OK}${c_off} instalada") || lines+=("  ${c_red}${I_FAIL}${c_off} não instalada")
    [[ -f "${d}/vendor/autoload.php" ]] && lines+=("  ${c_green}${I_OK}${c_off} dependências (vendor)") || lines+=("  ${c_red}${I_FAIL}${c_off} sem a pasta vendor")
    [[ -f "${d}/.env" ]] && lines+=("  ${c_green}${I_OK}${c_off} .env  $(grep -E '^APP_URL=' "${d}/.env" | cut -d= -f2-)") || lines+=("  ${c_red}${I_FAIL}${c_off} sem .env")
    [[ -w "${d}/storage" ]] && lines+=("  ${c_green}${I_OK}${c_off} storage gravável") || lines+=("  ${c_red}${I_FAIL}${c_off} storage sem permissão de escrita")
    local pf="${d}/storage/local-server.pid"
    if [[ -f "${pf}" ]]; then kill -0 "$(cat "${pf}")" 2>/dev/null && lines+=("  ${c_green}${I_OK}${c_off} servidor local ligado (PID $(cat "${pf}"))") || lines+=("  ${c_yellow}${I_WARN}${c_off} servidor local desligado"); fi
    if [[ -f "${d}/storage/logs/laravel.log" ]]; then
      local last; last="$(grep -E '^\[20' "${d}/storage/logs/laravel.log" | tail -1 | cut -c1-45 || true)"
      [[ -n "${last}" ]] && lines+=("  ${c_dim}último erro: ${last}...${c_off}")
    fi
    box "Instalação em ${d}" "${lines[@]}"
  done
  [[ -z "${seen}" ]] && info "Nenhuma instalação encontrada."
  return 0
}

# =============================================================================
#  Menu principal
# =============================================================================
main_menu() {
  banner
  detect_env
  local c
  choose c "O que você quer fazer?" \
    "Instalar no meu computador (testar ou desenvolver)" \
    "Instalar em um servidor (VPS) com domínio e HTTPS" \
    "Atualizar sem perder dados (faz backup antes)" \
    "Backup / restaurar" \
    "Ligar / desligar o site local" \
    "Diagnóstico (o que está faltando?)" \
    "Desinstalar (remover instalações, inclusive antigas)" \
    "Sair"
  case "${c}" in
    1) TARGET="local"; MODE="install" ;;
    2) TARGET="server"; MODE="install" ;;
    3) MODE="update" ;;
    4) local b; choose b "Backup" "Fazer um backup agora" "Restaurar um backup"
       case "${b}" in 2) MODE="restore" ;; *) MODE="backup" ;; esac ;;
    5) TARGET="local"; local a; choose a "Site local" "Ligar" "Desligar" "Ver status"
       case "${a}" in 2) MODE="stop" ;; 3) MODE="status" ;; *) MODE="start" ;; esac ;;
    6) MODE="diagnose" ;;
    7) MODE="uninstall" ;;
    *) exit 0 ;;
  esac
}

# =============================================================================
#  Execução
# =============================================================================
if [[ ${INTERACTIVE} -eq 1 && -z "${TARGET}" && "${MODE}" == "install" ]]; then main_menu
elif [[ "${MODE}" != "diagnose" ]]; then banner; fi

if [[ -z "${TARGET}" && "${MODE}" == "install" ]]; then
  usage; echo; die "Informe o que fazer: --local (seu computador) ou --domain (servidor). Ou rode em um terminal para abrir o menu."
fi

case "${MODE}" in
  install)  if [[ "${TARGET}" == "local" ]]; then do_local_install; else do_server_install; fi ;;
  update)   do_update ;;
  backup)   do_backup ;;
  restore)  do_restore ;;
  uninstall) do_uninstall ;;
  diagnose) do_diagnose ;;
  start|stop|status)
    [[ -n "${APP_DIR}" ]] || APP_DIR="${CHECKOUT:-${HOME}/casino}"
    case "${MODE}" in start) local_start ;; stop) local_stop ;; status) local_status ;; esac ;;
esac
