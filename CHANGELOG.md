# CHANGELOG.md

## v8.1.0

### Pagamentos
- Novos gateways: Mercado Pago, PagSeguro/PagBank, Pagar.me (v5), Asaas e Stripe — PIX e cartão (ver `PAGAMENTOS.md`).
- Removidos SuitPay, BsPay/PixUp, DigitoPay, SharkPay, Ezzepay e PayPal (código, rotas e telas do painel).
- Stripe corrigido: webhook com verificação de assinatura, secret key não é mais exposta, centavos não são truncados e o crédito passa pelo fluxo padrão (bônus, rollover, CPA).
- Crédito de depósito idempotente e confirmado na API do gateway (antes o polling mostrava "sucesso" para qualquer resposta).
- Saques: aprovação com PIX automático via Asaas ou marcação manual; cancelamento devolve o saldo.
- Botão "Atualizar dados" da tela de gateways voltou a salvar.
- Credenciais de terceiros removidas do dump `elitecassino.sql`.

### Jogos locais
- Treasures of Aztec: arquivo de resultados corrompido (bytes binários) impedia o jogo de girar.
- Queen of Bounty: erro fatal (argumento faltando) em todo giro.
- Queen of Bounty e Treasures of Aztec: vitórias pagavam R$ 0 (payout nulo).
- Rodadas grátis (freenum) davam erro fatal em todos os jogos.
- Fortune OX usava os resultados do Fortune Tiger; Hood vs Wolf usava a tabela de pagamentos do Tiger.
- Prêmios não eram creditados com rollover ativo (parâmetros trocados no histórico) e o afiliado não recebia revshare.
- O número de linhas enviado pelo navegador permitia apostar 1/5 do valor e receber o prêmio cheio.
- Saldo exibido após o giro já inclui o prêmio; agente demo joga só com saldo demo.
- Candy, Subway, Dino e Fruit Ninja: aposta debitada de qualquer carteira pelo `user_id` do navegador, centavos truncados e prêmios nunca pagos. Agora usam `/api/skillgames/start|finish` com rodada única e prêmio limitado à meta.
- Removidos scripts PHP soltos e inseguros em `public/originals` (SQL injection, credenciais de outro banco).

## v8.0.0

- Padronização profissional da documentação de instalação.
- Novo instalador automático com menu interativo e modos de execução.
- Inclusão de scripts de manutenção e diagnóstico operacional.
- Criação dos guias: deploy, cPanel, segurança e troubleshooting.
- Atualização do guia do instalador automático.
