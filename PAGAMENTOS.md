# 💳 Pagamentos

A plataforma trabalha com cinco gateways atuais. Todos usam o mesmo fluxo de
depósito (bônus de primeiro depósito, rollover, VIP e CPA de indicação).

| Gateway | PIX | Cartão de crédito | Saque automático |
|---|---|---|---|
| Mercado Pago | ✅ API de Pagamentos | ✅ Checkout Pro | — |
| PagSeguro / PagBank | ✅ API de Pedidos | ✅ Checkout PagBank | — |
| Pagar.me (API v5) | ✅ Pedidos | ✅ Checkout Pagar.me | — |
| Asaas | ✅ Cobrança PIX | ✅ Fatura Asaas | ✅ Transferência PIX |
| Stripe | ✅ (contas BR, opcional) | ✅ Stripe Checkout | — |

SuitPay, BsPay/PixUp, DigitoPay, SharkPay, Ezzepay e PayPal foram removidos.

## Configuração

1. Rode as migrations: `php artisan migrate --force`.
2. No painel: **Pagamentos > Gateway de Pagamentos** — informe as credenciais e
   ative o gateway (e o cartão, se quiser).
3. Cadastre o webhook no painel de cada gateway:

| Gateway | URL do webhook | Eventos |
|---|---|---|
| Mercado Pago | `https://SEU-DOMINIO/webhooks/mercadopago` | Pagamentos |
| PagSeguro | `https://SEU-DOMINIO/webhooks/pagseguro` | enviado automaticamente em cada cobrança |
| Pagar.me | `https://SEU-DOMINIO/webhooks/pagarme` | `order.paid`, `charge.paid` |
| Asaas | `https://SEU-DOMINIO/webhooks/asaas` | `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED` |
| Stripe | `https://SEU-DOMINIO/webhooks/stripe` | `checkout.session.completed`, `checkout.session.async_payment_succeeded` |

As URLs antigas `/mercadopago/callback` e `/asaas/callback` continuam funcionando.

> **Stripe:** o *Signing secret* do webhook (`whsec_...`) é obrigatório. Sem ele
> os depósitos via webhook não são creditados (o polling da tela continua funcionando).

## Segurança

- O webhook só indica *qual* depósito verificar: o crédito acontece apenas após
  consultar o status na API do gateway e conferir o valor pago.
- O crédito é idempotente (webhook e polling podem chegar juntos sem duplicar saldo).
- Dados de cartão nunca passam pelo servidor: o cartão é pago no checkout
  hospedado de cada gateway.
- Credenciais nunca são enviadas ao navegador (a integração antiga do Stripe
  expunha a *secret key* publicamente).

## Saques

Em **Saques**, o botão *Fazer pagamento*:
- envia o PIX automaticamente pelo Asaas, se **"Pagar saques via PIX Asaas"** estiver ativo;
- caso contrário, apenas marca o saque como pago (faça a transferência manualmente).

*Cancelar* devolve o valor para a carteira de saque do usuário.

## API (frontend)

| Método | Rota | Descrição |
|---|---|---|
| GET | `/api/wallet/deposit/methods` | métodos habilitados |
| POST | `/api/wallet/deposit/payment` | `gateway`, `payment_method` (`pix`/`credit_card`), `amount`, `cpf`, `accept_bonus` |
| POST | `/api/wallet/deposit/status` | `idTransaction` → `PAID`, `PENDING` ou `FAILED` |
