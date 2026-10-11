#!/usr/bin/env bash
# Mantido por compatibilidade: agora é o mesmo assistente (instala o que faltar, cria o banco etc.).
#   bash scripts/install-local.sh [--port 8000] [--db-user ... --db-pass ...] [--no-serve]
#   bash scripts/install-local.sh --serve     → só liga o site
exec bash "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/auto-install.sh" --local "$@"
