# 📦 Guia de instalação

Este guia cobre os cenários suportados:

| Cenário | Para quem | Tempo |
|---|---|---|
| [A. Hospedagem compartilhada](#a-hospedagem-compartilhada-cpanel-hostinger-hostgator) (cPanel, Hostinger, HostGator, Locaweb...) | Quem não tem acesso root/SSH | ~10 min |
| [B. VPS / servidor dedicado](#b-vps-ubuntu--debian--1-comando) (Ubuntu 22.04/24.04, Debian 12) | Melhor desempenho e controle | ~5 min (1 comando) |
| [C. Instalação manual](#c-instalação-manual-ssh--desenvolvimento) | Quem já tem PHP/Composer no servidor | ~5 min |
| [D. No seu computador (localhost)](#d-no-seu-computador-localhost) (Windows, macOS, Linux) | Testar, personalizar e desenvolver | ~5 min |

Em todos os casos a instalação é feita por um **assistente** que cria o banco de dados, o arquivo `.env` e o seu usuário administrador. Não é preciso importar SQL nem editar arquivos à mão.

Depois de instalar, veja o **[Manual de uso](MANUAL-DE-USO.md)** (painel, configurações, pagamentos, jogos).

> ⚖️ **Antes de começar:** operar apostas e jogos de azar exige licença na maior parte dos países (no Brasil, autorização da Secretaria de Prêmios e Apostas — Lei 14.790/2023). Você é responsável pelas licenças, KYC/PLD, jogo responsável, LGPD e pelo direito de uso dos jogos e provedores que ativar.

---

## Requisitos

| Item | Mínimo |
|---|---|
| PHP | **8.2, 8.3 ou 8.4** (recomendado 8.3 ou 8.4) — Laravel 12 |
| Extensões PHP | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`, `zip`, `intl`, `gd`, `dom` |
| Banco | MySQL 5.7+/8 ou MariaDB 10.4+ |
| Espaço | ~1,5 GB |
| Servidor web | Apache (com `.htaccess`/mod_rewrite) ou Nginx |

O assistente verifica tudo isso e mostra exatamente o que falta.

---

## A. Hospedagem compartilhada (cPanel, Hostinger, HostGator...)

### 1. Baixe o pacote pronto

Na página **[Releases](https://github.com/Edton180/casino/releases)** do repositório, baixe
**`casino-pronto-para-hospedagem.zip`**. Ele já vem com:

- as dependências PHP (pasta `vendor`) — você **não** precisa de SSH nem Composer;
- o frontend compilado (`public/build`) — você **não** precisa de Node.js.

> O pacote é gerado automaticamente pelo GitHub Actions a cada atualização do `main`.
> Se baixar o código-fonte (botão *Code → Download ZIP*), faltará a pasta `vendor` e o site mostrará uma página explicando isso.
> Para gerar o pacote no seu computador: `bash scripts/build-release.sh` (precisa de PHP, Composer e Node.js) — o zip fica em `dist/`.

### 2. Escolha a versão do PHP

No painel: **Selecionar versão do PHP** (cPanel) ou **Avançado → Configuração PHP** (Hostinger).
Escolha **PHP 8.3 ou 8.4** (8.2 também funciona) e marque as extensões: `intl`, `zip`, `gd`, `bcmath`, `fileinfo`, `pdo_mysql`, `mbstring`, `curl`.

### 3. Crie o banco de dados

cPanel → **Bancos de dados MySQL**:

1. Crie um banco (ex.: `usuario_casino`).
2. Crie um usuário com senha forte.
3. **Adicione o usuário ao banco** marcando **TODOS OS PRIVILÉGIOS**.

Anote: nome do banco, usuário e senha. O servidor quase sempre é `localhost`.

### 4. Envie os arquivos

Gerenciador de arquivos → abra a pasta **`public_html`** (ou a pasta do domínio/subdomínio) →
**Carregar** o zip → clique com o botão direito → **Extract/Extrair**.

A estrutura deve ficar assim:

```
public_html/
├── .htaccess        ← envia tudo para public/ e bloqueia arquivos internos
├── app/
├── public/
├── vendor/
└── ...
```

> **Dica:** se o painel permitir mudar o *Document Root* do domínio, aponte-o para `public_html/public`. É opcional — o `.htaccess` incluído já protege `.env`, `storage`, `vendor` etc.
>
> **Subdomínio ou domínio adicional:** extraia o zip na pasta do subdomínio (ex.: `public_html/cassino`) e aponte o subdomínio para ela. O site precisa ficar na **raiz** de um domínio ou subdomínio — não funciona em subpasta como `meusite.com/cassino`.

### 5. Rode o assistente

Acesse **`https://seu-dominio.com/install/`** e siga as etapas:

1. **Início** — leia e aceite o aviso legal.
2. **Requisitos** — tudo deve ficar verde. Itens vermelhos mostram como corrigir.
3. **Banco** — informe host (`localhost`), porta (`3306`), nome, usuário e senha → **Testar conexão**.
4. **Site e admin** — nome do site, URL (use `https://` se o SSL já estiver ativo), seu e-mail e senha de administrador.
5. **Instalar** — aguarde as 5 etapas ficarem verdes.

| Requisitos | Concluído |
|---|---|
| ![Requisitos](img/instalador-requisitos.png) | ![Concluído](img/instalador-concluido.png) |

Pronto! O painel fica em **`https://seu-dominio.com/admin`**:

![Painel administrativo](img/painel-admin.png)

Por segurança o instalador é bloqueado depois de concluído (`storage/installed.lock`).

### 6. Ative o SSL e o cron

- **SSL:** cPanel → *SSL/TLS Status* → **Run AutoSSL** (gratuito). Depois edite o `.env` e troque `APP_URL=http://` por `https://`.
- **Cron** (bônus de cashback e tarefas agendadas): cPanel → **Cron Jobs** → *A cada minuto*:

  ```
  cd /home/SEU_USUARIO/public_html && php artisan schedule:run >> /dev/null 2>&1
  ```

  > Em algumas hospedagens o PHP do cron é `/usr/local/bin/php` ou `/opt/cpanel/ea-php83/root/usr/bin/php`.

### 7. Atualizar (hospedagem compartilhada) — sem perder dados

1. No painel: **Manutenção → Sistema → Fazer backup agora** (e clique em **Baixar** para guardar uma cópia no seu computador).
2. Baixe o `casino-pronto-para-hospedagem.zip` mais recente e extraia **por cima** dos arquivos atuais. O zip não traz `.env`, uploads nem a trava de instalação, então nada disso é sobrescrito.
3. Volte em **Manutenção → Sistema** e clique em **Concluir atualização**: ele faz outro backup, aplica as mudanças do banco da versão nova, copia as imagens padrão novas e limpa os caches (o site fica em manutenção por alguns segundos).

Se algo der errado, a mesma página lista os backups: **Restaurar** volta o banco e os arquivos enviados ao estado do backup (antes, ela salva o estado atual).

Continue em [Depois de instalar](#depois-de-instalar).

---

## Instalação em 1 comando (recomendado)

O repositório é **público**: não precisa baixar nada antes. Abra o terminal e rode **um** destes comandos. O assistente **identifica o sistema, baixa o código, instala PHP, banco de dados, Composer e dependências, configura tudo e liga o site**.

| Onde | Comando (copie e cole **só esta linha**) |
|---|---|
| **Linux, macOS, WSL — no seu computador** | `curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/instalar.sh \| bash -s -- --auto` |
| **Windows** (PowerShell) | `irm https://raw.githubusercontent.com/Edton180/casino/main/scripts/install-windows.ps1 \| iex` |
| **VPS com domínio e HTTPS** | `curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/instalar.sh \| sudo bash -s -- --domain meusite.com --email voce@meusite.com --ssl -y` |

- `--auto` instala no seu computador **sem fazer nenhuma pergunta** (site em `http://127.0.0.1:8000`, login `admin@casino.local` / `admin12345`). Sem `--auto`, ele abre o menu interativo.
- Para **atualizar** depois sem perder dados: `bash ~/casino/instalar.sh --update` (faz backup antes; no VPS: `sudo bash /opt/casino-src/instalar.sh --update`).
- Para **remover tudo** (inclusive restos de instalações antigas): `bash ~/casino/instalar.sh --uninstall` — veja [Desinstalar](#desinstalar-remover-tudo).
- A instalação local fica em `~/casino` (Windows: `%USERPROFILE%\casino`).
- Se preferir baixar antes: `git clone https://github.com/Edton180/casino.git && cd casino && bash instalar.sh --auto` (Windows: dois cliques em `instalar-windows.bat`).

> Cada linha acima é **um comando completo**. Não cole várias linhas de uma vez nem os trechos de outras seções deste guia (eles são alternativas, não passos em sequência).

Se o repositório voltar a ser privado, baixe o código logado no GitHub (**Code → Download ZIP**) ou use `--token SEU_TOKEN` (GitHub → Settings → Developer settings → Fine-grained token com *Contents: Read-only*).

---

## B. VPS (Ubuntu / Debian)

**O que você precisa:** um servidor com Ubuntu 22.04/24.04 ou Debian 12 (1 GB de RAM ou mais), acesso `root`/`sudo`, e um **domínio** com o registro **A** apontando para o IP do servidor (o assistente confere).

```bash
# no servidor, depois de baixar o código (veja acima):
cd casino
sudo bash instalar.sh
```

O assistente abre um menu (use as setas e Enter). Escolha **"Instalar em um servidor (VPS)"** e responda: domínio, e-mail, nome do site, HTTPS e senha do administrador (ENTER gera uma forte).

**O que ele faz sozinho:**

| Etapa | O que acontece |
|---|---|
| 1. Diagnóstico | Identifica o sistema, memória, disco, internet, porta 80, e confere se o **DNS do domínio** aponta para este servidor. Se o Apache estiver ocupando a porta 80, oferece desativá-lo. |
| 2. Pacotes | Atualiza o apt e instala **Nginx, PHP 8.3 (FPM), MariaDB, Certbot, Composer, git, cron** (adiciona o repositório do PHP se a versão não existir na sua distribuição). |
| 3–4. Código e dependências | Copia o site para `/var/www/casino` e instala as dependências do PHP. |
| 5. Banco | Cria o banco `casino` e um usuário com **senha aleatória**. |
| 6. Site | Cria as tabelas, os jogos iniciais e o administrador. |
| 7. Web, SSL e cron | Configura o Nginx, emite o **certificado HTTPS** (Let's Encrypt), liga o agendador e **testa se o site respondeu**. |

Além disso, no servidor ele aplica o endurecimento de produção: `APP_ENV=production` e `APP_DEBUG=false`, limites do PHP-FPM (uploads de 64 MB, 512 MB de memória, OPcache), **swap** automático em máquinas com pouca RAM, **firewall `ufw`** (só se já estiver ativo: libera SSH, 80 e 443), rotação dos logs (`logrotate`) e um teste final que bate no Nginx local mesmo antes do DNS propagar. Se o certificado não puder ser emitido (DNS ainda não aponta), o site fica em HTTP e o assistente mostra o comando `certbot` para rodar depois.

No final aparece um quadro com o endereço do site, do painel e o login; tudo também fica em `/root/casino-credenciais.txt`.

**Sem menu (automação):**

```bash
sudo bash scripts/auto-install.sh --domain meusite.com --email voce@meusite.com --ssl -y
# se o repositório estiver privado, sem copiar o código antes:
sudo bash scripts/auto-install.sh --token github_pat_XXXX --domain meusite.com --email voce@meusite.com --ssl -y
```

| Opção | Descrição |
|---|---|
| `--domain`, `--email`, `--name` | Domínio, e-mail do administrador (login) e nome do site |
| `--ssl` | HTTPS gratuito (precisa do DNS já apontado) |
| `--admin-pass SENHA` | Senha do admin (padrão: gerada) |
| `--port 8080` | Porta HTTP, se a 80 estiver ocupada (sem SSL) |
| `--php 8.4` | Versão do PHP (8.2, 8.3 ou 8.4) |
| `--system-upgrade` | Também atualiza os pacotes do sistema (`apt upgrade`) |
| `--token`, `--repo`, `--branch`, `--source` | Origem do código (token do GitHub, outro repositório/branch, ou uma pasta/zip) |
| `--reinstall` | Apaga a instalação existente e instala de novo |
| `-y` | Não pergunta nada |
| `--no-color` | Sem cores nem animação (para logs) |

### Atualizar (sem perder dados)

```bash
sudo bash /opt/casino-src/instalar.sh --update
```

(`/opt/casino-src` é onde o assistente guarda o código no servidor; se você instalou a partir de uma cópia baixada, rode o `instalar.sh` dela.)
Ele faz um **backup completo antes** (em `/var/backups/casino`), baixa a versão nova, coloca o site em manutenção, atualiza arquivos e dependências, aplica as migrações, copia as imagens padrão novas, limpa os caches e reativa o site. Se algo falhar, o site volta do modo manutenção sozinho e o assistente mostra o comando para voltar ao backup. Funciona também em instalações feitas por versões antigas do instalador.

### Backup e restauração

```bash
sudo bash /opt/casino-src/instalar.sh --backup             # banco + .env + arquivos enviados num .zip
sudo bash /opt/casino-src/instalar.sh --restore            # escolhe um backup da lista
sudo bash /opt/casino-src/instalar.sh --restore ARQUIVO.zip
```

Além disso o sistema faz **sozinho, todo dia às 03:20, um backup do banco** (`storage/app/backups`, ficam os 10 mais novos) — basta o agendador (cron) estar ativo, o que o assistente do VPS já configura. Pelo painel: **Manutenção → Sistema** (criar, baixar, restaurar e apagar backups). Guarde cópias fora do servidor.

---

## C. Instalação manual (SSH / desenvolvimento)

```bash
git clone https://github.com/Edton180/casino.git
cd casino
composer install --no-dev --optimize-autoloader
php artisan system:install            # pergunta banco, URL e administrador
```

**Banco de dados:** informe o nome do banco, o usuário e a senha. Se o usuário ainda não existir (erro *Access denied* 1045/1698), o comando explica o motivo e **oferece criar o banco e o usuário** para você, com acesso só a esse banco. Se você deixou a senha em branco, ele gera uma senha forte e grava no `.env`. Para isso ele tenta, nesta ordem:

1. o `root` do MySQL sem senha (XAMPP, Laragon, Windows; ou quando o comando roda como root);
2. `sudo mysql` (Ubuntu/Debian, onde o root do MariaDB/MySQL não tem senha própria) — pede a senha do **seu usuário do computador**;
3. uma conta administradora que você digitar (ex.: `root` + senha).

> No Ubuntu/Debian, **não use `root` como usuário do site**: ele só entra pelo terminal com `sudo` (é isso que causa o erro 1698). Use um usuário próprio, como `casino`; o comando cria para você.

Sem perguntas:

```bash
php artisan system:install --no-interaction \
  --url=https://meusite.com --name="Meu Cassino" \
  --db-host=localhost --db-name=casino --db-user=casino --db-pass='SENHA' \
  --admin-email=voce@meusite.com --admin-pass='SENHA_ADMIN'
```

Sem perguntas, criando banco e usuário se ainda não existirem (seu computador ou VPS):

```bash
php artisan system:install --no-interaction --create-db \
  --url=http://127.0.0.1:8000 --name="Meu Cassino" \
  --db-name=casino --db-user=casino --db-pass='SENHA_DO_BANCO' \
  --admin-email=voce@meusite.com --admin-pass='SENHA_ADMIN'
# conta administradora do MySQL com senha:  --db-root-user=root --db-root-pass='SENHA_DO_ROOT'
```

Verificar só os requisitos: `php artisan system:install --check`.

Se o banco informado não existir e o usuário tiver permissão, ele é criado automaticamente.

Redefinir o administrador do painel (e conferir o login): `php artisan system:admin --email=voce@meusite.com --password='NOVA_SENHA'`.

O frontend já vem compilado em `public/build`. Para alterar o Vue/Tailwind:
`npm ci && npm run build`.

---

## D. No seu computador (localhost)

Para testar a plataforma, criar seus temas ou desenvolver. **Não precisa de domínio, Nginx nem SSL.**

### D1. Ubuntu, Debian, Linux Mint, WSL e macOS — `bash instalar.sh`

**O que você precisa:** só de internet (veja *"Instalação em 1 comando"* acima). **PHP e banco de dados não precisam estar instalados** — o assistente instala.

```bash
cd casino            # a pasta que você baixou/extraiu
bash instalar.sh
```

1. No menu escolha **"Instalar no meu computador"** (setas ↑↓ e Enter).
2. Responda: nome do site, e-mail e senha do administrador (ENTER aceita o padrão `admin@casino.local` / `admin12345`) e a porta (ele sugere a primeira livre a partir da 8000).
3. O assistente mostra o **diagnóstico** do seu computador e, se faltar algo, a lista do que vai instalar — por exemplo:

   ```
   ╭─ O assistente vai instalar ─────────────────────────╮
   │   ▸ PHP 8.3 + extensões                             │
   │   ▸ MariaDB (banco de dados)                        │
   ╰─────────────────────────────────────────────────────╯
   ? Posso instalar agora? (vai pedir sua senha de administrador) [S/n]
   ```

4. Ele pede a sua senha de administrador (`sudo`) **só nessa hora**, instala, cria o banco e o usuário do banco **sozinho** (resolvendo o problema do `root` do MariaDB no Ubuntu), instala o site e **liga** o servidor.
5. No final, abra o endereço mostrado (normalmente **http://127.0.0.1:8000**; painel em **/admin**).

> **Não use `sudo bash instalar.sh`** no modo local — os arquivos ficariam do root. Rode como seu usuário; o assistente pede a senha só para instalar pacotes.  
> No **WSL**, abra o endereço no navegador do Windows. No **macOS**, instale antes `brew install php composer mariadb && brew services start mariadb` (o assistente avisa e confere).

**Comandos do dia a dia** (na pasta do projeto):

| O que fazer | Comando |
|---|---|
| Ligar o site | `bash instalar.sh --start` (ou menu → *Ligar / desligar*) |
| Desligar | `bash instalar.sh --stop` |
| Ver se está ligado | `bash instalar.sh --status` |
| Atualizar sem perder dados (backup antes) | `bash instalar.sh --update` |
| Fazer backup | `bash instalar.sh --backup` (fica em `~/casino-backups`) |
| Restaurar um backup | `bash instalar.sh --restore` |
| Desinstalar (remove tudo, com backup final) | `bash instalar.sh --uninstall` |
| Diagnóstico | `bash instalar.sh --diagnose` |
| Liberar acesso de outros aparelhos da rede (celular) | `bash instalar.sh --local --lan` |

Sem menu, tudo de uma vez: `bash instalar.sh --local -y --name "Meu Cassino" --email eu@exemplo.com --admin-pass MinhaSenha123`.  
Os dados (login e senha do banco) ficam em `~/casino-credenciais-local.txt`.

### D2. Windows — dois cliques em `instalar-windows.bat`

**O que você precisa:** Windows 10 ou 11 e internet. O assistente identifica o Windows e **baixa o PHP 8.3 e o Composer sozinho** (na sua pasta de usuário, sem precisar de administrador) e ativa as extensões. Para o **banco de dados**:

- se você já tem **XAMPP** ou **Laragon**, só inicie o **MySQL** no painel deles; ou
- deixe o assistente instalar o **MariaDB** pelo `winget` (pede permissão de administrador uma vez).

Passo a passo:

1. Mais simples: no PowerShell rode a linha de *"Instalação em 1 comando"*. Ou baixe o ZIP do repositório, **extraia** (não rode de dentro do zip) e dê dois cliques em **`instalar-windows.bat`**.
2. (Se usou o ZIP) dê dois cliques em **`instalar-windows.bat`**.
3. Escolha **1) Instalar no meu computador** e responda as perguntas (ou aperte ENTER para os padrões).
4. O navegador abre em **http://127.0.0.1:8000**. Login padrão: `admin@casino.local` / `admin12345`.

Depois, dê dois cliques no mesmo arquivo e escolha no menu: **Ligar**, **Desligar**, **Atualizar** (backup antes, baixa a versão nova e atualiza o banco sem perder dados), **Fazer backup**, **Restaurar um backup**, **Desinstalar** ou **Diagnóstico**. Os backups ficam em `%USERPROFILE%\casino-backups`.
Pelo PowerShell: `powershell -ExecutionPolicy Bypass -File scripts\install-windows.ps1 -Acao Atualizar` (ou `Backup`, `Restaurar -Arquivo caminho.zip`, `Desinstalar`, `Diagnostico`).

> Se o Windows mostrar *"O Windows protegeu o computador"*, clique em *Mais informações → Executar assim mesmo* (o arquivo vem do seu próprio repositório).

### D3. Servidor Nginx completo no seu computador (Ubuntu/Debian)

Igual a uma VPS, mas em `http://localhost`, sem domínio nem SSL (útil para testar a configuração real de servidor):

```bash
sudo bash scripts/auto-install.sh --local --stack              # http://localhost
sudo bash scripts/auto-install.sh --local --stack --port 8080  # se a porta 80 estiver ocupada
```

### D4. Docker (qualquer sistema)

Com o [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado, na pasta do repositório:

```bash
docker compose up -d
docker compose logs -f app      # acompanhe a instalação (1ª vez leva alguns minutos)
```

Depois: http://localhost:8000 (painel em `/admin`, login `admin@casino.local` / `admin12345` — altere no `docker-compose.yml` **antes** da primeira subida).
Parar: `docker compose down` · apagar tudo, inclusive o banco: `docker compose down -v`.

> ⚠️ Em instalações locais o login padrão é público. Nunca exponha essa instalação na internet sem trocar a senha.

### Algo deu errado?

O assistente mostra as últimas linhas do erro, uma **dica específica** (memória, internet, repositório privado, apt ocupado...) e o caminho do registro completo. Rode `bash instalar.sh --diagnose` para ver o que está faltando e consulte [SOLUCAO-DE-PROBLEMAS.md](SOLUCAO-DE-PROBLEMAS.md#instalação-local-localhost).

---

## Estrutura do repositório

```
casino/
├── app/, bootstrap/, config/, database/, lang/, resources/, routes/   ← aplicação Laravel
├── public/                ← raiz pública (index.php, build/, originals/, install/)
├── storage/               ← logs, cache e uploads (precisa ser gravável)
├── .htaccess              ← hospedagem compartilhada: envia tudo para public/ e bloqueia o resto
├── database/install/      ← banco inicial usado pelo instalador (base.sql) e imagens padrão
├── database/extras/       ← lista de jogos da PlayFiver (importação manual opcional)
├── docs/                  ← esta documentação
├── scripts/               ← auto-instalador VPS, instalação local, backup, gerador do pacote
├── docker/, docker-compose.yml   ← instalação local com Docker
└── .github/workflows/     ← gera o pacote para hospedagem a cada atualização do main
```

`docs/`, `scripts/`, `docker/` e `.github/` não vão para o pacote de hospedagem nem para a pasta do site na VPS.

---

## Depois de instalar

### 1. Pagamentos
Painel → **Pagamentos → Gateway de Pagamentos**. Informe as credenciais e ative:
Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal.
Cadastre os webhooks no painel de cada gateway — detalhes em **[PAGAMENTOS.md](PAGAMENTOS.md)**.

### 2. Jogos
- Os **jogos originais** (Mines, Crash, Dice, Plinko, Double, Tower, HiLo, Wheel) e os jogos locais já funcionam sem nenhuma configuração.
- Para jogos de **provedores** (PlayFiver, Fivers, WorldSlot...): configure as chaves em **Configurações → Chaves dos Jogos** e ative os jogos em **Meus Jogos → Todos os Jogos** (selecione e use a ação em massa **Ativar Jogos**).
  Detalhes em **[JOGOS.md](JOGOS.md)**.

### 3. Aparência
- **Configurações** → nome, logos, banners, limites de depósito/saque, bônus e rollover.
- **Customização** → cores, CSS/JS, banners da home.

O passo a passo de cada tela está no **[Manual de uso](MANUAL-DE-USO.md)**.

### 4. E-mail (recuperação de senha e notificações)
**Definições de Email** no painel, ou no `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.seuprovedor.com
MAIL_PORT=587
MAIL_USERNAME=usuario
MAIL_PASSWORD=senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=nao-responda@meusite.com
```

### 5. Checklist de produção
- [ ] `APP_DEBUG=false` e `APP_ENV=production` no `.env` (o instalador já grava assim)
- [ ] HTTPS ativo e `APP_URL` com `https://`
- [ ] Cron rodando a cada minuto
- [ ] Webhooks dos gateways cadastrados
- [ ] Backup diário do banco
- [ ] Senha forte no administrador

Problemas? Veja **[SOLUCAO-DE-PROBLEMAS.md](SOLUCAO-DE-PROBLEMAS.md)**.

---

## Desinstalar (remover tudo)

Todas as formas fazem antes um **backup final** (banco, `.env` e arquivos enviados) **fora da pasta do site**, para você poder voltar atrás. Se não quiser: `--no-backup` (bash) ou `-SemBackup` (Windows).

| Onde | Como |
|---|---|
| **Linux, macOS, WSL** (seu computador) | `bash ~/casino/instalar.sh --uninstall` — ou, sem a pasta: `curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/instalar.sh \| bash -s -- --uninstall` |
| **VPS** | `sudo bash /opt/casino-src/instalar.sh --uninstall` |
| **Windows** | `instalar-windows.bat` → **Desinstalar** (ou `scripts\install-windows.ps1 -Acao Desinstalar`) |
| **Hospedagem compartilhada** | Painel → **Manutenção → Sistema → Desinstalar o sistema**; depois apague a pasta do site pelo gerenciador de arquivos e, se quiser, o banco e o usuário MySQL no painel da hospedagem |
| **Docker** | `docker compose down -v` na pasta do projeto (o `-v` apaga também o banco) |

O assistente (Linux/macOS/WSL/VPS e Windows):

- **encontra todas as instalações** — a atual, as registradas, as de `/var/www`, cópias antigas na pasta pessoal (inclusive uma dentro da outra, como `casino-main/casino-main`) e restos de instalações que falharam no meio — mostra cada uma (instalada, parcial ou só o código) e pergunta quais remover (`--all` = todas, `--dir PASTA` = só uma);
- desliga o site, apaga **as tabelas do cassino, o banco e o usuário do MySQL criados para ele** (se o banco tiver tabelas de outro sistema, só as do cassino saem e o banco fica), o `.env`, uploads, logs e caches, e a pasta (`--purge` apaga as pastas sem perguntar);
- no VPS também remove o site do Nginx (e o certificado SSL, se você confirmar), o agendador (cron), a rotação de logs e os ajustes do PHP-FPM;
- remove o que **ele mesmo instalou**: o Composer e o cache dele, o código-fonte em `/opt/casino-src`, o arquivo de senhas; programas como PHP, Nginx e MariaDB só com `--purge-packages` (ou se você confirmar), nunca os que já existiam antes;
- sem nenhuma instalação restante, procura **bancos "casino…" que sobraram** de instalações apagadas à mão e pergunta se pode apagá-los (com cópia antes).

`--keep-database` mantém o banco. Os backups **não** são apagados (ficam em `~/casino-backups`, `/var/backups/casino` ou `%USERPROFILE%\casino-backups`).

### Reinstalar do zero

Desinstale como acima e instale de novo. Para reaproveitar os dados, depois de instalar rode `bash instalar.sh --restore ARQUIVO.zip` (ou Painel → Sistema → Restaurar).
Na hospedagem compartilhada também dá para apagar `storage/installed.lock` e abrir `/install/` marcando **Apagar dados existentes** — isso apaga **todos** os dados (usuários, saldos, histórico).
