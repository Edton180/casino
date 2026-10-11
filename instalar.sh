#!/usr/bin/env bash
# Atalho: abre o assistente de instalação (Linux, macOS, WSL).
#   bash instalar.sh            → menu: instalar no computador, em servidor, atualizar, diagnóstico
#   bash instalar.sh --help     → todas as opções
exec bash "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/scripts/auto-install.sh" "$@"
