# DEPLOY.md

## Deploy de Produção (VPS)

> Criador: **Edton Braz**

1. Clonar projeto em `/opt/casino`.
2. Configurar `.env` com `APP_ENV=production` e `APP_DEBUG=false`.
3. Instalar dependências e build:

```bash
cd /opt/casino
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan migrate --force
php artisan optimize
```

4. Configurar Nginx apontando para `/opt/casino/public`.
5. Ativar SSL com Certbot:

```bash
certbot --nginx -d seu-dominio.com -m admin@seu-dominio.com --agree-tos --redirect
```

6. Configurar cron:

```bash
* * * * * cd /opt/casino && php artisan schedule:run >> /dev/null 2>&1
```

7. Configurar Supervisor para filas (se necessário).
