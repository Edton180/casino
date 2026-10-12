# CHANGELOG.md

## v9.4.0

### Instalação que funciona de verdade (testada no Ubuntu 26.04 + PHP 8.5 + MariaDB 11.8 + sudo-rs)
- **Corrigido o erro que parava todas as instalações novas**: o Composer 2.10 não tenta mais o caminho alternativo quando o GitHub recusa downloads anônimos ("Could not authenticate against github.com"); o assistente agora baixa as dependências pelo git sozinho (ou usa `--token`).
- Rodar o assistente de novo por cima de uma instalação que falhou no meio aproveita a pasta e conclui; Composer baixado pelo GitHub quando o getcomposer.org não responde; versão correta no cabeçalho.
- PHP 8.5: conexão com SSL do MySQL sem aviso de recurso obsoleto.
- Testado de ponta a ponta: instalação no computador (usuário comum com sudo-rs), em servidor (Nginx, PHP-FPM, cron, logrotate), pelo comando de 1 linha, atualização, backup/restauração e desinstalação; Windows validado no PowerShell 7.

### Atualizar sem perder dados, backup e restauração
- `bash instalar.sh --update` faz **backup completo antes**, baixa a versão nova, coloca o site em manutenção, aplica as migrações e reativa o site; se algo falhar, indica o comando para voltar ao backup.
- Novos comandos `php artisan system:backup | system:restore | system:update | system:uninstall` e opções `--backup`/`--restore` no assistente; backups `.zip` com banco, `.env` e arquivos enviados (só o dono lê o arquivo).
- **Painel → Manutenção → Sistema**: versão, mudanças de banco pendentes, *Concluir atualização* (hospedagem sem terminal), criar/baixar/restaurar/apagar backups e desinstalar.
- Backup diário automático do banco às 03:20 (agendador), mantendo os 10 mais novos.
- Arquivo `VERSION`; a versão instalada fica registrada.

### Desinstalar sem deixar rastros
- `bash instalar.sh --uninstall` encontra **todas** as instalações (a atual, as registradas, `/var/www`, cópias antigas na pasta pessoal, uma dentro da outra, tentativas que falharam), faz backup final e remove banco e usuário do MySQL, `.env`, uploads, logs, caches, pasta, site do Nginx/SSL, cron, logrotate, ajustes do PHP-FPM, o Composer e o cache dele; programas só com `--purge-packages`. Bancos com tabelas de outros sistemas nunca são apagados (só as tabelas do cassino saem). Sem instalações restantes, oferece apagar bancos "casino…" que sobraram.
- Windows: menu com **Atualizar, Fazer backup, Restaurar e Desinstalar** (remove também o PHP/Composer que o assistente baixou).
- Hospedagem compartilhada: desinstalar pelo painel, com aviso no instalador do que foi removido.

### Site e painel
- Corrigidos erros de JavaScript em várias páginas (janelas de depósito/saque/login/missões, carteira, saque, jogo); visitante não chama mais APIs de jogador.
- **Celular, tablet e PC**: nenhuma página do site ou do painel estoura a largura da tela (verificado em 360, 390, 768, 1024, 1280 e 1920 px); fileiras de jogos alinhadas no tablet; depósito, indicação, VIP e botões ajustados; menu do painel começa fechado no celular.
- "Top ganhos" mostra **prêmios reais** (antes nomes e valores inventados com links quebrados); página de suporte com link configurável (WhatsApp/Telegram/chat); removidos restos de outros sites (chat, links e textos).
- Rodapé refeito (responsivo, selos próprios); ícones padrão para categorias sem imagem; capas reais para jogos locais que mostravam o ícone do Construct 3; textos com acentuação quebrada corrigidos.
- Avatar do painel gerado no próprio servidor (sem serviço externo).

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

- Ubuntu 26.04 ("resolute") e outros sistemas novos: o PPA ondrej/php não tem pacotes para eles e derrubava o `apt update`. O assistente agora checa se o repositório de PHP existe para o codinome do sistema (PPA ou sury.org), remove repositórios quebrados e, se o PHP pedido não existir, usa o melhor disponível (8.4, 8.3, 8.2 ou 8.5 do próprio sistema).

- Novo `php artisan system:admin` (cria/redefine o administrador e confere o login); o instalador roda a conferência no final e recria o usuário se ela falhar.
- Menu lateral do site: entre 640 e 768 px de largura (ou zoom alto do navegador) ele ocupava a tela inteira por cima dos jogos; agora só ocupa a largura toda em telas de celular (< 640 px).
- Servidor local: reinstalar/atualizar não deixa mais o servidor antigo rodando (o `rsync` apagava o arquivo de PID); `--stop` encerra também servidores sem registro; o processo do servidor não prende mais o terminal.
- Banco de dados no Ubuntu/Debian sem perguntar a "senha do root do MySQL": o assistente só tentava `sudo mysql` quando já tinha pedido o sudo para instalar pacotes; com tudo já instalado, caía direto na senha do root (que não existe, o root entra pelo socket). Agora tenta root sem senha → `sudo mysql` (pede a senha do seu usuário) → conta de manutenção do Debian → senha do root (3 tentativas) e, se nada funcionar, mostra os comandos para resolver.
- `php artisan system:install`: explica o erro de acesso (1045/1698/2002/1049) e oferece criar o banco e o usuário (root sem senha, `sudo mysql` ou conta administradora informada); gera senha forte se ficar em branco; não deixa o site usar o `root`; novas opções `--create-db`, `--db-root-user`, `--db-root-pass`.
- Windows: confere o acesso ao MySQL antes de instalar e pede a senha do root quando ela existe; confere o login do administrador no final.
- Modo servidor reabre com `sudo env ...` em vez de `sudo -E` (o sudo-rs, padrão do Ubuntu 26.04, não aceita `-E`); quadro final alinhado com caminhos longos e com `~` no lugar da pasta pessoal.

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
