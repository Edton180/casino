# 🎰 Casino Platform

![versão](https://img.shields.io/badge/versão-9.2.0-blue)
![php](https://img.shields.io/badge/PHP-8.2%20%7C%208.3%20%7C%208.4-777bb4)
![laravel](https://img.shields.io/badge/Laravel-12-ff2d20)
![filament](https://img.shields.io/badge/Filament-3.3-f59e0b)

Plataforma de cassino online com programa de indicação (afiliados com revshare, CPA e sub-afiliados),
8 jogos originais *provably fair*, jogos locais, integração com provedores e seis gateways de pagamento.

**Criador:** Edton Braz

---

## ⚡ Instalação rápida

| Onde | Como | Guia |
|---|---|---|
| **Hospedagem compartilhada** (cPanel, Hostinger, HostGator...) | Baixe `casino-pronto-para-hospedagem.zip` em **[Releases](../../releases)**, extraia no `public_html`, crie um banco MySQL e abra `https://seu-dominio/install/` | [Seção A](docs/INSTALACAO.md#a-hospedagem-compartilhada-cpanel-hostinger-hostgator) |
| **VPS** (Ubuntu 22.04/24.04, Debian 12) | `curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/scripts/auto-install.sh -o auto-install.sh`<br>`sudo bash auto-install.sh --domain meusite.com --email voce@meusite.com --ssl` | [Seção B](docs/INSTALACAO.md#b-vps-ubuntu--debian--1-comando) |
| **Seu computador** (localhost) | Linux/macOS/WSL: `bash scripts/install-local.sh` · Windows: `scripts\install-local-windows.bat` · Docker: `docker compose up -d` | [Seção D](docs/INSTALACAO.md#d-no-seu-computador-localhost) |

Em todos os casos um assistente cria o banco, o `.env` e o administrador — não é preciso importar SQL nem editar arquivos.

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
├── scripts/                auto-instalador VPS, instalação local, backup, pacote de hospedagem
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
