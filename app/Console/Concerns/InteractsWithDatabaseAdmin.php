<?php

namespace App\Console\Concerns;

use App\Install\CasinoInstaller;
use Illuminate\Support\Str;

/**
 * Executa SQL com uma conta administradora do MySQL/MariaDB, do jeito mais simples que funcionar:
 *  1. conta informada em --db-root-user/--db-root-pass;
 *  2. root sem senha (XAMPP, Laragon, Windows; ou o comando rodando como root no Linux, pelo socket);
 *  3. "sudo mysql" (Ubuntu/Debian: o root do banco não tem senha própria) — pede a senha do usuário do computador;
 *  4. uma conta digitada agora (modo interativo).
 *
 * Quem usa precisa declarar as opções --db-root-user e --db-root-pass.
 */
trait InteractsWithDatabaseAdmin
{
    /**
     * @param  list<string>  $statements
     * @return string|null  por onde os comandos rodaram, ou null se nenhuma conta funcionou
     */
    protected function runAsDatabaseAdmin(CasinoInstaller $installer, array $db, array $statements, bool $interactive): ?string
    {
        $admins = [];
        if ($this->option('db-root-user') !== null || $this->option('db-root-pass') !== null) {
            $admins[] = ['username' => $this->option('db-root-user') ?: 'root', 'password' => (string) $this->option('db-root-pass')];
        }
        $admins[] = ['username' => 'root', 'password' => ''];
        foreach ($admins as $admin) {
            if ($installer->adminExecute($db, $admin, $statements)['ok']) {
                return "conta {$admin['username']} do MySQL";
            }
        }

        if ($this->sudoMysqlAvailable() && $installer->isLocalDatabaseHost($db['host'] ?? 'localhost')) {
            if ($interactive) {
                $this->line('  Vou usar o administrador do MySQL pelo sudo (pode pedir a senha do seu usuário do computador).');
            }
            if ($this->runSudoMysql(implode(";\n", $statements) . ";\n", $interactive)) {
                return 'sudo mysql';
            }
        }

        if ($interactive) {
            for ($try = 1; $try <= 3; $try++) {
                $user = (string) $this->ask('Usuário administrador do MySQL', 'root');
                $pass = (string) ($this->secret('Senha desse usuário (ENTER se não tiver)') ?? '');
                $result = $installer->adminExecute($db, ['username' => $user, 'password' => $pass], $statements);
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

    protected function sudoMysqlAvailable(): bool
    {
        if (PHP_OS_FAMILY === 'Windows' || !function_exists('proc_open')) {
            return false;
        }
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            return false; // já é root: a conexão como root pelo socket (passo 2) cobre esse caso
        }

        return $this->commandExists('sudo') && $this->commandExists('mysql');
    }

    protected function commandExists(string $command): bool
    {
        $out = [];
        $rc = 1;
        @exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null', $out, $rc);

        return $rc === 0 && $out !== [];
    }

    /** Roda o SQL no cliente "mysql" com sudo. O sudo pede a senha no próprio terminal (não pela entrada padrão). */
    protected function runSudoMysql(string $sql, bool $interactive): bool
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
            if ($reason !== '' && $interactive) {
                $this->warn('  sudo mysql: ' . Str::limit((string) preg_replace('/\s+/', ' ', $reason), 200));
            }
            return false;
        }

        return true;
    }
}
