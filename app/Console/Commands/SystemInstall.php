<?php

namespace App\Console\Commands;

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

        $how = $this->provisionAttempts($installer, $db, $interactive);
        if ($how === null) {
            return ['ok' => false, 'kind' => 'provision', 'message' => 'Não consegui criar o banco e o usuário: nenhuma conta administradora do MySQL funcionou.'];
        }

        $this->info("✔ Banco \"{$db['database']}\" e usuário \"{$db['username']}\" prontos (via {$how}).");
        if ($generated) {
            $this->line("  Senha gerada para o usuário do banco: <comment>{$db['password']}</comment>  (fica gravada no .env)");
        }

        return $installer->testDatabase($db);
    }

    /** @return string|null  por onde o banco foi criado, ou null se nenhuma forma funcionou */
    private function provisionAttempts(CasinoInstaller $installer, array $db, bool $interactive): ?string
    {
        // 1) conta informada nas opções; depois root sem senha (XAMPP/Laragon/Windows,
        //    ou o comando rodando como root no Linux, quando o root entra pelo socket)
        $admins = [];
        if ($this->option('db-root-user') !== null || $this->option('db-root-pass') !== null) {
            $admins[] = ['username' => $this->option('db-root-user') ?: 'root', 'password' => (string) $this->option('db-root-pass')];
        }
        $admins[] = ['username' => 'root', 'password' => ''];
        foreach ($admins as $admin) {
            if ($installer->provisionWithAdmin($db, $admin)['ok']) {
                return "conta {$admin['username']} do MySQL";
            }
        }

        // 2) Ubuntu/Debian: o root do MariaDB/MySQL não tem senha; entra pelo socket com sudo
        if ($this->sudoMysqlAvailable()) {
            if ($interactive) {
                $this->line('  Vou usar o administrador do MySQL pelo sudo (pode pedir a senha do seu usuário do computador).');
            }
            if ($this->runSudoMysql($installer->provisionSql($db), $interactive)) {
                return 'sudo mysql';
            }
        }

        // 3) uma conta administradora informada agora
        if ($interactive) {
            for ($try = 1; $try <= 3; $try++) {
                $user = (string) $this->ask('Usuário administrador do MySQL', 'root');
                $pass = (string) ($this->secret('Senha desse usuário (ENTER se não tiver)') ?? '');
                $result = $installer->provisionWithAdmin($db, ['username' => $user, 'password' => $pass]);
                if ($result['ok']) {
                    return "conta {$user} do MySQL";
                }
                $this->warn('  ' . $result['message']);
                if ($try < 3 && !$this->confirm('Tentar outra conta ou senha?', true)) {
                    break;
                }
            }
        }

        return null;
    }

    private function sudoMysqlAvailable(): bool
    {
        if (PHP_OS_FAMILY === 'Windows' || !function_exists('proc_open')) {
            return false;
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            return false; // já é root: a conexão como root pelo socket (passo 1) cobre esse caso
        }

        return $this->commandExists('sudo') && $this->commandExists('mysql');
    }

    private function commandExists(string $command): bool
    {
        $out = [];
        $rc = 1;
        @exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $out, $rc);

        return $rc === 0 && $out !== [];
    }

    /** Roda o SQL no cliente "mysql" com sudo. O sudo pede a senha no próprio terminal (não pela entrada padrão). */
    private function runSudoMysql(string $sql, bool $interactive): bool
    {
        $command = $interactive ? ['sudo', 'mysql'] : ['sudo', '-n', 'mysql'];
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            return false;
        }

        fwrite($pipes[0], $sql);
        fclose($pipes[0]);
        $out = (string) stream_get_contents($pipes[1]);
        $err = (string) stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        if (proc_close($process) !== 0) {
            $reason = trim($err !== '' ? $err : $out);
            if ($reason !== '') {
                $this->warn('  sudo mysql: ' . Str::limit(preg_replace('/\s+/', ' ', $reason), 200));
            }
            return false;
        }

        return true;
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
