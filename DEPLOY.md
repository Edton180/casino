# DEPLOY.md

## VPS (Ubuntu/Debian + NGINX + systemd + SSL)
1. Deploy do código e `composer install --no-dev`.
2. `npm ci && npm run build`.
3. `php artisan migrate --force`.
4. Configurar Nginx apontando para `public/`.
5. SSL com Certbot e redirect HTTP->HTTPS.
6. Queue worker via systemd.
7. Scheduler no cron.

## cPanel
1. Aplicação fora de `public_html` quando possível.
2. Conteúdo de `public/` em `public_html`.
3. Ajustar `index.php` paths.
4. Configurar `.env` e permissões.
5. Rodar `php artisan migrate --force` no terminal cPanel.

## Produção
- `APP_ENV=production`
- `APP_DEBUG=false`
- `SESSION_SECURE_COOKIE=true`
- Logs centralizados e rotação.
