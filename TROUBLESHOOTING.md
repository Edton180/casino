# TROUBLESHOOTING.md

## Erro de conexão com banco
- Verifique host, porta, usuário e senha.
- Teste com `php artisan system:install --mode=diagnose`.

## Extensão PHP ausente
Instale extensão faltante e reinicie PHP-FPM/Apache.

## Permissão negada em storage/bootstrap
```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

## Frontend build falhou
- Validar Node/NPM.
- Executar manualmente:
```bash
npm install
npm run build
```
