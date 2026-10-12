<?php

namespace App\Console\Commands;

use App\Console\Concerns\InteractsWithDatabaseAdmin;
use App\Install\CasinoInstaller;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Instalação pelo terminal (VPS / SSH / seu computador). Mesmas etapas do instalador web.
 *
 *   php artisan system:install
 *   php artisan system:install --url=https://site.com --db-name=casino --db-user=casino --db-pass=xxx \
 *       --admin-email=admin@site.com --admin-pass=xxxxxxxx --no-interaction
 *
 * Se o banco/usuário ainda não existirem (localhost ou VPS), o comando oferece criá-los
 * (ou cria direto com --create-db) usando o administrador do MySQL: root sem senha,
 * "sudo mysql" (Ubuntu/Debian) ou a conta informada em --db-root-user/--db-root-pass.
 */
class SystemInstall extends Command
{
    use InteractsWithDatabaseAdmin;

    protected $signature = 'system:install
        {--url= : URL do site (ex: https://meusite.com)}
        {--name= : Nome do site}
        {--db-host=localhost : Servidor MySQL}
        {--db-port=3306 : Porta do MySQL}
        {--db-name= : Nome do banco}
        {--db-user= : Usuário do banco}
        {--db-pass= : Senha do banco}
        {--create-db : Cria o banco e o usuário se ainda não existirem (seu computador ou VPS)}
        {--db-root-user= : Conta administradora do MySQL para criar o banco (padrão: root)}
        {--db-root-pass= : Senha da conta administradora do MySQL}
        {--admin-name=Administrador : Nome do administrador}
        {--admin-email= : E-mail do administrador}
        {--admin-pass= : Senha do administrador (mín. 8 caracteres)}
        {--overwrite : Apaga as tabelas existentes no banco}
        {--check : Apenas verifica os requisitos}';

    protected $description = 'Instala o sistema (banco de dados, .env e administrador)';

    public function handle(): int
    {
        $installer = new CasinoInstaller(base_path());

        $requirements = $installer->requirements();
        $this->table(['', 'Requisito', 'Detalhe'], array_map(
            fn ($i) => [$i['ok'] ? '<info>✔</info>' : ($i['required'] ? '<error>✖</error>' : '<comment>!</comment>'), $i['label'], $i['detail']],
            $requirements['items']
        ));

        if ($this->option('check')) {
            return $requirements['ok'] ? self::SUCCESS : self::FAILURE;
        }

        if (!$requirements['ok']) {
            $this->error('Corrija os requisitos acima antes de instalar.');
            return self::FAILURE;
        }

        if ($installer->isInstalled() && !$this->option('overwrite')) {
            $this->error('O sistema já está instalado (storage/installed.lock). Use --overwrite para reinstalar.');
            return self::FAILURE;
        }

        $db = [
            'host'     => $this->option('db-host') ?: 'localhost',
            'port'     => (int) ($this->option('db-port') ?: 3306),
            'database' => $this->option('db-name') ?: $this->ask('Nome do banco de dados', 'casino'),
            'username' => $this->option('db-user') ?: $this->ask('Usuário do banco (se ainda não existir, eu posso criar)', 'casino'),
            'password' => $this->option('db-pass') ?? ($this->secret('Senha do usuário do banco (ENTER = gero uma senha forte se eu criar o usuário)') ?? ''),
        ];

        $test = $installer->testDatabase($db);
        if (!$test['ok']) {
            $test = $this->provisionDatabase($installer, $db, $test);
        }
        if (!$test['ok']) {
            $this->error($test['message']);
            $this->databaseHelp($installer, $db, $test['kind'] ?? 'other');
            return self::FAILURE;
        }
        $this->info($test['message']);

        if (!empty($test['tables']) && !$this->option('overwrite')
            && !$this->confirm('O banco já possui tabelas. Apagar e reinstalar?', false)) {
            return self::FAILURE;
        }

        $url = $this->option('url') ?: $this->ask('URL do site', 'http://localhost');
        $name = $this->option('name') ?: $this->ask('Nome do site', 'Casino');
        $adminEmail = $this->option('admin-email') ?: $this->ask('E-mail do administrador');
        $adminPass = $this->option('admin-pass') ?: $this->secret('Senha do administrador (mín. 8 caracteres)');

        if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen((string) $adminPass) < 8) {
            $this->error('Informe um e-mail válido e uma senha com pelo menos 8 caracteres.');
            return self::FAILURE;
        }

        @unlink(storage_path('installed.lock'));

        $steps = [
            'Gravando .env'               => fn () => $installer->writeEnv(['app_name' => $name, 'app_url' => $url, 'db' => $db]),
            'Criando banco de dados'      => fn () => $installer->importBase($db, true),
            'Atualizando estrutura'       => fn () => $installer->migrate(),
            'Criando administrador'       => fn () => $installer->createAdmin($this->option('admin-name') ?: 'Administrador', $adminEmail, $adminPass),
            'Finalizando'                 => fn () => $installer->finish(['app_name' => $name]),
        ];

        foreach ($steps as $label => $step) {
            $failed = null;
            $this->components->task($label, function () use ($step, $installer, $label, &$failed) {
                try {
                    $step();
                    return true;
                } catch (\Throwable $e) {
                    $installer->log('Erro (CLI): ' . $e->getMessage(), ['step' => $label]);
                    $failed = $e->getMessage();
                    return false;
                }
            });

            if ($failed !== null) {
                $this->error($failed);
                return self::FAILURE;
            }
        }

        if (!$installer->isInstalled()) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Instalação concluída!');
        $this->line('Site:  ' . rtrim($url, '/'));
        $this->line('Painel: ' . rtrim($url, '/') . '/admin  (' . $adminEmail . ')');

        return self::SUCCESS;
    }

    /* ------------------------------------------------------------------ */
    /* Criação do banco e do usuário do site                                */
    /* ------------------------------------------------------------------ */

    /**
     * Quando o acesso falha num banco local, oferece criar o banco e o usuário (ou cria direto com --create-db).
     * Pode trocar o usuário (nunca root) e gerar a senha; por isso recebe $db por referência.
     */
    private function provisionDatabase(CasinoInstaller $installer, array &$db, array $test): array
    {
        $kind = $test['kind'] ?? 'other';
        if (!in_array($kind, ['access', 'unknown_db', 'no_privilege'], true) || !$installer->isLocalDatabaseHost($db['host'])) {
            return $test;
        }

        $interactive = $this->input->isInteractive();
        if (!$this->option('create-db')) {
            if (!$interactive) {
                return $test;
            }
            $this->warn($test['message']);
            $this->newLine();
            $question = strtolower((string) $db['username']) === 'root'
                ? "O site não deve usar o root do MySQL. Quer que eu crie agora o banco \"{$db['database']}\" e um usuário próprio para o site (com acesso só a esse banco)?"
                : "Quer que eu crie agora o banco \"{$db['database']}\" e o usuário \"{$db['username']}\" (com acesso só a esse banco)?";
            if (!$this->confirm($question, true)) {
                return $test;
            }
        }

        if (strtolower((string) $db['username']) === 'root' || (string) $db['username'] === '') {
            $db['username'] = $interactive
                ? (string) $this->ask('Nome do usuário do banco que vou criar', 'casino')
                : 'casino';
        }

        $generated = false;
        if ((string) $db['password'] === '') {
            $db['password'] = Str::password(24, true, true, false);
            $generated = true;
        }

        try {
            $statements = $installer->provisionStatements($db, [CasinoInstaller::class, 'sqlQuote']);
        } catch (\InvalidArgumentException $e) {
            return ['ok' => false, 'kind' => 'provision', 'message' => $e->getMessage()];
        }
        $how = $this->runAsDatabaseAdmin($installer, $db, $statements, $interactive);
        if ($how === null) {
            return ['ok' => false, 'kind' => 'provision', 'message' => 'Não consegui criar o banco e o usuário: nenhuma conta administradora do MySQL funcionou.'];
        }

        $this->info("✔ Banco \"{$db['database']}\" e usuário \"{$db['username']}\" prontos (via {$how}).");
        if ($generated) {
            $this->line("  Senha gerada para o usuário do banco: <comment>{$db['password']}</comment>  (fica gravada no .env)");
        }

        return $installer->testDatabase($db);
    }

    private function databaseHelp(CasinoInstaller $installer, array $db, string $kind): void
    {
        $name = $db['database'] ?: 'casino';
        $user = (strtolower((string) $db['username']) === 'root' || !$db['username']) ? 'casino' : $db['username'];

        $this->newLine();
        $this->line('<comment>Como resolver:</comment>');

        if ($kind === 'no_server') {
            $this->line('  • Ligue o MySQL/MariaDB e rode de novo:  sudo systemctl start mariadb  (XAMPP/Laragon: botão "Start" do MySQL)');
            $this->line('  • Ou use o assistente, que instala e liga tudo:  bash instalar.sh');
            return;
        }

        if (!$installer->isLocalDatabaseHost($db['host'])) {
            $this->line("  • Confira servidor, usuário e senha do banco em {$db['host']} (o usuário precisa de TODOS os privilégios no banco \"{$name}\").");
            return;
        }

        $this->line('  • Deixe este comando criar o banco e o usuário:  php artisan system:install --create-db');
        $this->line('  • Ou use o assistente, que faz tudo sozinho:      bash instalar.sh');
        $this->line('  • Ou crie manualmente (Ubuntu/Debian) e rode de novo informando esse usuário:');
        $this->line("      sudo mysql -e \"CREATE DATABASE {$name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
            . " CREATE USER '{$user}'@'localhost' IDENTIFIED BY 'UmaSenhaForte123';"
            . " GRANT ALL PRIVILEGES ON {$name}.* TO '{$user}'@'localhost';\"");
        $this->line('  • Hospedagem compartilhada: crie banco e usuário no painel (cPanel → Bancos de dados MySQL) e marque TODOS OS PRIVILÉGIOS.');
    }
}
