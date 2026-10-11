# TROUBLESHOOTING.md

## Problemas Comuns

> Criador: **Edton Braz**

### Erro 500
- Verifique logs: `storage/logs/laravel.log`.
- Rode: `php artisan optimize:clear`.

### Permissão de storage

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

### Composer memory limit

```bash
php -d memory_limit=-1 /usr/local/bin/composer install
```

### Node versão errada

```bash
node -v
# usar Node 20+
```

### Porta 80 ocupada

```bash
sudo lsof -i :80
sudo systemctl stop apache2
```

### SSL falhando
- Verifique DNS apontando para IP correto.
- Teste emissão manual com Certbot.

### Banco não conecta
- Revise `DB_*` no `.env`.
- Teste credenciais diretamente no MySQL.
