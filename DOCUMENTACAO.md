# Casino Platform — Documentação completa (instalação, operação e suporte)

Este documento é uma versão detalhada para times técnicos e também para pessoas iniciantes.
Se você quer apenas começar rápido, leia primeiro o `README.md`.

---

## 1) Visão geral
A Casino Platform é uma aplicação Laravel com frontend Vue, preparada para:
- Gestão de operações de cassino
- Painel administrativo
- Integrações de pagamento
- Processamento assíncrono com filas
- Execução de tarefas agendadas

**Stack principal**
- Backend: PHP 8.1+ / Laravel 10
- Frontend: Vue 3 + Vite + TailwindCSS
- Banco: MySQL/MariaDB
- Auth/API: Sanctum + JWT
- Admin: Filament

---

## 2) Estrutura de diretórios esperada
A pasta principal da aplicação Laravel está em:

```bash
extraia para public_html/public_html
```

Na maioria dos cenários, todos os comandos serão executados nessa pasta.

---

## 3) Pré-requisitos detalhados

### 3.1 Ferramentas obrigatórias
- Git
- PHP 8.1+
- Composer 2+
- Node.js 18+
- npm
- MySQL 8+ (ou MariaDB compatível)

### 3.2 Extensões PHP recomendadas
- `curl`
- `intl`
- `libxml`
- `simplexml`
- `zip`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `fileinfo`
- `gd`
- `bcmath`

### 3.3 Verificações úteis
```bash
php -v
composer -V
node -v
npm -v
```

---

## 4) Instalação local completa (do zero)

## Passo 1 — entrar no diretório da aplicação
```bash
cd "extraia para public_html/public_html"
```

## Passo 2 — criar o arquivo de ambiente
```bash
cp .env.example .env
```

## Passo 3 — instalar dependências PHP
```bash
composer install
```

## Passo 4 — gerar chave da aplicação
```bash
php artisan key:generate
```

## Passo 5 — instalar dependências JS
```bash
npm install
```

## Passo 6 — configurar banco no `.env`
Exemplo:

```env
APP_NAME="Casino Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casino
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

## Passo 7 — rodar migrações
```bash
php artisan migrate
```

## Passo 8 — criar link simbólico de storage
```bash
php artisan storage:link
```

## Passo 9 — limpar caches
```bash
php artisan optimize:clear
```

---

## 5) Modos de execução local

### 5.1 Modo simples (mais fácil)
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Acesso: `http://127.0.0.1:8000`

### 5.2 Modo com frontend separado (HMR)
Em outro terminal:
```bash
npm run dev -- --host 127.0.0.1 --port 5173
```

### 5.3 Usando Apache/Nginx local
- `DocumentRoot` apontando para `.../public`
- Ajustar `APP_URL`
- Garantir permissão em `storage/` e `bootstrap/cache/`

---

## 6) Banco de dados e cenários de migração

### Cenário A — banco novo
```bash
php artisan migrate
```

### Cenário B — banco legado (dump SQL)
1. Criar um banco vazio.
2. Importar dump SQL antigo.
3. Ajustar `.env` com os dados corretos.
4. Rodar migrações para completar estrutura:

```bash
php artisan migrate
```

---

## 7) Build e publicação

### 7.1 Build frontend
```bash
npm run build
```

### 7.2 Limpeza de assets (quando necessário)
```bash
npm run clean
npm run build
```

### 7.3 Deploy em produção
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 8) Checklist de produção

### 8.1 Variáveis críticas
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://seu-dominio`

### 8.2 Segurança
- HTTPS ativo
- Não versionar `.env`
- Senhas fortes e acesso restrito ao admin
- Chaves e webhooks configurados corretamente

### 8.3 Operação contínua
- Worker de fila rodando de forma persistente
- Cron ativo para scheduler
- Backup automático do banco
- Monitoramento de logs

---

## 9) Filas, scheduler e manutenção

### 9.1 Worker de fila
```bash
php artisan queue:work --tries=3 --timeout=120
```

### 9.2 Scheduler no cron
```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

### 9.3 Limpeza de cache
```bash
php artisan optimize:clear
```

---

## 10) Comandos de diagnóstico
```bash
php artisan about
php artisan route:list
php artisan config:cache
php artisan view:cache
php artisan migrate --force
```

---

## 11) Troubleshooting (erros comuns)

### Erro: `vendor/autoload.php` não encontrado
```bash
composer install
```

### Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

### Falha de conexão com banco
- Conferir credenciais no `.env`
- Confirmar se MySQL/MariaDB está ativo
- Validar porta (`3306` por padrão)

### Permissão negada em cache/storage
- Ajustar proprietário/grupo do projeto
- Garantir escrita em `storage/` e `bootstrap/cache/`

---

## 12) Boas práticas para quem está começando
- Execute um comando por vez e leia o erro com calma.
- Se um comando falhar, resolva antes de ir para o próximo.
- Mantenha um backup do `.env` antes de alterar configurações críticas.
- Faça deploy primeiro em ambiente de homologação antes de produção.
