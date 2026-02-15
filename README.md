# 🎰 Casino Platform — Documentação completa

## 👤 Autor
**Edton Braz**

---

## 📌 Sobre o projeto
A **Casino Platform** é uma aplicação web para operação de cassino online, com backend em Laravel e frontend em Vue.

### Stack principal
- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Vue 3 + Vite + TailwindCSS
- **Admin:** Filament
- **Banco de dados:** MySQL 8+ / MariaDB compatível
- **Autenticação/API:** Sanctum + JWT
- **Mensageria em tempo real:** Pusher (quando habilitado)

---

## 🧭 Estrutura do repositório
O código executável da aplicação Laravel está em:

```bash
extraia para public_html/public_html
```

> A menos que indicado, todos os comandos deste guia devem ser executados nessa pasta.

---

## ✅ Pré-requisitos

### Ferramentas obrigatórias
- Git
- PHP 8.1 ou superior
- Composer 2+
- Node.js 18+
- npm
- MySQL 8+ (ou MariaDB compatível)

### Extensões PHP exigidas/recomendadas
- curl
- intl
- libxml
- simplexml
- zip
- mbstring
- openssl
- pdo_mysql
- fileinfo
- gd
- bcmath

### Comandos para validar ambiente
```bash
php -v
composer -V
node -v
npm -v
```

---

## ⚙️ Instalação local completa

## 1) Acesse a pasta da aplicação
```bash
cd "extraia para public_html/public_html"
```

## 2) Crie o arquivo de ambiente
```bash
cp .env.example .env
```

## 3) Instale dependências PHP
```bash
composer install
```

## 4) Gere a chave da aplicação
```bash
php artisan key:generate
```

## 5) Instale dependências JS
```bash
npm install
```

## 6) Configure o `.env`
Ajuste as variáveis básicas:

```env
APP_NAME="Casino Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casino
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

QUEUE_CONNECTION=sync
CACHE_DRIVER=file
SESSION_DRIVER=file
```

## 7) Rode as migrações
```bash
php artisan migrate
```

## 8) Gere o link do storage
```bash
php artisan storage:link
```

## 9) Limpe caches antigos
```bash
php artisan optimize:clear
```

---

## ▶️ Execução em desenvolvimento

Use dois terminais na pasta da aplicação.

### Terminal A — backend Laravel
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
A aplicação backend ficará em: `http://127.0.0.1:8000`

### Terminal B — frontend Vite (HMR)
```bash
npm run dev -- --host 127.0.0.1 --port 5173
```
Servidor de assets/HMR: `http://127.0.0.1:5173`

---

## 🧪 Verificações rápidas pós-instalação
```bash
php artisan about
php artisan route:list
php artisan migrate:status
```

Se todos executarem sem erro, o ambiente está funcional.

---

## 🏗️ Build de frontend

### Build padrão
```bash
npm run build
```

### Limpar e recompilar assets
```bash
npm run clean
npm run build
```

---

## 🚀 Deploy em produção

Dentro da pasta da aplicação no servidor:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Variáveis críticas para produção
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://seu-dominio`
- `LOG_LEVEL=error` (ou conforme sua estratégia)

### Requisitos operacionais
- HTTPS ativo e redirecionamento HTTP → HTTPS
- Permissão de escrita em `storage/` e `bootstrap/cache/`
- Backup automatizado do banco
- Monitoramento de logs e alertas

---

## ⏱️ Filas e tarefas agendadas

### Worker de fila (exemplo)
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Scheduler no cron
Adicione ao `crontab`:

```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

---

## 🔐 Segurança mínima recomendada
- Nunca versionar o arquivo `.env`
- Usar senhas fortes e rotação periódica de credenciais
- Restringir acesso ao painel administrativo por IP/VPN quando possível
- Revisar permissões de arquivos e dono do processo web
- Atualizar dependências regularmente (`composer outdated` / `npm outdated`)

---

## 🧰 Troubleshooting (problemas comuns)

## 1) `vendor/autoload.php` não encontrado
```bash
composer install
```

## 2) Erro de chave de aplicação (`No application encryption key has been specified`)
```bash
php artisan key:generate
php artisan config:clear
```

## 3) Erro de conexão com banco
- Verifique `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` no `.env`
- Confirme se MySQL/MariaDB está ativo
- Teste acesso ao banco com as mesmas credenciais

## 4) Erro de permissão (`Permission denied` em `storage` ou `bootstrap/cache`)
Garanta escrita para o usuário do servidor web:
```bash
chmod -R ug+rw storage bootstrap/cache
```

## 5) Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

## 6) Rotas/configuração antigas em cache
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📚 Documentação complementar
Para material adicional de operação e suporte, consulte também `DOCUMENTACAO.md`.
