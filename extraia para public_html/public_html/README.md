# Casino Platform (Laravel) — Guia Profissional de Instalação, Operação e Pagamentos

Este documento consolida o processo completo de **setup**, **deploy**, **operação** e **integração de pagamentos** da aplicação.

> Diretório da aplicação Laravel no repositório:
>
> ```bash
> cd "extraia para public_html/public_html"
> ```

---

## 1) Visão geral técnica

- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Vue 3 + Vite + Tailwind
- **Banco:** MySQL 8+ ou MariaDB compatível
- **Auth/API:** Sanctum + JWT
- **Admin:** Filament
- **Pagamentos:** Mercado Pago, Asaas, Stripe (dependências presentes no projeto)

---

## 2) Pré-requisitos obrigatórios

## Sistema
- PHP **8.1+**
- Composer **2+**
- Node.js **18+** + npm
- MySQL/MariaDB
- Git

## Extensões PHP obrigatórias
Conforme dependências do projeto:
- `ext-curl`
- `ext-intl`
- `ext-libxml`
- `ext-simplexml`
- `ext-zip`

Recomendadas para Laravel:
- `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `gd`, `bcmath`

### Verificação rápida de ambiente
```bash
php -v
composer -V
node -v
npm -v
php -m
```

---

## 3) Instalação limpa (recomendada)

1. Entrar no diretório da aplicação:
```bash
cd "extraia para public_html/public_html"
```

2. Criar arquivo de ambiente e instalar dependências:
```bash
cp .env.example .env
composer install
npm install
```

3. Gerar chave da aplicação e preparar banco:
```bash
php artisan key:generate
php artisan migrate
```

4. Link de storage e limpeza de cache:
```bash
php artisan storage:link
php artisan optimize:clear
```

5. Build frontend:
```bash
npm run build
```

---

## 4) Configuração do `.env` (mínimo funcional)

Exemplo recomendado:

```env
APP_NAME="Casino Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casino
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Produção (obrigatório)
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://seu-dominio.com`
- Ajustar `QUEUE_CONNECTION` (ex.: redis/database)

---

## 5) Instalação com base legada (SQL)

Dump disponível em:
- `INSTALL CASSINO/elitecassino.sql`

Fluxo recomendado:
1. Criar banco vazio.
2. Importar o dump SQL.
3. Atualizar credenciais do banco no `.env`.
4. Rodar migrações pendentes para atualizar esquema:
```bash
php artisan migrate
```
5. Limpar cache:
```bash
php artisan optimize:clear
```

---

## 6) Execução em desenvolvimento

Executar backend e frontend em paralelo:

```bash
php artisan serve
npm run dev
```

Checks úteis:

```bash
php artisan about
php artisan route:list
```

---

## 7) Deploy em produção (passo a passo)

Dentro do servidor, no diretório da aplicação:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Permissões
Garantir escrita em:
- `storage/`
- `bootstrap/cache/`

### Pós-deploy recomendado
```bash
php artisan optimize:clear
php artisan config:cache
```

---

## 8) Filas, scheduler e serviços contínuos

### Worker de fila
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Cron do scheduler
Adicionar no crontab:
```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

### Recomendação operacional
- Em produção, use supervisor/systemd para manter `queue:work` ativo.

---

## 9) Pagamentos — configuração correta (melhorada)

Ajuste todos os gateways pelo painel administrativo antes de liberar para clientes.

## Mercado Pago
Campos essenciais:
- `mp_is_enable`
- `mp_credit_card_enable`
- `mp_sandbox`
- `mp_access_token`
- `mp_public_key`
- `mp_webhook_key`

## Asaas
Campos essenciais:
- `asaas_is_enable`
- `asaas_credit_card_enable`
- `asaas_sandbox`
- `asaas_api_key`
- `asaas_webhook_key`

### Boas práticas de configuração
- Use ambiente **sandbox** para homologação (`*_sandbox=true`).
- Só habilite cartão (`*_credit_card_enable`) após validar antifraude e fluxos de estorno.
- Cadastre callbacks com URL HTTPS pública.
- Nunca exponha tokens/chaves no frontend.

### Endpoints de callback/webhook
- `POST /mercadopago/callback`
- `POST /mercadopago/payment/{id}`
- `POST /asaas/callback`
- `POST /asaas/payment`

### Checklist de homologação de pagamentos
1. Gateway habilitado no admin.
2. Chaves corretas e sem espaços extras.
3. Callback cadastrado no provedor com HTTPS.
4. Transação PIX aprovada em sandbox.
5. Transação cartão aprovada em sandbox (se habilitado).
6. Confirmação de crédito em carteira.
7. Validação de logs sem exceções.

---

## 10) Fluxos de API de depósito (referência)

Endpoint principal:
- `POST /api/wallet/deposit/payment`

### Exemplo PIX
```json
{
  "gateway": "mercadopago",
  "payment_method": "pix",
  "amount": 50,
  "cpf": "00000000000"
}
```

### Exemplo cartão
```json
{
  "gateway": "asaas",
  "payment_method": "credit_card",
  "amount": 50,
  "cpf": "00000000000",
  "credit_card": {
    "number": "4111111111111111",
    "name": "NOME TESTE",
    "expiry": "12/30",
    "cvc": "123"
  }
}
```

### Regras de negócio importantes
- `amount` deve respeitar limites (`min_deposit` e `max_deposit`).
- Cartão só funciona se `*_credit_card_enable=true` no gateway escolhido.
- Trate timeouts/retries no consumo de callback para evitar crédito duplicado.

---

## 11) Segurança (hardening)

- Forçar HTTPS e HSTS no servidor web.
- Bloquear acesso público a arquivos sensíveis.
- Rotacionar tokens/chaves periodicamente.
- Limitar tentativas de login no admin.
- Revisar permissões de usuários administrativos.
- Monitorar `storage/logs/laravel.log` diariamente.

---

## 12) Troubleshooting (erros comuns)

### `vendor/autoload.php` ausente
```bash
composer install
```

### Erro de chave da aplicação
```bash
php artisan key:generate
php artisan config:clear
```

### Frontend não carrega assets
```bash
npm install
npm run build
php artisan optimize:clear
```

### Erro de permissão em cache/storage
- Ajustar owner/group do deploy user.
- Garantir escrita em `storage/` e `bootstrap/cache/`.

### Callback não credita saldo
- Confirmar URL de webhook no provedor.
- Validar assinatura/chave de webhook.
- Conferir status real da cobrança no provedor.
- Ver logs em `storage/logs/laravel.log`.

### Depósito duplicado
- Revisar idempotência no processamento de webhook.
- Garantir que a confirmação financeira não execute duas vezes para o mesmo `transaction_id`.

---

## 13) Comandos úteis de operação

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
php artisan config:cache
php artisan view:cache
```

---

## 14) Checklist final antes de colocar em produção

- [ ] `.env` de produção revisado (`APP_DEBUG=false`).
- [ ] Banco atualizado e backup validado.
- [ ] Build frontend gerado com sucesso.
- [ ] Filas e cron ativos.
- [ ] Gateways testados em sandbox e produção.
- [ ] Webhooks recebendo/confirmando pagamentos.
- [ ] Monitoramento de logs ativo.

