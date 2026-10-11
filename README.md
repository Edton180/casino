# 🎰 Casino Platform

![versão](https://img.shields.io/badge/versão-9.3.0-blue)
![php](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-777bb4)
![laravel](https://img.shields.io/badge/Laravel-12-ff2d20)
![filament](https://img.shields.io/badge/Filament-3.3-f59e0b)

Plataforma de cassino online com programa de indicação (afiliados com revshare, CPA e sub-afiliados),
8 jogos originais *provably fair*, jogos locais, integração com provedores e seis gateways de pagamento.

**Criador:** Edton Braz

---

## ⚡ Instalação rápida

O repositório é **privado**: baixe o código logado no GitHub (**Code → Download ZIP**, ou `git clone` com seu token) e rode o assistente. Ele identifica o sistema, mostra o que falta, **instala PHP, banco e dependências sozinho**, instala o site e sabe atualizá-lo.

| Onde | Como |
|---|---|
| **Seu computador — Linux, macOS, WSL** | `bash instalar.sh` → menu → *Instalar no meu computador* |
| **Seu computador — Windows** | dois cliques em `instalar-windows.bat` |
| **VPS** (Ubuntu 22.04/24.04, Debian 12) | `sudo bash instalar.sh` → menu → *Instalar em um servidor* (ou direto: `sudo bash scripts/auto-install.sh --domain meusite.com --email voce@meusite.com --ssl`) |
| **Hospedagem compartilhada** (cPanel, Hostinger...) | Baixe `casino-pronto-para-hospedagem.zip` em **[Releases](../../releases)**, extraia no `public_html`, crie um banco MySQL e abra `https://seu-dominio/install/` |
| **Docker** | `docker compose up -d` |

👉 Passo a passo completo de cada opção: **[docs/INSTALACAO.md](docs/INSTALACAO.md)**

## 📚 Documentação

| Documento | Conteúdo |
|---|---|
| [docs/INSTALACAO.md](docs/INSTALACAO.md) | Instalação passo a passo (compartilhada, VPS, manual, localhost), atualização, estrutura do repositório e checklist de produção |
| [docs/MANUAL-DE-USO.md](docs/MANUAL-DE-USO.md) | Uso do painel: configurações, rodapé, pagamentos, saques, jogos, usuários e funções, afiliados, VIP, missões, aparência, manutenção |
| [docs/PAGAMENTOS.md](docs/PAGAMENTOS.md) | Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal; webhooks e saques |
| [docs/JOGOS.md](docs/JOGOS.md) | Jogos originais, jogos locais e provedores (chaves e webhooks) |
| [docs/SOLUCAO-DE-PROBLEMAS.md](docs/SOLUCAO-DE-PROBLEMAS.md) | Erros comuns e como resolver |
| [SECURITY.md](SECURITY.md) | Segurança e checklist do operador |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de versões |

## ✨ Recursos

- **8 jogos originais** (código próprio, resultado no servidor, provably fair): Mines, Crash, Dice, Plinko, Double, Tower, HiLo e Wheel.
- **Jogos locais** e de habilidade: Jack Frost, Treasures of Aztec, Dino, Subway e Fruit Ninja.
- **Provedores**: PlayFiver, Fivers, WorldSlot, Games2Api, Slotegrator, Salsa, EverGame, KaGaming e outros.
- **Pagamentos**: PIX e cartão via Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal; saque por PIX.
- **Indicação**: revshare, CPA, sub-afiliados em 3 níveis, painel do afiliado.
- **Carteira**: bônus de primeiro depósito, rollover, cashback, VIP, missões e limite de saques por período.
- **Painel administrativo** (Filament): usuários e funções, jogos, provedores, depósitos, saques, relatórios, rodapé e customização visual.

## 🗂️ Estrutura

```
├── app/ bootstrap/ config/ database/ lang/ resources/ routes/   aplicação Laravel 12
├── public/                 raiz pública (index.php, build/, originals/, install/)
├── storage/                logs, cache e uploads
├── .htaccess               hospedagem compartilhada (envia tudo para public/)
├── docs/                   documentação
├── instalar.sh / instalar-windows.bat   assistentes de instalação (abrem um menu)
├── scripts/                assistente (auto-install.sh, install-windows.ps1), backup, pacote de hospedagem
├── docker/ docker-compose.yml
└── .github/workflows/      gera o pacote pronto para hospedagem
```

## 🧱 Tecnologia

Laravel 12 · PHP 8.2–8.4 · MySQL/MariaDB · Vue 3 + Vite + TailwindCSS · Filament 3.3 · Livewire 3 · JWT

Desenvolvimento: `composer install && npm ci && npm run dev` (veja [docs/INSTALACAO.md](docs/INSTALACAO.md#c-instalação-manual-ssh--desenvolvimento)).

## ⚖️ Aviso legal

Operar jogos de azar e apostas online exige licença na maioria dos países (no Brasil, autorização da
Secretaria de Prêmios e Apostas — Lei 14.790/2023). O operador é responsável pelas licenças, KYC/PLD,
jogo responsável, LGPD e pelo direito de uso de todos os jogos, marcas e provedores que ativar.
Alguns jogos locais desativados reproduzem títulos de terceiros (ex.: PG Soft) — veja [docs/JOGOS.md](docs/JOGOS.md).
