# 🎰 Casino Platform — Premium Documentation

![version](https://img.shields.io/badge/version-8.0.0-blue)
![status](https://img.shields.io/badge/status-production_ready-brightgreen)
![license](https://img.shields.io/badge/license-Commercial-important)

Casino Platform é uma solução profissional para operação de cassino online com foco em performance, automação de instalação e prontidão comercial.

## Demonstração
- Instalador web: `https://seu-dominio/install`
- CLI inteligente: `php artisan system:install`

## Recursos principais
- Lobby de jogos e apostas com integração de provedores.
- Relatórios operacionais e gestão administrativa.
- Pagamentos via Mercado Pago, PagSeguro, Pagar.me, Asaas e Stripe (PIX e cartão) — veja `PAGAMENTOS.md`.
- Fluxos com RNG/rotação de jogos e experiência responsiva.
- Instalador multi-modo (VPS, cPanel, localhost, docker, diagnose).

## Stack tecnológica
- **Backend:** Laravel 10 / PHP 8.2+
- **Frontend:** Vue 3 + Vite + TailwindCSS
- **Banco:** MySQL 8+
- **Infra:** Nginx, Supervisor (queue workers), cron scheduler

## Arquitetura (visão geral)
- Código Laravel em `extraia para public_html/public_html`
- Instalador web em `/install`
- Instalador CLI em `php artisan system:install`
- Auto instalador shell em `scripts/auto-install.sh`

## Instalação rápida
```bash
cd "extraia para public_html/public_html"
composer install
npm install
php artisan system:install
```

## Modos de instalação
- **web**: wizard visual em `/install`
- **cli**: `php artisan system:install`
- **produção**: `--mode=vps` ou `--mode=cpanel`
- **localhost**: `--mode=localhost`
- **docker**: `--mode=docker`
- **diagnose/help**: diagnóstico e ajuda rápida

## Deploy simplificado
Consulte guias dedicados:
- `INSTALLATION.md`
- `DEPLOY.md`
- `AUTO_INSTALLER_GUIDE.md`
- `CLI_USAGE.md`
- `TROUBLESHOOTING.md`
- `SECURITY.md`
- `PAGAMENTOS.md`

## Segurança
- HTTPS obrigatório em produção.
- Uso de `storage/installed.lock` para bloquear reinstalação via web.
- Ajuste de permissões em `storage` e `bootstrap/cache`.

## Suporte técnico
Para suporte comercial, inclua:
- Versão do projeto
- Modo de instalação usado
- Trechos de `storage/logs/laravel.log`

## Aviso legal
Este software deve ser usado conforme a legislação local aplicável para operações de jogos e apostas online.
