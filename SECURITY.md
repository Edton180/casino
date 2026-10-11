# 🔒 Segurança

## O que a plataforma já faz
- Instalador bloqueado após a instalação (`storage/installed.lock`) e protegido por token durante as etapas.
- `.htaccess` da raiz bloqueia `.env`, `storage`, `vendor`, `database` e demais arquivos internos na hospedagem compartilhada.
- `.env` gravado com `APP_DEBUG=false`, chaves `APP_KEY`/`JWT_SECRET` aleatórias e permissão 640.
- Depósitos: o crédito só ocorre após consultar o status na API do gateway e conferir o valor pago; webhooks com assinatura verificada (Stripe, PayPal, Mercado Pago opcional, PagSeguro, Asaas por token, Pagar.me por Basic Auth). Crédito idempotente (sem duplicidade).
- Carteiras com transações InnoDB e travas de linha (sem saldo duplicado em requisições simultâneas).
- Jogos: resultado sempre calculado no servidor; aposta/linhas validadas; rodadas pagas uma única vez.
- Dados de cartão nunca passam pelo servidor (checkout hospedado dos gateways).

## Checklist do operador
- [ ] HTTPS obrigatório e `APP_URL` com `https://`.
- [ ] `APP_ENV=production` e `APP_DEBUG=false`.
- [ ] Senha forte e exclusiva no administrador; não compartilhe o login.
- [ ] Backup diário do banco (VPS: `scripts/casino-backup`; cPanel: *Backup*).
- [ ] Mantenha o sistema atualizado (`auto-install.sh --update` ou novo pacote).
- [ ] Firewall: libere só 22, 80 e 443 (VPS).
- [ ] Revise periodicamente `storage/logs/laravel.log`.

## Reportar vulnerabilidade
Abra uma *issue* privada (Security advisory) no GitHub.
