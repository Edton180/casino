# INSTALLATION.md

## 1) Pré-requisitos
```bash
php -v
composer -V
node -v
npm -v
```

## 2) Instalação web
1. Acesse `/install`.
2. Revise diagnóstico de ambiente e extensões.
3. Teste conexão de banco.
4. Informe modo, domínio, SSL e super admin.
5. Execute instalação.

## 3) Instalação CLI
```bash
cd "extraia para public_html/public_html"
php artisan system:install
```

## 4) Instalação automatizada (não interativa)
```bash
bash scripts/auto-install.sh \
  --mode vps \
  --domain example.com \
  --ssl \
  --email admin@example.com \
  --db-name casino \
  --db-user casino_user \
  --db-pass 'StrongPass!123' \
  --admin-pass 'AdminPass!123' \
  --non-interactive
```

## 5) Pós-instalação
```bash
php artisan optimize
php artisan queue:work
```
