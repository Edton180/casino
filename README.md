# 🎰 Casino Platform — Documentação Oficial

> Plataforma de cassino com backend em **Laravel 10** e frontend em **Vue 3 + Vite**.

## 👤 Criador
**Edton Braz**

---

## 📌 Visão geral
Esta aplicação oferece uma base completa para operação de cassino online, com:
- Painel administrativo;
- Módulos e integrações de pagamento;
- Processamento assíncrono com filas e agendador;
- Fluxo de build e deploy para produção.

---

## 🧱 Stack Tecnológica
- **Backend:** PHP 8.1+ / Laravel 10
- **Frontend:** Vue 3, Vite, TailwindCSS
- **Banco de dados:** MySQL 8+ / MariaDB compatível
- **Autenticação/API:** Laravel Sanctum e JWT
- **Admin:** Filament
- **Pagamentos:** Mercado Pago, Asaas, Stripe

---

## 📂 Estrutura esperada
A aplicação foi pensada para execução em hospedagens com estrutura tipo:

```bash
extraia para public_html/public_html
```

---

## ✅ Pré-requisitos
Antes da instalação, garanta:

- PHP **8.1+**
- Composer **2+**
- Node.js **18+** e npm
- MySQL 8+ (ou MariaDB compatível)
- Git

### Extensões PHP obrigatórias
- `curl`
- `intl`
- `libxml`
- `simplexml`
- `zip`

### Extensões recomendadas para Laravel
- `mbstring`
- `openssl`
- `pdo_mysql`
- `fileinfo`
- `gd`
- `bcmath`

---

## 🚀 Instalação local (desenvolvimento)
No diretório raiz do projeto:

```bash
cd "extraia para public_html/public_html"
cp .env.example .env
composer install
php artisan key:generate
npm install
```

Depois configure o banco no arquivo `.env` e execute:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

Para iniciar o frontend em modo desenvolvimento:

```bash
npm run dev
```

---

## ⚙️ Configuração de ambiente (.env)
Exemplo mínimo:

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

### Recomendações
- **Desenvolvimento:** `APP_DEBUG=true`
- **Produção:** `APP_DEBUG=false`
- Ajuste `QUEUE_CONNECTION`, `CACHE_DRIVER` e `SESSION_DRIVER` conforme sua infraestrutura.

---

## 🗄️ Banco de dados e migrações
### Cenário A — Instalação limpa
```bash
php artisan migrate
```

### Cenário B — Base legada (dump SQL)
1. Criar banco vazio;
2. Importar dump legado;
3. Ajustar `.env`;
4. Rodar migrações pendentes:

```bash
php artisan migrate
```

---

## 🧪 Execução da aplicação
### Desenvolvimento
Em terminais separados:

```bash
php artisan serve
npm run dev
```

### Verificações básicas
```bash
php artisan route:list
php artisan about
```

---

## 🏗️ Build de frontend
Para gerar assets otimizados:

```bash
npm run build
```

Se precisar limpar assets:

```bash
npm run clean
```

---

## 🌐 Deploy em produção
Dentro da pasta da aplicação no servidor:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Permissões e links
```bash
php artisan storage:link
```

Garanta escrita em `storage/` e `bootstrap/cache/`.

---

## 🕒 Filas, scheduler e cache
### Worker de filas
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Cron do scheduler
```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

### Limpeza geral
```bash
php artisan optimize:clear
```

---

## 🔒 Segurança e boas práticas
- Usar **HTTPS** em produção;
- Nunca versionar `.env` com dados sensíveis;
- Configurar chaves/webhooks dos gateways no painel admin;
- Monitorar `storage/logs/laravel.log`;
- Restringir acesso ao painel administrativo com políticas de senha e permissões.

---

## 🧰 Comandos úteis
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
php artisan config:cache
php artisan view:cache
```

---

## 🛠️ Troubleshooting
### Erro `vendor/autoload.php` não encontrado
```bash
composer install
```

### Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

### Erro de conexão com banco
Verifique:
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

E confirme se o MySQL está ativo.

### Erro de permissão em cache/storage
Ajuste dono/grupo da aplicação e permissões de escrita em:
- `storage/`
- `bootstrap/cache/`

---

## 📄 Licença
Defina aqui o modelo de licenciamento do projeto (ex.: MIT, Proprietária, etc.).

---

Desenvolvido por **Edton Braz**.

## 🧭 Auditoria e prontidão de produção
Nesta revisão foram aplicadas correções em função crítica de callback de jogos (`ApiPg12`) e melhoria de documentação operacional de banco de dados, validações e checklist de go-live no README técnico da aplicação (`extraia para public_html/public_html/README.md`).

Recomendação: usar o README técnico da aplicação como fonte principal para deploy e operação.

