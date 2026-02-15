# 🎰 Casino Platform — Guia simples (para iniciantes)

> Este guia foi escrito para quem **não tem experiência técnica** e quer instalar o projeto passo a passo, sem adivinhação.

## 👤 Autor
**Edton Braz**

---

## 📚 O que é este projeto?
A **Casino Platform** é uma aplicação web para operação de cassino online.
Ela possui:
- Backend em **Laravel 10 (PHP)**
- Frontend em **Vue 3 + Vite**
- Painel administrativo
- Integrações com gateways de pagamento
- Recursos de fila e tarefas agendadas

Se você nunca instalou Laravel antes, siga este documento **na ordem**.

---

## 🧭 Caminho da aplicação dentro deste repositório
O código principal fica na pasta:

```bash
extraia para public_html/public_html
```

> Sempre execute os comandos a partir dessa pasta, exceto quando o guia disser o contrário.

---

## ✅ Pré-requisitos (o que precisa estar instalado antes)

### Obrigatórios
- **Git**
- **PHP 8.1 ou superior**
- **Composer 2+**
- **Node.js 18+** e **npm**
- **MySQL 8+** (ou MariaDB compatível)

### Extensões PHP recomendadas para evitar erros
- `curl`
- `intl`
- `libxml`
- `simplexml`
- `zip`
- `mbstring`
- `openssl`
- `pdo_mysql`
- `fileinfo`
- `gd`
- `bcmath`

---

## 🛠️ Instalação local — passo a passo (do zero)

## 1) Entre na pasta do projeto Laravel
```bash
cd "extraia para public_html/public_html"
```

## 2) Crie o arquivo de ambiente
```bash
cp .env.example .env
```

Esse arquivo `.env` é onde ficam as configurações do seu ambiente (banco, URL, chaves etc.).

## 3) Instale as dependências do backend (PHP)
```bash
composer install
```

## 4) Gere a chave da aplicação
```bash
php artisan key:generate
```

## 5) Instale as dependências do frontend (JavaScript)
```bash
npm install
```

## 6) Configure o banco de dados no `.env`
Abra o arquivo `.env` e ajuste esta parte:

```env
APP_NAME="Casino Platform"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=casino
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

> Troque `seu_usuario` e `sua_senha` pelos dados reais do seu MySQL.

## 7) Crie as tabelas no banco
```bash
php artisan migrate
```

## 8) Crie o link de storage (uploads/arquivos públicos)
```bash
php artisan storage:link
```

## 9) Limpe caches antigos
```bash
php artisan optimize:clear
```

Pronto. O sistema já deve estar preparado para rodar.

---

## ▶️ Como iniciar a aplicação (modo desenvolvimento)
Use **2 terminais** na mesma pasta do projeto.

### Terminal A (backend Laravel)
```bash
php artisan serve --host=127.0.0.1 --port=8000
```
Acesse: `http://127.0.0.1:8000`

### Terminal B (frontend com atualização automática)
```bash
npm run dev -- --host 127.0.0.1 --port 5173
```
Frontend/HMR: `http://127.0.0.1:5173`

---

## 🧪 Comandos rápidos para validar se está tudo certo
```bash
php artisan about
php artisan route:list
```

Se esses comandos rodarem sem erro, a instalação está no caminho certo.

---

## 🏗️ Gerar versão de produção (build)
Quando for publicar:

```bash
npm run build
```

Se quiser limpar e recompilar assets:

```bash
npm run clean
npm run build
```

---

## 🌐 Deploy em produção (resumo seguro)
No servidor, dentro da pasta da aplicação:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Configurações importantes em produção
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL` com domínio final (HTTPS)
- Permissão de escrita em `storage/` e `bootstrap/cache/`
- Configurar cron e worker de fila

---

## ⏱️ Filas e tarefas agendadas (produção)

## Worker de fila
```bash
php artisan queue:work --tries=3 --timeout=120
```

## Cron do scheduler
Adicione no crontab do servidor:

```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

---

## 🚨 Problemas comuns (e solução)

## 1) `vendor/autoload.php` não encontrado
```bash
composer install
```

## 2) Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

## 3) Erro de banco de dados
- Revise `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Verifique se o serviço MySQL está ligado.

## 4) Erro de permissão
Garanta escrita em:
- `storage/`
- `bootstrap/cache/`

---

## 📎 Quer documentação mais completa?
Também existe um guia expandido em `DOCUMENTACAO.md` com mais detalhes operacionais.
