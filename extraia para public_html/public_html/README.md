# Casino Platform (Laravel) — Documentação Técnica Completa

Guia profissional para instalar, configurar, executar e publicar a aplicação em ambiente de desenvolvimento e produção.

---

## 1) Diretório correto do projeto
A raiz da aplicação Laravel está em:

```bash
cd "extraia para public_html/public_html"
```

---

## 2) Stack da aplicação
- **Backend:** Laravel 10 (PHP 8.1+)
- **Frontend:** Vue 3 + Vite + TailwindCSS
- **Banco de dados:** MySQL/MariaDB
- **Auth/API:** Sanctum + JWT
- **Admin:** Filament
- **Integrações:** Mercado Pago, Asaas, Stripe

---

## 3) Pré-requisitos
- PHP **8.1+**
- Composer **2+**
- Node.js **18+** e npm
- MySQL 8+ (ou MariaDB compatível)

### Extensões PHP obrigatórias (conforme `composer.json`)
- `ext-curl`
- `ext-intl`
- `ext-libxml`
- `ext-simplexml`
- `ext-zip`

### Extensões recomendadas para Laravel
- `mbstring`, `openssl`, `pdo_mysql`, `fileinfo`, `gd`, `bcmath`

---

## 4) Instalação limpa (recomendada)
```bash
cp .env.example .env
composer install
php artisan key:generate
npm install
npm run build
```

Edite o `.env`:
- `APP_URL`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

Depois execute:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

---

## 5) Instalação com base legada (SQL)
Existe dump SQL em:
- `INSTALL CASSINO/elitecassino.sql`

Fluxo recomendado:
1. Criar banco vazio.
2. Importar o SQL legado.
3. Ajustar o `.env` para esse banco.
4. Rodar migrações para sincronizar colunas/tabelas novas:
   ```bash
   php artisan migrate
   ```
5. Limpar cache:
   ```bash
   php artisan optimize:clear
   ```

---

## 6) Execução em desenvolvimento
Em terminais separados:

```bash
php artisan serve
npm run dev
```

Validações úteis:

```bash
php artisan route:list
php artisan about
```

---

## 7) Build frontend
Para gerar assets de produção:

```bash
npm run build
```

Para limpeza de build:

```bash
npm run clean
```

---

## 8) Deploy em produção
No servidor (na pasta da aplicação):

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
```

Garantir permissões de escrita em:
- `storage/`
- `bootstrap/cache/`

---

## 9) Filas e agendador
### Worker
```bash
php artisan queue:work --tries=3 --timeout=120
```

### Cron para scheduler
```bash
* * * * * php /caminho/da/aplicacao/artisan schedule:run >> /dev/null 2>&1
```

---

## 10) Painel admin e segurança
- URL admin padrão: `/admin`
- Trocar senha padrão imediatamente.
- Produção com `APP_DEBUG=false`.
- Usar HTTPS obrigatório.
- Não expor segredos de API/chaves no repositório.

---

## 11) Gateway de pagamentos
Configurar no painel de administração os campos dos gateways.

### Mercado Pago
- `mp_is_enable`
- `mp_credit_card_enable`
- `mp_sandbox`
- `mp_access_token`
- `mp_public_key`
- `mp_webhook_key`

### Asaas
- `asaas_is_enable`
- `asaas_credit_card_enable`
- `asaas_sandbox`
- `asaas_api_key`
- `asaas_webhook_key`

Callbacks usuais:
- `/mercadopago/callback`
- `/asaas/callback`

---

## 12) Troubleshooting
### `vendor/autoload.php` ausente
```bash
composer install
```

### Frontend não atualiza
```bash
npm install
npm run build
php artisan optimize:clear
```

### Falha de banco de dados
- Revisar `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- Verificar se o MySQL/MariaDB está em execução.

### Callback não credita saldo
- Validar URL de webhook no provedor.
- Conferir chave/assinatura configurada.
- Analisar logs em `storage/logs/laravel.log`.

---

## 13) Comandos úteis
```bash
php artisan optimize:clear
php artisan migrate --force
php artisan route:list
php artisan queue:work
php artisan config:cache
php artisan view:cache
```
