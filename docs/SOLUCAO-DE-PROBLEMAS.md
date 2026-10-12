# 🛠️ Solução de problemas

Logs: `storage/logs/laravel.log` (aplicação) e `storage/logs/installer.log` (instalador).
Para ver o erro na tela temporariamente: no `.env`, `APP_DEBUG=true` (volte para `false` depois).

## Instalação

| Sintoma | Causa / solução |
|---|---|
| Página "Faltam as dependências do sistema (pasta vendor)" | Você enviou o código-fonte. Use o **`casino-pronto-para-hospedagem.zip`** da página *Releases*, ou rode `composer install --no-dev` via SSH. |
| `/install/` dá 404 | O `.htaccess` da raiz não foi enviado (arquivos ocultos!). No gerenciador de arquivos ative "mostrar arquivos ocultos" e confira `public_html/.htaccess`. No Nginx, a raiz do site deve ser a pasta `public`. |
| Requisito "Extensão X" em vermelho | Ative a extensão em *Selecionar versão do PHP → Extensões* (cPanel) / *Configuração PHP* (Hostinger). |
| "acesso negado" / *Access denied* **1045** ou **1698** no banco | O usuário do banco não existe ou a senha está errada. **Hospedagem:** confira usuário/senha (no cPanel o nome tem prefixo, ex.: `usuario_casino`). **Seu computador/VPS:** rode `php artisan system:install` e aceite *"Quer que eu crie agora o banco e o usuário?"* (ou use `--create-db`), ou use `bash instalar.sh`, que cria tudo sozinho. O **1698** aparece ao usar `root` no Ubuntu/Debian: o root do MySQL só entra pelo terminal com `sudo`; o site precisa de um usuário próprio. |
| "o servidor do banco não respondeu" (erro 2002) | O MySQL/MariaDB está desligado: `sudo systemctl start mariadb` (XAMPP/Laragon: botão *Start* do MySQL). |
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
| Login do painel não entra | Use o e-mail/senha definidos no instalador em `/admin`. Para dar acesso a outra pessoa: painel → *Usuários → Editar* → **Funções: admin**. |
| "Este jogo está indisponível no momento" | Jogo de provedor sem chaves configuradas ou provedor fora do ar. Veja [JOGOS.md](JOGOS.md). |
| "Faça login para jogar" | Sessão expirada; entre novamente. |
| Depósito fica "aguardando pagamento" | Webhook não cadastrado no gateway ou URL errada. Veja [PAGAMENTOS.md](PAGAMENTOS.md). O status também é consultado a cada 5 s enquanto o QR Code está aberto. |
| Stripe não credita | O *Signing secret* (`whsec_...`) do webhook é obrigatório. |
| Cashback não libera | O cron (`php artisan schedule:run`) precisa rodar a cada minuto. |
| HTTPS dá "conteúdo misto" | `APP_URL` no `.env` deve começar com `https://`. Depois: `php artisan optimize:clear`. |

## Esqueci a senha do admin

Se outro administrador tiver acesso: *Administração → Usuários → (usuário) → Alterar senha*.
Sem nenhum acesso, rode na pasta do site (SSH, terminal do cPanel ou do computador):

```bash
php artisan tinker --execute="App\Models\User::where('email','seu@email.com')->firstOrFail()->update(['password' => 'NovaSenha123']);"
```

A senha é criptografada automaticamente. Depois entre em `/admin` com a nova senha.

## Assistente de instalação (`instalar.sh`, `instalar-windows.bat`)

| Sintoma | Solução |
|---|---|
| Não consigo entrar no `/admin` ("Essas credenciais não correspondem") | Redefina o administrador e confira o login: `php artisan system:admin --email=SEU@EMAIL --password=SUASENHA` (dentro da pasta do site; use o e-mail e a senha que quiser, mín. 8 caracteres). O comando mostra o banco usado e diz se o login passou a funcionar. O instalador já faz essa conferência no final. |
| "Opção desconhecida: --stack" (ou outra opção) | A pasta `casino` é uma cópia **antiga**. Atualize: `git pull` (ou rode o comando de 1 linha de [INSTALACAO.md](INSTALACAO.md#instalação-em-1-comando-recomendado), que já usa a versão mais nova). O `instalar.sh` atualiza a cópia git sozinho. |
| `curl ... 404` ao baixar o instalador | O repositório está privado ou o endereço está errado. Com o repositório **público** use: `curl -fsSL https://raw.githubusercontent.com/Edton180/casino/main/instalar.sh \| bash -s -- --auto`. Se estiver privado: baixe o ZIP logado no GitHub ou use `--token`. |
| "Não consegui baixar o código" | Sem internet/GitHub bloqueado, ou repositório privado (use `--token`). |
| "PHP não encontrado" / faltam extensões | Rode `bash instalar.sh`: ele mostra o que falta e instala com apt (pede sua senha). No macOS: `brew install php composer mariadb`. |
| "Não rode o modo local com sudo" | Rode sem `sudo`: `bash instalar.sh`. Ele pede a senha só quando precisa instalar pacotes. |
| "Senha do usuário root do MySQL" / "Não consegui acessar o MySQL como administrador" | O assistente tenta, nesta ordem: `root` sem senha, `sudo mysql` (pede a **senha do seu usuário do computador**, não do MySQL), a conta de manutenção do Debian e, por último, a senha do root do MySQL (3 tentativas). No Ubuntu/Debian normalmente basta a senha do `sudo`. Se nada funcionar, ele mostra os comandos para criar o banco e o usuário à mão e como rodar de novo com `--db-user`/`--db-pass`. |
| O apt está ocupado / "Could not get lock" | Outra atualização está rodando. Aguarde uns minutos (ou `sudo dpkg --configure -a`) e rode de novo. |
| Faltou memória durante o Composer | Crie um swap de 2 GB (o assistente mostra o comando) e rode de novo. |
| "A porta 80 já está em uso" (VPS/`--stack`) | Se for o Apache, o assistente oferece desativá-lo. Ou use `--port 8080`. |
| O site não abre em `localhost:8000` | Use o endereço mostrado no fim (`http://127.0.0.1:8000`). Para ver o estado: `bash instalar.sh --status`; para tudo: `bash instalar.sh --diagnose`. |
| Quero outra porta / acessar do celular | `bash instalar.sh --stop`, depois `bash instalar.sh --start --port 8080 --lan`. |
| Windows: "O Windows protegeu o computador" | *Mais informações → Executar assim mesmo* (o arquivo é do seu repositório). |
| Windows: "O usuário root do MySQL tem senha" | Digite a senha do root do seu MySQL/MariaDB (o assistente confere antes de instalar). Sem perguntas: `scripts\install-windows.ps1 -DbUsuario root -DbSenha SUA_SENHA`. |
| Windows: MySQL não encontrado | Inicie o MySQL do XAMPP/Laragon, ou deixe o assistente instalar o MariaDB (winget). Para outro MySQL: `-DbUsuario root -DbSenha SENHA`. |
| Docker: o site não abre | A 1ª subida leva alguns minutos. Acompanhe com `docker compose logs -f app`. |
| XAMPP: requisito *intl*, *zip* ou *gd* em vermelho | Em `C:\xampp\php\php.ini` tire o `;` de `extension=intl`, `extension=zip` e `extension=gd` e rode de novo. |

O registro completo de cada execução fica em `/tmp/casino-install-AAAAMMDD-HHMMSS.log` (no Windows: `%TEMP%`).

## VPS

| Sintoma | Solução |
|---|---|
| Certbot falhou | O DNS (registro A) do domínio precisa apontar para o servidor. Depois: `certbot --nginx -d seu-dominio -m seu@email --agree-tos --redirect` |
| 502 Bad Gateway | `systemctl restart php8.3-fpm nginx` |
| Ver diagnóstico | `sudo bash auto-install.sh --diagnose` |
| Atualizar | `sudo bash auto-install.sh --update` |
| "A porta 80 já está em uso por 'apache2'" | O servidor já tem Apache. Pare-o (`systemctl disable --now apache2`) ou use `--port 8080`. |
| Nginx não iniciou | `nginx -t` mostra o erro de configuração; `journalctl -u nginx` mostra o motivo. |
