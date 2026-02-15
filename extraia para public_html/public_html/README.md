# Casino Platform (Laravel) — Documentação Técnica Completa

Guia profissional para instalar, configurar, executar, publicar e operar a aplicação em desenvolvimento e produção.

---

# 1) Diretório correto do projeto

A raiz da aplicação Laravel está em:

```bash
cd "extraia para public_html/public_html"
```

---

# 2) Stack da aplicação

- Backend: Laravel 10 (PHP 8.1+)
- Frontend: Vue 3 + Vite + TailwindCSS
- Banco: MySQL 8+ ou MariaDB compatível
- Auth/API: Sanctum + JWT
- Admin: Filament
- Pagamentos: Mercado Pago, Asaas, Stripe

---

# 3) Pré-requisitos obrigatórios

## Sistema
- PHP 8.1+
- Composer 2+
- Node.js 18+
- npm
- MySQL 8+ ou MariaDB
- Git

## Extensões PHP obrigatórias
- ext-curl
- ext-intl
- ext-libxml
- ext-simplexml
- ext-zip

## Extensões recomendadas
- mbstring
- openssl
- pdo_mysql
- fileinfo
- gd
- bcmath

### Verificação rápida

```bash
php -v
composer -V
node -v
npm -v
php -m
```

---

# 4) Instalação limpa (RECOMENDADO)

```bash
cp .env.example .env
composer install
npm install
```

Editar o `.env`:

- APP_URL
- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

Depois executar:

```bash
php artisan key:generate
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

Build frontend:

```bash
npm run build
```

---

# 5) Exemplo mínimo de .env

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

## Produção (OBRIGATÓRIO)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-dominio.com
```

---

# 6) Instalação com base SQL legada

Dump disponível em:

INSTALL CASSINO/elitecassino.sql

Fluxo:

1. Criar banco vazio.
2. Importar o dump SQL.
3. Ajustar `.env` com as credenciais.
4. Rodar:

```bash
php artisan migrate
php artisan optimize:clear
```

---

# 7) Execução em desenvolvimento

Em terminais separados:

```bash
php artisan serve
npm run dev
```

Validações úteis:

```bash
php artisan route:list
php artisan about
```

---


## Acesso local (localhost)

### Opção 1: Laravel embutido
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Acesse `http://127.0.0.1:8000`.

### Opção 2: Vite com HMR
```bash
npm run dev -- --host 127.0.0.1 --port 5173
```
Use para desenvolvimento do frontend com recarregamento em tempo real.

### Opção 3: Apache/Nginx local
- DocumentRoot para `public/`
- `APP_URL=http://localhost`
- Permissões em `storage/` e `bootstrap/cache/`

---

# 8) Deploy em produção

No servidor:

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

Garantir permissões de escrita em:

- storage/
- bootstrap/cache/

---


## Checklist de produção (go-live)

1. Ambiente
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://seu-dominio.com`
2. Segurança
   - HTTPS obrigatório
   - Segredos apenas em variáveis seguras
   - Painel admin protegido (senha forte + 2FA se disponível)
3. Operação
   - Supervisor/systemd para fila
   - Cron do scheduler ativo
   - Monitoramento de logs e alertas
4. Performance
   - `config:cache`, `route:cache`, `view:cache`
   - Build frontend com `npm run build`
   - `composer install --no-dev --optimize-autoloader`

---

# 9) Filas e Scheduler

## Worker

```bash
php artisan queue:work --tries=3 --timeout=120
```

## Cron (obrigatório)

```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

Recomendado usar Supervisor ou systemd em produção.

---

# 10) Painel Admin e Segurança

- URL padrão: /admin
- Trocar senha padrão imediatamente
- Usar HTTPS obrigatório
- APP_DEBUG=false em produção
- Nunca expor chaves no repositório

---

# 11) Gateway de Pagamentos

Configurar no painel administrativo antes de liberar para clientes.

## Mercado Pago

Campos:

- mp_is_enable
- mp_credit_card_enable
- mp_sandbox
- mp_access_token
- mp_public_key
- mp_webhook_key

## Asaas

Campos:

- asaas_is_enable
- asaas_credit_card_enable
- asaas_sandbox
- asaas_api_key
- asaas_webhook_key

## Endpoints de callback

- POST /mercadopago/callback
- POST /mercadopago/payment/{id}
- POST /asaas/callback
- POST /asaas/payment

## Checklist de homologação

- Gateway habilitado
- Chaves corretas
- Webhook cadastrado com HTTPS
- PIX testado em sandbox
- Cartão testado em sandbox
- Saldo creditando corretamente
- Logs sem exceções

---

# 12) API de Depósito (Referência)

Endpoint:

POST /api/wallet/deposit/payment

## Exemplo PIX

```json
{
  "gateway": "mercadopago",
  "payment_method": "pix",
  "amount": 50,
  "cpf": "00000000000"
}
```

## Exemplo Cartão

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

Regras importantes:

- amount deve respeitar min_deposit e max_deposit
- Cartão só funciona se *_credit_card_enable=true
- Implementar idempotência para evitar crédito duplicado

---

# 13) Segurança (Hardening)

- Forçar HTTPS e HSTS
- Bloquear acesso a arquivos sensíveis
- Rotacionar chaves periodicamente
- Limitar tentativas de login no admin
- Monitorar storage/logs/laravel.log
- Validar assinatura dos webhooks

---

# 14) Troubleshooting

## vendor/autoload.php ausente

```bash
composer install
```

## Erro de chave da aplicação

```bash
php artisan key:generate
php artisan config:clear
```

## Frontend não carrega

```bash
npm install
npm run build
php artisan optimize:clear
```

## Erro de permissão

Garantir escrita em:

- storage/
- bootstrap/cache/

## Callback não credita saldo

- Conferir URL no provedor
- Validar chave webhook
- Conferir status real da cobrança
- Ver logs em storage/logs/laravel.log

## Banco não conecta

Verificar:

- DB_HOST
- DB_PORT
- DB_DATABASE
- DB_USERNAME
- DB_PASSWORD

---

# 15) Comandos úteis

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
php artisan config:cache
php artisan view:cache
```

---

# 16) Checklist Final Produção

- [ ] APP_DEBUG=false
- [ ] Banco atualizado e backup feito
- [ ] Build frontend gerado
- [ ] Filas funcionando
- [ ] Cron configurado
- [ ] Gateways testados
- [ ] Webhooks recebendo
- [ ] Logs monitorados

---

# 17) Banco de Dados — Operação segura para produção

## Charset e collation recomendados
Use UTF-8 completo para evitar falhas com acentos/emojis e inconsistência entre tabelas:

- charset: `utf8mb4`
- collation: `utf8mb4_unicode_ci` (ou `utf8mb4_0900_ai_ci` em MySQL 8)

## Estratégia de migração em produção
1. Fazer backup completo antes de qualquer alteração.
2. Executar migrações com `--force`.
3. Validar tabelas críticas: `users`, `wallets`, `orders`, `deposits`, `withdrawals`.
4. Confirmar índices em colunas de busca frequente (`user_id`, `transaction_id`, `status`).

Comandos sugeridos:

```bash
php artisan migrate --force
php artisan db:show
php artisan db:table users
php artisan db:table wallets
php artisan db:table orders
```

## Backup e restore (exemplo MySQL)
Backup:

```bash
mysqldump -uUSER -p --single-transaction --routines --triggers DB_NAME > backup_$(date +%F_%H%M).sql
```

Restore:

```bash
mysql -uUSER -p DB_NAME < backup_arquivo.sql
```

---

# 18) Funções críticas e regras de negócio (resumo operacional)

## Fluxo de saldo (Wallet)
- `balance`: saldo principal
- `balance_bonus`: saldo de bônus
- `balance_withdrawal`: saldo liberado para saque
- `total_balance`: soma operacional para consumo de APIs

## Fluxo de jogo/callback
- Callback de provedor deve:
  1. validar usuário e carteira ativa;
  2. aplicar débito/apuração de vitória com segurança;
  3. registrar histórico/transação;
  4. responder com payload consistente e sem vazar erro interno.

## Fluxo de gateway de pagamento
- Habilitar e validar gateway no painel.
- Exigir webhook com HTTPS.
- Garantir idempotência por `transaction_id`.
- Auditar logs em caso de divergência de saldo.

---

# 19) Auditoria de prontidão para produção (status atual)

## Melhorias aplicadas nesta revisão
- Callback `ApiPg12` sem `dd()` em exceções (evita parada de produção).
- Tratamento explícito para usuário/carteira inexistentes.
- Remoção de chamada externa de debug dentro do processamento do jogo.
- Logging estruturado de erro no callback para observabilidade.
- Correção de uso de variável de transação potencialmente indefinida.

## Itens obrigatórios antes do go-live
- [ ] Configurar Redis para `CACHE_DRIVER`, `SESSION_DRIVER` e `QUEUE_CONNECTION`.
- [ ] Rodar worker com Supervisor/systemd e restart automático.
- [ ] Definir política de backup diário e teste de restore.
- [ ] Ativar monitoramento de logs e alertas (5xx, exceptions, falha de webhook).
- [ ] Revisar credenciais e rotacionar chaves de produção.
- [ ] Validar rate-limit e proteção de rotas administrativas.

---

# 20) Comandos de validação pré-produção

```bash
php -v
php artisan about
php artisan route:list
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
php artisan optimize:clear
```

