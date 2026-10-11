#!/usr/bin/env bash
# Casino Platform — instalador automático (Linux, macOS, WSL).
#
#   Um comando, sem baixar nada antes (baixa o código, instala PHP/banco/Composer e liga o site):
#     curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/instalar.sh | bash -s -- --auto
#
#   Dentro da pasta do projeto:
#     bash instalar.sh            → menu (computador, servidor, atualizar, diagnóstico)
#     bash instalar.sh --auto     → instala no computador sem perguntar nada
#     bash instalar.sh --help     → todas as opções
REPO_SLUG="${REPO_SLUG:-Edton180/casino}"
BRANCH="${BRANCH:-main}"
RAW="${CASINO_RAW:-https://raw.githubusercontent.com/${REPO_SLUG}/${BRANCH}}"

SELF="${BASH_SOURCE[0]:-}"
DIR=""
if [[ -n "${SELF}" && -f "${SELF}" ]]; then DIR="$(cd "$(dirname "${SELF}")" && pwd)"; fi

# Executado via "curl | bash" (não há pasta do projeto ao lado): baixa o assistente e continua.
if [[ -z "${DIR}" || ! -f "${DIR}/scripts/auto-install.sh" ]]; then
  command -v curl >/dev/null 2>&1 || { echo "Preciso do curl:  sudo apt install -y curl" >&2; exit 1; }
  TMP="$(mktemp -d)"; trap 'rm -rf "${TMP}"' EXIT
  curl -fsSL "${RAW}/scripts/auto-install.sh" -o "${TMP}/auto-install.sh" \
    || { echo "Não consegui baixar o assistente de ${RAW}. Confira a internet e se o repositório é público." >&2; exit 1; }
  # com "curl | bash" o teclado não é a entrada padrão: reconecta ao terminal para o menu/perguntas
  if [[ ! -t 0 && -r /dev/tty ]] && (: </dev/tty) 2>/dev/null; then exec bash "${TMP}/auto-install.sh" "$@" </dev/tty; fi
  exec bash "${TMP}/auto-install.sh" "$@"
fi

# Dentro de uma cópia git: puxa as novidades antes de rodar (falha em silêncio se houver alterações locais).
if [[ -d "${DIR}/.git" && -z "${CASINO_NO_UPDATE:-}" ]] && command -v git >/dev/null 2>&1; then
  before="$(git -C "${DIR}" rev-parse HEAD 2>/dev/null || true)"
  GIT_TERMINAL_PROMPT=0 timeout 20 git -C "${DIR}" pull -q --ff-only origin "${BRANCH}" >/dev/null 2>&1 || true
  after="$(git -C "${DIR}" rev-parse HEAD 2>/dev/null || true)"
  [[ "${before}" == "${after}" ]] || echo "  ↻ Instalador atualizado para a versão mais recente."
fi

exec bash "${DIR}/scripts/auto-install.sh" "$@"
