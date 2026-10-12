# 📘 Manual de uso da plataforma

Este manual explica **como operar o cassino depois de instalado**: o painel do administrador (super admin), as configurações, os pagamentos, os jogos e o que o jogador vê no site.

> Ainda não instalou? Veja o **[Guia de instalação](INSTALACAO.md)**.

**Sumário**

1. [Acessando o painel](#1-acessando-o-painel)
2. [Mapa do painel](#2-mapa-do-painel)
3. [Primeiros passos (checklist)](#3-primeiros-passos-checklist)
4. [Configurações](#4-configurações)
5. [Pagamentos: depósitos e saques](#5-pagamentos-depósitos-e-saques)
6. [Jogos, provedores e categorias](#6-jogos-provedores-e-categorias)
7. [Usuários e carteiras](#7-usuários-e-carteiras)
8. [Afiliados (indicação)](#8-afiliados-indicação)
9. [Bônus, rollover, VIP, missões e roleta](#9-bônus-rollover-vip-missões-e-roleta)
10. [Aparência do site](#10-aparência-do-site)
11. [E-mail](#11-e-mail)
12. [Funções e permissões](#12-funções-e-permissões)
13. [Manutenção](#13-manutenção)
14. [O que o jogador vê](#14-o-que-o-jogador-vê)
15. [Tarefas do dia a dia](#15-tarefas-do-dia-a-dia)

---

## 1. Acessando o painel

| | |
|---|---|
| Endereço | `https://seu-dominio.com/admin` (local: `http://127.0.0.1:8000/admin`) |
| Login | o e-mail e a senha definidos na instalação |
| Esqueceu a senha? | Outro administrador troca em **Administração → Usuários → (usuário) → Alterar senha**. Sem nenhum admin com acesso: veja [Solução de problemas](SOLUCAO-DE-PROBLEMAS.md#esqueci-a-senha-do-admin) |

Somente usuários com a função **admin** entram no painel. Jogadores usam o e-mail e a senha apenas no site.

![Painel administrativo](img/painel-admin.png)

---

## 2. Mapa do painel

| Menu | Para que serve |
|---|---|
| **Painel** | Resumo: depósitos, saques, usuários, apostas e gráficos |
| **Configurações** | Nome, logos, banners de login/cadastro, limites, bônus, rollover, taxas de afiliados, gateways (abas laterais) |
| **Definições do Spin** | Prêmios da Roleta da Sorte |
| **Chaves dos Jogos** | Credenciais dos provedores de jogos (PlayFiver, Fivers, WorldSlot...) |
| **Definições de Email** | Servidor SMTP para recuperação de senha e avisos |
| **Saques de Afiliados** | Pedidos de saque da comissão de indicação |
| **Modulos → Missões / Vip** | Desafios com prêmio e níveis VIP |
| **Meus Jogos** | Todas as Categorias, Todos os Provedores, Todos os Jogos, Histórico de Partidas |
| **Pagamentos → Gateway de Pagamentos** | Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal |
| **Customização** | Banners da home e cores/CSS/JS do site |
| **Administração** | Usuários, Carteiras, Depósitos, Saques |
| **Funções e Permissões** | Quem pode acessar o quê |
| **Manutenção** | **Sistema** (versão, concluir atualização, backups, desinstalar), Opções Avançadas (limpar cache, importar jogos) e Limpar o cache |

---

## 3. Primeiros passos (checklist)

Na ordem recomendada:

1. **Configurações → Padrão**: nome do site, logos (clara e escura), favicon e banners de login/cadastro.
2. **Configurações → Pagamentos**: depósito mínimo/máximo, saque mínimo/máximo, bônus do 1º depósito e moeda (`BRL`).
3. **Configurações → Rollover**: quantas vezes o depósito/bônus precisa ser apostado antes do saque.
4. **Pagamentos → Gateway de Pagamentos**: credenciais de pelo menos um gateway e **Ativar**. Cadastre o webhook no painel do gateway ([PAGAMENTOS.md](PAGAMENTOS.md)).
5. **Definições de Email**: SMTP (sem ele, "esqueci minha senha" não funciona).
6. **Meus Jogos → Todos os Jogos**: confira os jogos ativos. Os 8 originais e os jogos locais já vêm prontos.
7. Faça um **cadastro de teste** no site, um depósito pequeno e um saque, e aprove o saque no painel.

---

## 4. Configurações

Menu **Configurações** (as abas ficam na lateral esquerda da página):

| Aba | O que ajustar |
|---|---|
| **Padrão** | Nome, textos da barra superior (logado/deslogado), logos, favicon, tela de carregamento, banners de login/cadastro/giros, botão e link do app, faixa *Top ganhos* (prêmios reais dos jogadores) e **Rodapé** (veja abaixo) |
| **Bônus Vip** | Liga/desliga o bônus VIP e o percentual |
| **Rollover** | *Rollover Depósito* (ex.: `1` = apostar 1× o valor depositado), *Rollover Bônus*, *Proteção de Rollover* (mínimo de apostas) e *Desativar Rollover* (o depósito fica sacável na hora) |
| **Taxas** | Comissão de afiliados: **RevShare (%)**, **CPA Valor** e **CPA Baseline** (depósito mínimo do indicado para pagar o CPA), NGR e RevShare negativo |
| **Limites** | Quantidade de saques permitidos por período (dia/semana/mês) |
| **Pagamentos** | Depósito/saque mínimo e máximo, **Bônus Inicial (%)** do 1º depósito, moeda, % de sub-afiliados (níveis 1–3), cupom de boas-vindas, Roleta da Sorte e giros grátis |
| **Gateways** | Atalho para ativar/desativar os gateways |
| **Afiliados** | % de sub-afiliados por nível |

Depois de salvar aparece **"Dados alterados com sucesso!"**. O site usa os novos valores na hora.

### Rodapé do site
**Configurações → Padrão → Rodapé**:

| Campo | Efeito |
|---|---|
| Direitos autorais | Linha de copyright. Vazio = `© <ano> <nome do site>. Todos os direitos reservados.` |
| Crédito do desenvolvedor | Ex.: `Desenvolvido por Edton Braz` (padrão). Pode ter um link e ser ocultado em **Mostrar crédito do desenvolvedor** |
| Texto "Sobre" | Substitui o texto institucional padrão do rodapé |
| E-mails de suporte, jurídico e parceiros | Só aparecem no rodapé quando preenchidos |
| **Link de suporte** | WhatsApp (`https://wa.me/55DDDNUMERO`), Telegram ou chat. É o botão *Falar com o suporte* da página **Suporte** do site; vazio = a página mostra só o e-mail de suporte |

---

## 5. Pagamentos: depósitos e saques

### Gateways
**Pagamentos → Gateway de Pagamentos**. Para cada gateway: informe as chaves, marque **Ativar** e salve. Os jogadores só veem os gateways ativos.

| Gateway | PIX | Cartão | Observação |
|---|---|---|---|
| Mercado Pago | ✅ | ✅ (checkout do MP) | |
| PagSeguro / PagBank | ✅ | ✅ | |
| Pagar.me | ✅ | ✅ | |
| Asaas | ✅ | ✅ | |
| Stripe | — | ✅ | ideal para moedas estrangeiras |
| PayPal | — | ✅ (conta PayPal/cartão) | |

Passo a passo de cada um (onde pegar as chaves e qual URL de webhook cadastrar): **[PAGAMENTOS.md](PAGAMENTOS.md)**.

### Como o depósito funciona
1. O jogador escolhe valor e forma de pagamento → recebe o QR Code PIX ou é levado ao checkout.
2. Quando o gateway confirma (webhook) — ou quando o site consulta o status — o valor entra na carteira **uma única vez**, com bônus de 1º depósito, VIP e CPA do afiliado.
3. Acompanhe em **Administração → Depósitos** (status *Pendente*/*Pago*).

> O crédito é sempre conferido direto no gateway; um webhook falso não credita nada.

### Aprovar saques
**Administração → Saques**:

| Ação | O que acontece |
|---|---|
| **Aprovar** | Marca como pago. Faça o PIX para a chave do jogador **antes** (o valor já foi descontado do saldo quando ele pediu) |
| **Cancelar** | Devolve o valor ao saldo sacável do jogador |
| Editar | Anexar comprovante e observações |

Saques de comissão ficam em **Saques de Afiliados** e funcionam igual.

---

## 6. Jogos, provedores e categorias

### Tipos de jogo
| Tipo | Precisa configurar? |
|---|---|
| **Originais** (Mines, Crash, Dice, Plinko, Double, Tower, HiLo, Wheel) | Não — rodam no seu servidor, com *provably fair* |
| **Jogos locais** (Jack Frost, Treasures of Aztec, Dino Cash, Subway, Fruit Ninja) | Não |
| **Provedores** (PlayFiver, Fivers, WorldSlot, EverGame...) | Sim — contrato e chaves |

Detalhes, RTP e regras de cada jogo: **[JOGOS.md](JOGOS.md)**.

### Ativar jogos de um provedor
1. **Chaves dos Jogos**: preencha as credenciais do provedor e salve.
2. Informe ao provedor a URL de webhook ([tabela em JOGOS.md](JOGOS.md#3-provedores-de-jogos-apis-de-terceiros)).
3. WorldSlot/EverGame: **Manutenção → Opções Avançadas → Importar provedores** e depois **Importar jogos**.
4. **Meus Jogos → Todos os Jogos**: filtre pelo provedor, selecione os jogos e use a ação em massa **Ativar Jogos**.

### Editar um jogo
**Todos os Jogos → (jogo) → Editar**: nome, provedor, categorias, RTP, destaque na home (*Mostrar na Home*), ativo/inativo. A **capa** pode ser um endereço (já preenchido) ou uma imagem enviada (322×322).

### Categorias e provedores
- **Todas as Categorias**: cria as abas da home (ex.: *Originais*, *Slots*, *Crash*). Ligue os jogos pela edição do jogo.
- **Todos os Provedores**: ativa/desativa um provedor inteiro e define o RTP padrão.

### Histórico de Partidas
Todas as apostas (de todos os tipos de jogo), com usuário, jogo, valor, ganho e data. Os originais aparecem com o provedor `originals`.

---

## 7. Usuários e carteiras

**Administração → Usuários**
- Buscar por nome/e-mail/CPF, ver detalhes, editar dados, **bloquear** (o jogador não consegue mais apostar nem logar) e **alterar senha**.
- **Criar** usuário manualmente. No campo **Funções**, escolha *admin* para dar acesso ao painel (deixe vazio, ou *afiliado*, para jogadores).

**Administração → Carteiras**
- Saldo real, saldo de bônus, saldo sacável, rollover pendente e comissões de cada jogador.
- Editar a carteira permite **ajustes manuais** de saldo real, saldo de bônus, saldo sacável e comissão (ex.: bônus de compensação). Todo ajuste manual é de sua responsabilidade — registre o motivo.

---

## 8. Afiliados (indicação)

- Todo jogador tem um link de indicação em **Perfil → Afiliados** (`/register?code=CODIGO`).
- **CPA**: valor fixo pago quando o indicado deposita pelo menos o *CPA Baseline*.
- **RevShare**: % sobre o que a casa ganha com os jogadores indicados (inclusive nos jogos originais).
- Sub-afiliados: % dos níveis 2 e 3 em **Configurações → Pagamentos/Afiliados**.
- Valores globais em **Configurações → Taxas**; para condições especiais de um afiliado, edite o usuário (CPA/RevShare individuais).
- A comissão vai para o saldo de indicação; o afiliado pede o saque, e você aprova em **Saques de Afiliados**.

---

## 9. Bônus, rollover, VIP, missões e roleta

| Recurso | Onde | Como funciona |
|---|---|---|
| **Bônus do 1º depósito** | Configurações → Pagamentos → *Bônus Inicial (%)* | Vai para o saldo de bônus, com rollover |
| **Rollover** | Configurações → Rollover | O jogador precisa apostar *valor × rollover* antes de sacar |
| **VIP** | Modulos → Vip | Cada nível tem *aposta necessária*, período e bônus. O progresso aparece em `/vip` |
| **Missões** | Modulos → Missões | Desafios (ex.: apostar X em um jogo) com prêmio. Aparecem na home para jogadores logados |
| **Roleta da Sorte** | Definições do Spin + Configurações → Pagamentos | Prêmios da roleta para visitantes e o liga/desliga |

---

## 10. Aparência do site

- **Customização → Banners**: banners da home (imagem, link e tipo). Use imagens largas e leves (WebP/JPG).
- **Customização → Customização**: cores (fundo, cartões, botões, textos), CSS e JavaScript extras (ex.: chat de suporte, pixel de anúncios).
- **Configurações → Padrão**: logos, favicon, imagem da tela de carregamento e banners de login/cadastro.

---

## 11. E-mail

**Definições de Email**: servidor SMTP (host, porta, usuário, senha, criptografia e remetente). Use um serviço de envio (ex.: SMTP do seu provedor de hospedagem, Amazon SES, Brevo, Mailgun). Teste com "Esqueci minha senha" no site.

---

## 12. Funções e permissões

- **Funções**: `admin` (acesso total ao painel) e `afiliado` (jogador). Em **Funções → Criar** você define o nome e marca as permissões. A função `admin` não pode ser apagada.
- **Permissões**: crie permissões e vincule a funções; selecione várias e use **Adicionar a uma função** para vincular em lote.
- Para dar acesso ao painel a outra pessoa: **Administração → Usuários → (usuário) → Editar → Funções → admin → Salvar**.

---

## 13. Manutenção

**Manutenção → Sistema**

| Parte | O que faz |
|---|---|
| **Versão e atualização** | Mostra a versão dos arquivos e a instalada, PHP/Laravel, espaço livre e avisos (pasta sem permissão, depuração ligada em produção). Se houver mudanças de banco pendentes aparece *"Há uma atualização para concluir"* |
| **Concluir atualização** | Depois de enviar os arquivos de uma versão nova (hospedagem): faz backup, aplica as migrações, copia imagens padrão novas, limpa caches e registra a versão. Nada é apagado |
| **Backups** | *Fazer backup agora* (banco, `.env` e arquivos enviados num `.zip`), **Baixar**, **Restaurar** (salva antes o estado atual; digite RESTAURAR) e **Apagar**. Ficam em `storage/app/backups`, protegidos do navegador; os 10 mais novos são mantidos |
| **Desinstalar o sistema** | Para hospedagem compartilhada: pede sua senha e a palavra DESINSTALAR, faz um backup final fora da pasta do site e apaga tabelas, `.env`, uploads, logs e caches. Depois apague a pasta pelo gerenciador de arquivos |

Com o agendador (cron) ativo, o sistema também copia o banco **todo dia às 03:20**.

**Manutenção → Opções Avançadas**

| Botão | Quando usar |
|---|---|
| **Atualizar banco de dados** | Só as migrações (prefira *Sistema → Concluir atualização*, que faz backup antes) |
| **Limpar cache** | Mudou algo no `.env` ou o site mostra dados antigos |
| **Criar link de uploads** | Imagens enviadas pelo painel não aparecem |
| **Importar provedores/jogos** | Catálogo da WorldSlot/EverGame |

**Manutenção → Limpar o cache** faz o mesmo que o botão acima, em um clique.

Por comando (VPS, seu computador, SSH):

```bash
sudo bash /opt/casino-src/instalar.sh --update    # VPS: baixa a versão nova e atualiza sem perder dados (backup antes)
bash ~/casino/instalar.sh --update                # no seu computador
php artisan system:backup                         # backup (banco, .env e arquivos enviados)
php artisan system:restore                        # restaura (escolhe na lista)
php artisan system:update                         # conclui uma atualização (depois de trocar os arquivos)
php artisan system:uninstall                      # desinstala (backup final, tabelas, .env, uploads, logs)
```

No Windows, as mesmas opções estão no menu do `instalar-windows.bat`.

---

## 14. O que o jogador vê

| Página | Endereço | O que tem |
|---|---|---|
| Home | `/` | Banners, missões, categorias, jogos em destaque, provedores, últimos ganhadores |
| Cassino | `/casinos`, `/casino/provider/...` | Todos os jogos com filtro por provedor/categoria e busca |
| Jogo | `/games/play/{id}/{slug}` | Abre o jogo; exige login. Favoritar e curtir |
| Cadastro / Login | `/register`, `/login` | Cadastro com CPF e telefone; código de indicação opcional |
| Carteira | `/profile/wallet` | Saldos (real, bônus, sacável), rollover pendente |
| Depósito | `/profile/deposit` | Escolhe o gateway, PIX ou cartão |
| Saque | `/profile/withdraw` | Pedido de saque via PIX (respeita mínimo, máximo, limite por período e rollover) |
| Transações | `/profile/transactions` | Histórico de depósitos e saques |
| Afiliados | `/profile/affiliate` | Link de indicação, indicados, comissões e saque de comissão |
| Favoritos / Recentes | `/profile/favorite`, `/profile/recents` | Jogos favoritos e jogados recentemente |
| VIP | `/vip` | Nível atual e progresso |
| Termos | `/terms/...` | Termos de uso, privacidade, jogo responsável, bônus |

---

## 15. Tarefas do dia a dia

**Aprovar saques pendentes**
Administração → Saques → filtre *Pendente* → faça o PIX → **Aprovar**.

**Um depósito foi pago mas não caiu**
1. Confira em Administração → Depósitos se está *Pendente*.
2. Verifique se o webhook está cadastrado no gateway ([PAGAMENTOS.md](PAGAMENTOS.md)).
3. Enquanto a tela do PIX está aberta, o site reconsulta o gateway e credita automaticamente quando estiver pago.

**Desativar um jogo com problema**
Meus Jogos → Todos os Jogos → editar → desmarque *Status*.

**Dar um bônus manual**
Administração → Carteiras → editar a carteira do jogador → some o valor ao *saldo de bônus*.

**Bloquear um jogador**
Administração → Usuários → editar → *Banido*.

**Trocar a logo**
Configurações → Padrão → Logo Branca/Escura → Salvar.

**Antes de mexer em algo importante**
Manutenção → Sistema → *Fazer backup agora* → *Baixar* (guarde a cópia fora do servidor).

**Configurar o atendimento**
Configurações → Padrão → Rodapé → *Link de suporte* (WhatsApp/Telegram/chat) e *E-mail de suporte*.

---

Problemas? **[SOLUCAO-DE-PROBLEMAS.md](SOLUCAO-DE-PROBLEMAS.md)**
