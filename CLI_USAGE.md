# CLI_USAGE.md

## Comando principal
```bash
php artisan system:install
```

## Modos
- `vps`
- `cpanel`
- `localhost`
- `docker`
- `diagnose`
- `help`

## Exemplos
```bash
php artisan system:install --mode=diagnose
php artisan system:install --mode=help
php artisan system:install --mode=vps --domain=casino.site --ssl=sim --email=admin@casino.site
```

## Opções relevantes
- `--db-connection=mysql|pgsql`
- `--db-host`
- `--db-port`
- `--db-name`
- `--db-user`
- `--db-pass`
- `--admin-name`
- `--admin-pass`
