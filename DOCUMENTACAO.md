# Casino Platform — Documentação Profissional

## Visão geral
Esta aplicação é uma plataforma de cassino construída em **Laravel 10** com frontend em **Vite + Vue 3**. O projeto inclui painel administrativo, módulos, integrações de pagamento e infraestrutura de filas/eventos para fluxos assíncronos.

---

## Sumário
1. [Arquitetura e stack](#arquitetura-e-stack)
2. [Pré-requisitos](#pré-requisitos)
3. [Instalação local (desenvolvimento)](#instalação-local-desenvolvimento)
4. [Configuração de ambiente (.env)](#configuração-de-ambiente-env)
5. [Banco de dados e migrações](#banco-de-dados-e-migrações)
6. [Build de frontend](#build-de-frontend)
7. [Execução da aplicação](#execução-da-aplicação)
8. [Deploy em produção](#deploy-em-produção)
9. [Filas, agendador e cache](#filas-agendador-e-cache)
10. [Segurança e boas práticas](#segurança-e-boas-práticas)
11. [Comandos úteis](#comandos-úteis)
12. [Troubleshooting](#troubleshooting)

---

## Arquitetura e stack
- **Backend:** PHP 8.1+ / Laravel 10
- **Frontend:** Vite, Vue 3, TailwindCSS
- **Banco:** MySQL/MariaDB
- **Auth/API:** Laravel Sanctum e JWT
- **Painel administrativo:** Filament
- **Integrações importantes:** Mercado Pago, Asaas, Stripe e módulos auxiliares

Diretório principal da aplicação Laravel:

```bash
extraia para public_html/public_html
```

---

## Pré-requisitos
Garanta que o servidor/ambiente tenha:

- PHP **8.1+**
- Composer **2+**
- Node.js **18+** e npm
- MySQL 8+ (ou MariaDB compatível)
- Git

### Extensões PHP obrigatórias
- `curl`
- `intl`
- `libxml`
- `simplexml`
- `zip`
- (recomendadas para Laravel: `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `gd`, `bcmath`)

---

## Instalação local (desenvolvimento)
No diretório raiz do repositório:

```bash
cd "extraia para public_html/public_html"
cp .env.example .env
composer install
php artisan key:generate
npm install
```

Depois, configure o banco no `.env` e execute:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

Para desenvolvimento frontend:

```bash
npm run dev
```

---

## Configuração de ambiente (.env)
Parâmetros mínimos que você deve ajustar:

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

### Recomendações por ambiente
- **Desenvolvimento:** `APP_DEBUG=true`
- **Produção:** `APP_DEBUG=false`
- Configure `QUEUE_CONNECTION`, `CACHE_DRIVER` e `SESSION_DRIVER` conforme infraestrutura.

---

## Banco de dados e migrações
### Cenário A — instalação limpa
```bash
php artisan migrate
```

### Cenário B — base legada (dump SQL)
1. Criar banco vazio.
2. Importar o dump legado.
3. Ajustar `.env` para apontar ao banco importado.
4. Rodar migrações pendentes para sincronizar estrutura:

```bash
php artisan migrate
```

---

## Build de frontend
Para compilar assets otimizados:

```bash
npm run build
```

Se houver necessidade de limpeza de assets:

```bash
npm run clean
```

---

## Execução da aplicação
### Desenvolvimento
Em terminais separados:

```bash
php artisan serve
npm run dev
```

### Testes e validações básicas
```bash
php artisan route:list
php artisan about
```

---

## Deploy em produção
No servidor, dentro da pasta da aplicação:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Permissões e links
```bash
php artisan storage:link
```

Garanta permissões de escrita em `storage/` e `bootstrap/cache/`.

---

## Filas, agendador e cache
### Worker de filas
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Scheduler (cron)
Adicionar no crontab:

```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

### Limpeza geral de cache
```bash
php artisan optimize:clear
```

---

## Segurança e boas práticas
- Use **HTTPS** obrigatório em produção.
- Nunca versionar `.env` com dados sensíveis.
- Configure chaves e webhooks dos gateways no painel admin.
- Monitore `storage/logs/laravel.log` regularmente.
- Restrinja acesso ao painel administrativo por política de senha forte e controle de permissões.

---

## Comandos úteis
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
php artisan config:cache
php artisan view:cache
```

---

## Troubleshooting
### Erro `vendor/autoload.php` não encontrado
```bash
composer install
```

### Frontend sem atualizar
```bash
npm install
npm run build
php artisan optimize:clear
```

### Erro de conexão com banco
- Verifique `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Confirme se o serviço MySQL está ativo.

### Erro de permissão em cache/storage
- Ajuste dono/grupo da aplicação e permissões de escrita em `storage/` e `bootstrap/cache/`.

---

## Observação final
Se desejar, eu também posso criar uma versão desta documentação com seção de **CI/CD (GitHub Actions)**, **Nginx/Apache pronto para produção** e **checklist de go-live**.
