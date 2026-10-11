# 🎰 Casino Platform

![versão](https://img.shields.io/badge/versão-9.1.0-blue)
![php](https://img.shields.io/badge/PHP-8.1%E2%80%938.3-777bb4)
![laravel](https://img.shields.io/badge/Laravel-10-ff2d20)

Plataforma de cassino online com programa de indicação (afiliados com revshare, CPA e sub-afiliados),
jogos originais, jogos locais, integração com provedores e seis gateways de pagamento.

## ⚡ Instalação rápida

| Onde | Como |
|---|---|
| **Hospedagem compartilhada** (cPanel, Hostinger, HostGator...) | Baixe `casino-pronto-para-hospedagem.zip` em **[Releases](../../releases)**, extraia no `public_html`, crie um banco MySQL e abra `https://seu-dominio/install/` |
| **VPS** (Ubuntu/Debian) | `sudo bash auto-install.sh --domain meusite.com --email voce@meusite.com --ssl` |
| **Seu computador** (localhost) | Linux/macOS/WSL: `bash scripts/install-local.sh` · Windows: `scripts\install-local-windows.bat` · Docker: `docker compose up -d` |

👉 Passo a passo completo, com capturas de tela de cada etapa: **[INSTALACAO.md](INSTALACAO.md)**

## 📚 Documentação

| Documento | Conteúdo |
|---|---|
| [INSTALACAO.md](INSTALACAO.md) | Instalação (compartilhada, VPS, manual, localhost), pós-instalação e checklist de produção |
| [MANUAL-DE-USO.md](MANUAL-DE-USO.md) | Como usar o painel: configurações, pagamentos, saques, jogos, usuários, afiliados, VIP, missões, aparência |
| [PAGAMENTOS.md](PAGAMENTOS.md) | Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal; webhooks e saques |
| [JOGOS.md](JOGOS.md) | Jogos originais, jogos locais e provedores (chaves e webhooks) |
| [SOLUCAO-DE-PROBLEMAS.md](SOLUCAO-DE-PROBLEMAS.md) | Erros comuns e como resolver |
| [SECURITY.md](SECURITY.md) | Segurança e checklist do operador |
| [CHANGELOG.md](CHANGELOG.md) | Histórico de versões |

## ✨ Recursos

- **8 jogos originais** (código próprio, provably fair): Mines, Crash, Dice, Plinko, Double, Tower, HiLo e Wheel.
- **Jogos locais** e de habilidade (Jack Frost, Treasures of Aztec, Dino, Subway, Fruit Ninja...).
- **Provedores**: PlayFiver, Fivers, WorldSlot, Games2Api, Slotegrator, Salsa, EverGame, KaGaming e outros.
- **Pagamentos**: PIX e cartão via Mercado Pago, PagSeguro, Pagar.me, Asaas, Stripe e PayPal; saque automático por PIX (Asaas).
- **Indicação**: revshare, CPA, sub-afiliados em 3 níveis, painel do afiliado.
- **Carteira**: bônus de primeiro depósito, rollover, cashback, VIP e missões.
- **Painel administrativo** (Filament): usuários, jogos, provedores, depósitos, saques, relatórios e customização visual.

## 🧱 Tecnologia

Laravel 10 · PHP 8.1–8.3 · MySQL/MariaDB · Vue 3 + Vite + TailwindCSS · Filament 3 · JWT

O código da aplicação fica em `extraia para public_html/public_html` (é o conteúdo que vai para o `public_html` da hospedagem).

## ⚖️ Aviso legal

Operar jogos de azar e apostas online exige licença na maioria dos países (no Brasil, autorização da
Secretaria de Prêmios e Apostas — Lei 14.790/2023). O operador é responsável pelas licenças, KYC/PLD,
jogo responsável, LGPD e pelo direito de uso de todos os jogos, marcas e provedores que ativar.
Alguns jogos locais incluídos reproduzem títulos de terceiros (ex.: PG Soft) — veja [JOGOS.md](JOGOS.md).
