# CHANGELOG.md

## v9.0.0

### Instalação
- Novo instalador web (`/install/`) independente do Laravel: requisitos, teste de banco, `.env`, banco inicial, administrador — testado em Apache (hospedagem compartilhada) e Nginx.
- Novo instalador de VPS (`scripts/auto-install.sh`): Nginx + PHP 8.3 + MariaDB + SSL + cron em um comando; modos `--update` e `--diagnose`.
- `php artisan system:install` reescrito (mesmas etapas, modo sem perguntas).
- Pacote pronto para hospedagem (vendor + frontend) gerado pelo GitHub Actions em *Releases*.
- `database/install/base.sql`: base limpa, sem dados pessoais nem credenciais de terceiros, todas as tabelas InnoDB.
- Correções que impediam instalações novas: admin criado sem o papel `admin` (não entrava no painel), `.user.ini` com `open_basedir` de outro servidor, `.htaccess` da raiz com regras de WordPress, Composer falhando no PHP 8.3, `package-lock.json` dessincronizado, `JWTMiddleWare` não carregando no Linux.

### Jogos
- Novos jogos originais: **Mines, Crash, Dice, Plinko e Double** (resultado no servidor, provably fair, RTP configurável).
- Capas locais para os jogos e placeholder para capas ausentes.
- Webhooks de KaGaming, Salsa, Shark Connect, Vibra, Fivers, WorldSlot, Games2Api e EverGame passaram a ter rota (antes o saldo desses provedores não era atualizado).
- Removidos `dd()` de depuração que interrompiam webhooks de provedores.
- Abertura de jogos de provedores mostra mensagem clara quando o provedor falha (antes: carregamento infinito / "JOGO EM MANUTENÇÃO").
- Removidos Google Analytics e SDK de anúncios (Poki) de terceiros embutidos nos jogos.

### Pagamentos
- **PayPal** (Orders v2): checkout com saldo PayPal ou cartão, captura no retorno e webhook verificado na API do PayPal.

### Correções gerais
- Cadastro de jogadores falhava em instalação nova (configurações carregadas sem as colunas de afiliado); comissões de afiliados vinham zeradas.
- O site inteiro travava na tela de carregamento se o CDN do Firebase falhasse (página `/datapage` de terceiros removida).
- Rollover nunca liberava o saldo e perdia o prêmio do giro final.
- Tabelas convertidas para InnoDB (transações e travas de saldo).
- `/api/settings/data` não expõe mais cupons e regras internas.
- Scripts públicos inseguros removidos (`updatecash.php`, APIs PHP soltas em `public/originals`).

### Documentação
- Novos guias: `INSTALACAO.md`, `JOGOS.md`, `SOLUCAO-DE-PROBLEMAS.md`; `PAGAMENTOS.md` e `SECURITY.md` atualizados; guias antigos e desatualizados removidos.

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
