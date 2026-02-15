# Casino Platform (Laravel)

Guia técnico completo para instalar, configurar e operar a plataforma com **Mercado Pago** e **Asaas** no painel admin.

---

## 1) Diretório correto do projeto

Use esta pasta como raiz da aplicação:

```bash
cd "extraia para public_html/public_html"
```

---

## 2) Pré-requisitos

- PHP 8.2+
- Composer 2+
- Node.js 18+ / npm
- MySQL 8+ ou MariaDB compatível
- Extensões PHP: `mbstring`, `openssl`, `pdo_mysql`, `bcmath`, `intl`, `curl`, `gd`, `xml`, `fileinfo`

---

## 3) Instalação limpa (recomendado)

```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
```

Configurar `.env`:
- `APP_URL=https://seu-dominio.com`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Executar:

```bash
php artisan migrate
php artisan optimize:clear
php artisan storage:link
```

---

## 4) Instalação com SQL legado

Dump disponível em:
- `INSTALL CASSINO/elitecassino.sql`

Fluxo:
1. Criar banco vazio.
2. Importar SQL.
3. Ajustar `.env` para esse banco.
4. Rodar migrações pendentes:
   ```bash
   php artisan migrate
   ```
5. Limpar cache:
   ```bash
   php artisan optimize:clear
   ```

> Importante: mesmo com SQL importado, rode `migrate` para sincronizar campos novos de gateways.

---

## 5) Produção (resumo)

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Também configurar worker de fila (`queue:work`) e cron (`schedule:run`) quando necessário.

---

## 6) Painel admin e segurança inicial

- URL admin padrão: `/admin`
- Trocar imediatamente senha padrão do administrador.
- Garantir `APP_DEBUG=false` em produção.
- Usar HTTPS obrigatório.

---

## 7) Configuração de gateway via painel admin

No painel admin, menu **Gateway de Pagamentos**:

### Mercado Pago
- `mp_is_enable`
- `mp_credit_card_enable`
- `mp_sandbox`
- `mp_access_token`
- `mp_public_key`
- `mp_webhook_key`

### Asaas
- `asaas_is_enable`
- `asaas_credit_card_enable`
- `asaas_sandbox`
- `asaas_api_key`
- `asaas_webhook_key`

As URLs de callback aparecem na própria tela:
- `/mercadopago/callback`
- `/asaas/callback`

---

## 8) API de depósito (PIX e cartão)

Endpoint:
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

Regras:
- `amount` respeita `min_deposit` e `max_deposit`.
- Cartão exige `*_credit_card_enable=true` no gateway selecionado.

---

## 9) Webhooks

Rotas:
- `POST /mercadopago/callback`
- `POST /mercadopago/payment/{id}`
- `POST /asaas/callback`
- `POST /asaas/payment`

Checklist:
1. URL pública HTTPS.
2. Callback cadastrado no provedor.
3. Chave de webhook configurada no admin (quando usada).
4. Verificação de logs do Laravel para confirmação de recebimento.

---

## 10) Checklist final de validação

### Backend
- `php artisan optimize:clear`
- Confirmar migrations aplicadas.
- Criar depósito PIX em MP e Asaas.
- Testar cartão com flag habilitada.
- Confirmar crédito na carteira.

### Frontend
- Login admin e menu de gateways carregando.
- Tela de depósito exibindo gateways conforme settings.
- Fluxo de QR Code/checkout sem erro JS.

### Jogos/provedores
- Validar chaves dos provedores de jogo.
- Abrir jogo autenticado.
- Confirmar reconciliação financeira em logs/tabelas.

---

## 11) Troubleshooting rápido

### `vendor/autoload.php` ausente
Rode:
```bash
composer install
```

### Gateway não aparece no frontend
- Verifique `settings` (`mercadopago_is_enable`, `asaas_is_enable`).
- Limpe cache: `php artisan optimize:clear`.

### Callback não credita saldo
- Conferir URL de webhook no provedor.
- Conferir chave/assinatura configurada.
- Inspecionar logs (`storage/logs/laravel.log`).

---

## 12) Comandos úteis

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
```
