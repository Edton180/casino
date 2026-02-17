# MANUAL_INSTALL_CPANEL.md

## Instalação Manual no cPanel

> Criador: **Edton Braz**

1. Envie os arquivos para a conta cPanel.
2. Ajuste o domínio/subdomínio para usar `public` como DocumentRoot.
3. Crie banco e usuário MySQL no cPanel.
4. Atualize `.env`:

```env
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nome_banco
DB_USERNAME=usuario_banco
DB_PASSWORD=senha_banco
```

5. Via SSH (recomendado):

```bash
cd ~/casino
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
npm install
npm run build
php artisan optimize
```

6. Ative SSL pelo AutoSSL/cPanel.
