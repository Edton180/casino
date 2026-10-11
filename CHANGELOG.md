# CHANGELOG.md

## v9.3.0

### Assistente de instalação (novo)
- `bash instalar.sh` / `instalar-windows.bat`: menu com setas, etapas com barra de progresso, animação e quadros; **identifica o sistema**, mostra um diagnóstico (internet, disco, memória, PHP, extensões, banco, composer, porta, DNS), lista o que vai instalar e **instala sozinho** PHP, MariaDB, Nginx, Composer e dependências.
- Resolve o acesso ao MySQL no Ubuntu (cria banco e usuário próprio via socket/sudo), escolhe porta livre, verifica se o site realmente respondeu antes de dizer "concluído".
- Funciona a partir do código já baixado (sem `curl`) e com **repositório privado** (`--token`, ou orientação passo a passo); `--source` aceita pasta ou .zip.
- Modo local sem root, com `--start`, `--stop`, `--status`, `--lan`; `--update` para atualizar; `--diagnose`; `--system-upgrade` para atualizar o sistema.
- Windows: `install-windows.ps1` baixa PHP 8.3 e Composer sozinho, ativa as extensões, usa o MySQL do XAMPP/Laragon ou instala o MariaDB via winget.
- Docker com a mesma identidade visual; erros mostram dica específica e o caminho do log.

### Validação e correções (localhost e produção)
- Windows: composer/git escrevem avisos no stderr — isso não derruba mais a instalação; fluxo completo (dependências → banco → site → health check → desligar) validado com PowerShell 7; alinhamento dos quadros; Composer com endereço alternativo; ligar o site informa a porta mesmo se já estiver ligado.
- Servidor: domínio que ainda não resolve não aborta mais o assistente; se o SSL falhar, `APP_URL` e o resumo passam a mostrar o endereço HTTP real; novos ajustes de produção (PHP-FPM, swap, ufw, logrotate, teste final via `--resolve`).

### Documentação
- Guia de instalação reorganizado (como obter o código com repositório privado, VPS, localhost por sistema); tabela de problemas do assistente.


## v9.2.0

### Plataforma
- **Laravel 12** (antes 10, sem correções de segurança desde fev/2025) com **PHP 8.2, 8.3 ou 8.4**; Filament 3.3, Livewire 3.8, Sanctum 4, JWT com lcobucci/jwt 5.
- Funções e Permissões reescritas como telas próprias do painel (o pacote antigo não tem versão para Laravel 12 + Filament 3).
- Removidas dependências sem uso (módulos nwidart, editor de .env de terceiros) e 35 cópias desatualizadas de telas do Filament.

### Repositório
- A aplicação saiu de `extraia para public_html/public_html` para a **raiz do repositório**; documentação em `docs/`.
- Pacote para hospedagem: o GitHub Actions falhava ao gerar o zip (caminho relativo) e nunca publicou o arquivo — corrigido.
- `auto-install.sh --update` aceita a estrutura nova e a antiga.

### Segurança
- Removida a pasta `public/gambiarra` (scripts de teste que enviavam usuário e saldo dos callbacks de provedores para um site externo).
- Removida cópia antiga do JavaScript do Livewire em `public/livewire` (sobrepunha a versão instalada).
- Removido arquivo de validação SSL (`.well-known`) de outro servidor.

### Painel
- **Rodapé configurável**: direitos autorais, crédito do desenvolvedor (com link e opção de ocultar), texto "Sobre" e e-mails de contato.


## v9.1.0

### Instalação
- **Instalação local (localhost)**: `scripts/install-local.sh` (Linux/macOS/WSL), `scripts/install-local-windows.bat` (XAMPP/Laragon), `auto-install.sh --local` (Nginx completo) e `docker-compose.yml`.
- `auto-install.sh`: opção `--port`, aviso quando a porta já está ocupada e falha real se o Nginx não subir (antes dizia "concluída").
- O instalador cria o banco de dados quando ele não existe e o usuário tem permissão.

### Jogos
- Novos originais **Tower, HiLo e Wheel** (mecânicas do Luck2x reescritas como provably fair).
- Visual novo em todos os originais: histórico de rodadas, sons, animação de vitória, efeitos de derrota e gráficos refeitos (gemas e bombas no Mines, foguete e área no Crash, pinos que acendem no Plinko etc.).

### Painel administrativo
- Funções e Permissões abriam com erro (rotas antigas sobrepunham as do painel); menu apontava para `/admpadrao`.
- Salvar as Configurações gerais dava erro 500 e não voltava ao painel.
- Pagamentos: o campo Cupom obrigatório impedia salvar os limites.
- Trocar senha de usuário passou a validar (mínimo 8 caracteres e confirmação).
- Usuários: novo campo **Funções** para dar acesso de administrador.
- VIP: símbolo e nível obrigatórios (antes erro de banco).
- Jogos: editar um jogo não exige mais reenviar a capa (nem apaga a atual); miniaturas da lista corrigidas.
- Opções Avançadas: os botões eram de fachada; agora atualizam o banco, limpam o cache, criam o link de uploads e importam provedores/jogos (WorldSlot/EverGame). "Limpar o cache" do menu passou a funcionar e só para administradores.
- Rótulos invertidos corrigidos ("Ativar Roleta da Sorte", "Desativar Giros Grátis").
- Removidas mensagens ofensivas e redirecionamentos "pegadinha".

### Site
- Hospedagem compartilhada: chamadas da API com barra final eram redirecionadas para `/public/api/...` (quebrava POSTs); `/app` dava 403.
- Página VIP exige login; Recentes não retorna mais erro 400 quando vazio.
- Roleta da Sorte quebrava em produção (dependia do Faker, que não é instalado).
- Missões: só listagem e inscrição ficam expostas na API.


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
