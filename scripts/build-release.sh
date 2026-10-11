#!/usr/bin/env bash
# -----------------------------------------------------------------------------
# Gera o pacote "pronto para hospedagem" (casino-pronto-para-hospedagem.zip):
#   - dependências PHP de produção (vendor) já instaladas
#   - frontend já compilado (public/build)
#   - sem node_modules, .git, .env, documentação e scripts de servidor
#
# Basta enviar o zip para o public_html, extrair e abrir https://seu-dominio/install/
#
# Uso: bash scripts/build-release.sh [pasta-de-saida]
# -----------------------------------------------------------------------------
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-${ROOT}/dist}"
mkdir -p "${OUT}"
OUT="$(cd "${OUT}" && pwd)"          # caminho absoluto (o zip é criado de dentro da pasta de preparo)
STAGE="${OUT}/public_html"
ZIP="${OUT}/casino-pronto-para-hospedagem.zip"

command -v composer >/dev/null || { echo "composer não encontrado"; exit 1; }
command -v npm >/dev/null || { echo "npm não encontrado"; exit 1; }
command -v zip >/dev/null || { echo "zip não encontrado"; exit 1; }

echo "==> Compilando o frontend"
( cd "${ROOT}" && { [[ -d node_modules ]] || npm ci --no-audit --no-fund; } && npm run build )

echo "==> Copiando arquivos para ${STAGE}"
rm -rf "${STAGE}" "${ZIP}"
mkdir -p "${STAGE}"
rsync -a \
  --exclude '.git' --exclude '/.github' --exclude '/node_modules' --exclude '/vendor' --exclude '/dist' \
  --exclude '/docs' --exclude '/scripts' --exclude '/docker' --exclude '/docker-compose.yml' --exclude '/.dockerignore' \
  --exclude '/tests' --exclude '/.env' --exclude '/storage/installed.lock' --exclude '/storage/install.token' \
  --exclude '/storage/logs/*.log' --exclude '/storage/framework/cache/data/*' \
  --exclude '/storage/framework/sessions/*' --exclude '/storage/framework/views/*' \
  --exclude '/bootstrap/cache/*.php' --exclude '/public/storage' --exclude '/public/hot' \
  --exclude '/.phpunit.result.cache' \
  "${ROOT}/" "${STAGE}/"

echo "==> Instalando dependências PHP de produção"
# REUSE_VENDOR=1 reaproveita a pasta vendor local (mais rápido, sem download)
if [[ "${REUSE_VENDOR:-0}" == "1" && -d "${ROOT}/vendor" ]]; then
  rsync -a --exclude '.git' "${ROOT}/vendor/" "${STAGE}/vendor/"
fi
( cd "${STAGE}" && composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-progress --no-scripts \
  && composer dump-autoload --optimize --no-dev --no-scripts )
find "${STAGE}/vendor" -type d -name .git -prune -exec rm -rf {} +

mkdir -p "${STAGE}/storage/framework/"{cache/data,sessions,views} "${STAGE}/storage/logs" "${STAGE}/bootstrap/cache"
chmod -R 775 "${STAGE}/storage" "${STAGE}/bootstrap/cache"

echo "==> Gerando ${ZIP}"
( cd "${STAGE}" && zip -qr "${ZIP}" . )

echo "Pacote pronto: ${ZIP} ($(du -h "${ZIP}" | cut -f1))"
