# 🎰 Casino Platform — Documentação técnica completa

Este guia foi feito para servir como documentação principal de operação do projeto **Casino Platform**, cobrindo instalação, desenvolvimento, deploy, manutenção e diagnóstico.

> **Importante:** o código da aplicação Laravel está dentro da pasta `extraia para public_html/public_html`. Salvo indicação contrária, execute os comandos nessa pasta.

---

## 1) Visão geral da aplicação

A Casino Platform é uma aplicação web para operação de cassino online com:
- API/backend em Laravel
- frontend em Vue com Vite
- painel administrativo com Filament
- autenticação via Sanctum + JWT
- suporte a filas, scheduler e integrações externas (pagamentos, e-mail e realtime quando habilitado)

### Stack principal
- **Backend:** PHP 8.1+ / Laravel 10
- **Frontend:** Vue 3 + Vite + TailwindCSS
- **Banco de dados:** MySQL 8+ ou MariaDB compatível
- **Admin:** Filament
- **Mensageria (opcional):** Pusher

---

## 2) Estrutura de diretórios

Raiz do repositório:

```bash
/workspace/casino
```

Diretório executável da aplicação:

```bash
/workspace/casino/extraia para public_html/public_html
```

Exemplos importantes (relativos ao diretório da aplicação):
- `app/` → regras de negócio e serviços
- `routes/` → rotas web e API
- `resources/` → views e assets de frontend
- `database/` → migrations/seeders/factories
- `storage/` → logs, cache e arquivos gerados
- `bootstrap/cache/` → cache de configuração/rotas

---

## 3) Pré-requisitos

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

### 3.3 Verificação rápida do ambiente
```bash
php -v
composer -V
node -v
npm -v
```

---

## 4) Instalação local (passo a passo)

### 4.1 Entrar na pasta da aplicação
```bash
cd "extraia para public_html/public_html"
```

### 4.2 Criar arquivo de ambiente
```bash
cp .env.example .env
```

### 4.3 Instalar dependências
```bash
composer install
npm install
```

### 4.4 Inicializar Laravel
```bash
php artisan key:generate
php artisan storage:link
php artisan optimize:clear
```

### 4.5 Configurar `.env`
Exemplo base:

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

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### 4.6 Criar estrutura de banco
```bash
php artisan migrate
```

> Se o projeto possuir seeders e você quiser dados iniciais para desenvolvimento, execute também:
>
> ```bash
> php artisan db:seed
> ```

---

## 5) Executar em desenvolvimento

Use dois terminais dentro da pasta da aplicação.

### Terminal A — backend Laravel
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Acesso: `http://127.0.0.1:8000`

### Terminal B — frontend (Vite/HMR)
```bash
npm run dev -- --host 127.0.0.1 --port 5173
```
HMR/Assets: `http://127.0.0.1:5173`

### Validação de saúde local
```bash
php artisan about
php artisan route:list
php artisan migrate:status
```

---

## 6) Banco de dados e migrações

### 6.1 Cenário A — banco novo
```bash
php artisan migrate
```

### 6.2 Cenário B — banco legado (import SQL)
1. Criar banco vazio.
2. Importar dump SQL.
3. Atualizar `.env` com credenciais corretas.
4. Rodar:

```bash
php artisan migrate
```

### 6.3 Operações úteis
```bash
php artisan migrate:status
php artisan schema:dump
```

---

## 7) Build e entrega

### 7.1 Build de frontend
```bash
npm run build
```

### 7.2 Rebuild completo de assets
```bash
npm run clean
npm run build
```

### 7.3 Fluxo recomendado para deploy em produção
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

### 8.1 Configurações obrigatórias
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://seu-dominio`
- credenciais reais de banco, cache, e-mail e gateways

### 8.2 Segurança
- HTTPS obrigatório
- `.env` fora de versionamento
- senhas fortes + rotação periódica
- princípio do menor privilégio para usuários e chaves de integração
- acesso ao admin protegido (IP allowlist, VPN ou SSO quando possível)

### 8.3 Performance/estabilidade
- OPcache habilitado
- workers de fila gerenciados com Supervisor/systemd
- scheduler ativo no cron
- logs com rotação
- backups automáticos e testados

---

## 9) Filas, scheduler e operação contínua

### 9.1 Worker de fila (manual)
```bash
php artisan queue:work --tries=3 --timeout=120
```

### 9.2 Scheduler no cron
```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

### 9.3 Reiniciar workers após deploy
```bash
php artisan queue:restart
```

### 9.4 Limpar caches com segurança
```bash
php artisan optimize:clear
```

---

## 10) Rotina de manutenção

### 10.1 Antes de publicar nova versão
- Garantir backup do banco
- Revisar variáveis do `.env`
- Rodar build local e validações
- Confirmar janela de manutenção (se necessário)

### 10.2 Depois do deploy
- Validar login e páginas críticas
- Testar fluxo de depósito/saque em sandbox
- Verificar fila e scheduler
- Inspecionar logs por erros novos

### 10.3 Comandos úteis de manutenção
```bash
php artisan about
php artisan queue:monitor
php artisan config:cache
php artisan route:list
```

---

## 11) Troubleshooting (erros comuns)

### 11.1 `vendor/autoload.php` não encontrado
```bash
composer install
```

### 11.2 `No application encryption key has been specified`
```bash
php artisan key:generate
php artisan config:clear
```

### 11.3 Falha de conexão com banco
- revisar `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- confirmar serviço MySQL/MariaDB ativo
- testar conexão com as mesmas credenciais do `.env`

### 11.4 Erro de permissão em `storage`/`bootstrap/cache`
```bash
chmod -R ug+rw storage bootstrap/cache
```

### 11.5 Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

### 11.6 Aplicação usando rotas/config antigas
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 12) Boas práticas para equipe

- Registrar mudanças de infraestrutura e `.env` em changelog interno.
- Sempre testar em homologação antes de produção.
- Evitar deploy direto sem validação de migração.
- Monitorar erros de pagamento e notificações de webhook.
- Definir plano de rollback para cada release.

---

## 13) Referência rápida (comandos essenciais)

```bash
# setup
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
php artisan storage:link

# desenvolvimento
php artisan serve --host=127.0.0.1 --port=8000
npm run dev -- --host 127.0.0.1 --port 5173

# produção
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

# diagnóstico
php artisan about
php artisan route:list
php artisan migrate:status
php artisan optimize:clear
```
