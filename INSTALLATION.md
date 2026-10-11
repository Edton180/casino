# 📦 Guia de Instalação – Casino Platform v8.0.0

Guia completo de instalação do **Casino Platform** para desenvolvimento e produção.

> Redname do criador: **Edton Braz**

---

## ⚠️ AVISO IMPORTANTE – SEM SUPORTE TÉCNICO

Este sistema é fornecido **"como está"**.

Não oferecemos suporte para:

- Instalação do sistema
- Configuração de servidor
- Correção de erros
- Customizações

Conhecimentos necessários:

- Linux (Ubuntu/Debian/CentOS)
- PHP 8.2+
- Laravel 10+
- Node.js 20+
- MySQL 8+
- Nginx ou Apache
- Docker (opcional)

---

## 📋 Requisitos Mínimos

### Para VPS/Servidor

| Item | Mínimo |
|---|---|
| Sistema | Ubuntu 20.04+ / Debian 11+ |
| RAM | 4GB (recomendado 8GB) |
| Disco | 30GB SSD |
| CPU | 2 vCPU |
| Acesso | Root |

### Para Desenvolvimento Local

| Item | Mínimo |
|---|---|
| PHP | 8.2+ |
| Composer | 2+ |
| Node.js | 20+ |
| Banco | MySQL 8+ |
| RAM | 4GB |
| Disco | 10GB |

### Para cPanel

| Item | Mínimo |
|---|---|
| PHP | 8.2+ |
| Banco | MySQL 8+ |
| SSH | Recomendado |
| Disco | 2GB |

---

## 🚀 Instalador Automático v8.0.0

Script oficial:

```bash
scripts/auto-install.sh
```

Instalação direta:

```bash
wget https://raw.githubusercontent.com/Edton180/casino/main/scripts/auto-install.sh
sudo bash auto-install.sh
```

Parâmetros CLI:

```bash
--mode vps|docker|localhost|cpanel|virtualbox|diagnose
--domain meusite.com
--email admin@meusite.com
--ssl
--non-interactive
--help
```

Exemplo:

```bash
sudo bash auto-install.sh --mode vps --domain casino.com --email admin@casino.com --ssl
```

No modo VPS, o instalador prepara:

- PHP 8.2
- Composer
- Node.js 20 / NPM
- MySQL
- Nginx
- Certbot
- Supervisor
- Git
- Deploy em `/opt/casino`
- Build e otimização Laravel
- Cron do Laravel
- Lock file: `storage/installed.lock`

---

## 🖥️ Instalação Manual em VPS

### 1) Atualizar servidor e dependências

```bash
apt update && apt upgrade -y
apt install -y nginx mysql-server php8.2 php8.2-fpm php8.2-mysql php8.2-cli unzip curl git
```

### 2) Instalar Node.js 20

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt install -y nodejs
```

### 3) Instalar Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
```

### 4) Clonar e instalar aplicação

```bash
git clone https://github.com/Edton180/casino.git /opt/casino
cd /opt/casino
cp .env.example .env
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

### 5) Permissões Laravel

```bash
chown -R www-data:www-data /opt/casino
chmod -R 775 /opt/casino/storage /opt/casino/bootstrap/cache
```

### 6) Nginx e SSL

Use os guias: [DEPLOY.md](DEPLOY.md) e [SECURITY.md](SECURITY.md).

---

## 🌐 Instalação em cPanel

1. Suba os arquivos para `public_html` (ou pasta dedicada).
2. Ajuste o DocumentRoot para `.../public`.
3. Configure o `.env` manualmente.
4. Rode Composer via SSH.
5. Execute migrations.
6. Ative SSL no painel.

Guia completo: [MANUAL_INSTALL_CPANEL.md](MANUAL_INSTALL_CPANEL.md).

---

## 💻 Localhost (Desenvolvimento)

```bash
git clone https://github.com/Edton180/casino.git
cd casino
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate
npm install
npm run dev
php artisan serve
```

---

## 🗄️ Configuração de Banco

1. Crie o banco.
2. Crie usuário e permissões.
3. Ajuste o `.env`:

```env
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

---

## 👤 Setup Inicial

Após primeiro login:

1. Criar usuário admin
2. Configurar gateway
3. Configurar moeda
4. Configurar jogos
5. Configurar limites

---

## 🔄 Manutenção

Scripts:

- `scripts/casino-update`
- `scripts/casino-backup`
- `scripts/casino-health`
- `scripts/casino-diagnose`

Fluxo padrão:

```bash
cd /opt/casino
git pull
composer install
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

---

## 🔐 Segurança

Checklist de produção:

- HTTPS obrigatório
- `APP_ENV=production`
- `APP_DEBUG=false`
- Permissões corretas
- Firewall ativo
- Backup diário

Detalhes: [SECURITY.md](SECURITY.md).

---

## 🆘 Troubleshooting

Consulte [TROUBLESHOOTING.md](TROUBLESHOOTING.md) para:

- Erro 500
- Permissão `storage`
- Composer memory limit
- Node versão errada
- Porta 80 ocupada
- SSL falhando
- Banco não conecta
