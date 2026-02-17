#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_NAME=$(basename "$0")
ROOT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
APP_DIR_REL='extraia para public_html/public_html'
APP_DIR="${ROOT_DIR}/${APP_DIR_REL}"
FORCE=0
WITH_SEED=0
SKIP_COMPOSER=0
SKIP_NPM=0
NO_INTERACTION=0

usage() {
  cat <<USAGE
Instalador automático da Casino Platform

Uso:
  ${SCRIPT_NAME} [opções]

Opções:
  --app-dir <caminho>    Caminho da aplicação Laravel (padrão: ${APP_DIR_REL})
  --force                Executa migrações com --force
  --with-seed            Executa php artisan db:seed após migrate
  --skip-composer        Pula instalação de dependências PHP
  --skip-npm             Pula instalação de dependências Node.js
  --no-interaction       Repassa --no-interaction para comandos suportados
  -h, --help             Exibe esta ajuda

Exemplos:
  ${SCRIPT_NAME}
  ${SCRIPT_NAME} --force --with-seed
  ${SCRIPT_NAME} --app-dir "extraia para public_html/public_html" --skip-npm
USAGE
}

log() { printf '\n[%s] %s\n' "$1" "$2"; }
info() { log "INFO" "$1"; }
warn() { log "WARN" "$1"; }
ok() { log "OK" "$1"; }
err() { log "ERRO" "$1"; }

trap 'err "Falha na linha $LINENO. Abortando."' ERR

require_cmd() {
  local cmd="$1"
  command -v "$cmd" >/dev/null 2>&1 || {
    err "Comando obrigatório não encontrado: $cmd"
    exit 1
  }
}

version_report() {
  info "Verificando versões de runtime"
  php -v | head -n1
  composer -V
  node -v
  npm -v
}

parse_args() {
  while [[ $# -gt 0 ]]; do
    case "$1" in
      --app-dir)
        APP_DIR="$2"
        shift 2
        ;;
      --force)
        FORCE=1
        shift
        ;;
      --with-seed)
        WITH_SEED=1
        shift
        ;;
      --skip-composer)
        SKIP_COMPOSER=1
        shift
        ;;
      --skip-npm)
        SKIP_NPM=1
        shift
        ;;
      --no-interaction)
        NO_INTERACTION=1
        shift
        ;;
      -h|--help)
        usage
        exit 0
        ;;
      *)
        err "Opção desconhecida: $1"
        usage
        exit 1
        ;;
    esac
  done
}

composer_install() {
  local args=(install)
  [[ $NO_INTERACTION -eq 1 ]] && args+=(--no-interaction)
  info "Instalando dependências PHP com Composer"
  composer "${args[@]}"
}

npm_install() {
  info "Instalando dependências Node.js"
  if [[ -f package-lock.json ]]; then
    npm ci
  else
    npm install
  fi
}

artisan() {
  local args=("$@")
  [[ $NO_INTERACTION -eq 1 ]] && args+=(--no-interaction)
  php artisan "${args[@]}"
}

prepare_env() {
  if [[ ! -f .env ]]; then
    info "Criando .env a partir de .env.example"
    cp .env.example .env
  else
    warn ".env já existe. Mantendo arquivo atual"
  fi
}

ensure_key() {
  if grep -Eq '^APP_KEY=base64:' .env; then
    ok "APP_KEY já configurada"
  else
    info "Gerando APP_KEY"
    artisan key:generate
  fi
}

run_migrations() {
  local args=(migrate)
  [[ $FORCE -eq 1 ]] && args+=(--force)
  info "Executando migrações"
  artisan "${args[@]}"

  if [[ $WITH_SEED -eq 1 ]]; then
    info "Executando seeders"
    artisan db:seed
  fi
}

main() {
  parse_args "$@"

  require_cmd php
  require_cmd composer
  require_cmd node
  require_cmd npm

  [[ -d "$APP_DIR" ]] || {
    err "Diretório da aplicação não encontrado: $APP_DIR"
    exit 1
  }

  info "Iniciando auto instalação profissional"
  info "Raiz do repositório: $ROOT_DIR"
  info "Diretório da aplicação: $APP_DIR"
  version_report

  cd "$APP_DIR"

  prepare_env

  if [[ $SKIP_COMPOSER -eq 0 ]]; then
    composer_install
  else
    warn "Etapa Composer ignorada (--skip-composer)"
  fi

  if [[ $SKIP_NPM -eq 0 ]]; then
    npm_install
  else
    warn "Etapa npm ignorada (--skip-npm)"
  fi

  ensure_key
  run_migrations

  info "Gerando link de storage"
  artisan storage:link || warn "storage:link já pode existir, seguindo..."

  info "Limpando caches"
  artisan optimize:clear

  ok "Instalação concluída com sucesso"
  cat <<NEXT

Próximos passos recomendados:
  1) Revisar credenciais no arquivo .env
  2) Subir backend: php artisan serve --host=127.0.0.1 --port=8000
  3) Subir frontend: npm run dev -- --host 127.0.0.1 --port 5173

NEXT
}

main "$@"
