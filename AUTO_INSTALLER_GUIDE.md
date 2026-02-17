# AUTO_INSTALLER_GUIDE.md

## Componentes
- Web installer: `/install`
- CLI installer: `php artisan system:install`
- Shell installer: `scripts/auto-install.sh`

## Fluxo
1. Detecta ambiente (VPS/cPanel/Windows localhost).
2. Valida extensões e permissões.
3. Testa banco antes de gravar `.env`.
4. Gera `.env` com APP/DB/SSL/mode.
5. Executa `key:generate`, `migrate`, `optimize`.
6. Tenta build frontend com fallback.
7. Cria super admin.
8. Bloqueia instalador com `storage/installed.lock`.

## Melhorias comerciais recomendadas
- Versionamento do instalador (`INSTALLER_VERSION`).
- Check de updates via endpoint GitHub.
- Logs detalhados por etapa.
- Suporte opcional a PostgreSQL.
