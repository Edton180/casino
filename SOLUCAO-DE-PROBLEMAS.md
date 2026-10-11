# 🛠️ Solução de problemas

Logs: `storage/logs/laravel.log` (aplicação) e `storage/logs/installer.log` (instalador).
Para ver o erro na tela temporariamente: no `.env`, `APP_DEBUG=true` (volte para `false` depois).

## Instalação

| Sintoma | Causa / solução |
|---|---|
| Página "Faltam as dependências do sistema (pasta vendor)" | Você enviou o código-fonte. Use o **`casino-pronto-para-hospedagem.zip`** da página *Releases*, ou rode `composer install --no-dev` via SSH. |
| `/install/` dá 404 | O `.htaccess` da raiz não foi enviado (arquivos ocultos!). No gerenciador de arquivos ative "mostrar arquivos ocultos" e confira `public_html/.htaccess`. No Nginx, a raiz do site deve ser a pasta `public`. |
| Requisito "Extensão X" em vermelho | Ative a extensão em *Selecionar versão do PHP → Extensões* (cPanel) / *Configuração PHP* (Hostinger). |
| "usuário ou senha incorretos" no banco | Confira usuário/senha. No cPanel o nome tem prefixo (`usuario_casino`). |
| "o usuário não tem permissão neste banco" | cPanel → Bancos MySQL → *Adicionar usuário ao banco* → **TODOS OS PRIVILÉGIOS**. |
| "O banco já possui tabelas" | Use um banco vazio ou marque *Apagar dados existentes* (apaga tudo!). |
| Instalação parou numa etapa | A mensagem mostra o motivo; detalhes em `storage/logs/installer.log`. Clique em **Tentar novamente**. |
| Quero reinstalar | Apague `storage/installed.lock` e acesse `/install/`. |

## Depois de instalar

| Sintoma | Causa / solução |
|---|---|
| Erro 500 em todas as páginas | Permissão: `storage` e `bootstrap/cache` precisam ser graváveis (775). Rode `php artisan optimize:clear` (ou apague `bootstrap/cache/*.php` exceto `packages.php`/`services.php`). |
| Página em branco / "Vite manifest not found" | Falta `public/build`. Use o pacote pronto ou rode `npm ci && npm run build`. |
| Imagens enviadas no painel não aparecem | A pasta `public/storage` precisa existir e ser gravável (o instalador cria). |
| Login do painel não entra | Use o e-mail/senha definidos no instalador em `/admin`. Para dar acesso a outra pessoa: painel → *Usuários → Editar* → função **admin**. |
| "Este jogo está indisponível no momento" | Jogo de provedor sem chaves configuradas ou provedor fora do ar. Veja [JOGOS.md](JOGOS.md). |
| "Faça login para jogar" | Sessão expirada; entre novamente. |
| Depósito fica "aguardando pagamento" | Webhook não cadastrado no gateway ou URL errada. Veja [PAGAMENTOS.md](PAGAMENTOS.md). O status também é consultado a cada 5 s enquanto o QR Code está aberto. |
| Stripe não credita | O *Signing secret* (`whsec_...`) do webhook é obrigatório. |
| Cashback não libera | O cron (`php artisan schedule:run`) precisa rodar a cada minuto. |
| HTTPS dá "conteúdo misto" | `APP_URL` no `.env` deve começar com `https://`. Depois: `php artisan optimize:clear`. |

## VPS

| Sintoma | Solução |
|---|---|
| Certbot falhou | O DNS (registro A) do domínio precisa apontar para o servidor. Depois: `certbot --nginx -d seu-dominio -m seu@email --agree-tos --redirect` |
| 502 Bad Gateway | `systemctl restart php8.3-fpm nginx` |
| Ver diagnóstico | `sudo bash auto-install.sh --diagnose` |
| Atualizar | `sudo bash auto-install.sh --update` |
