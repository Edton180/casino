<?php

namespace App\Console\Commands;

use App\Console\Concerns\InteractsWithDatabaseAdmin;
use App\Install\CasinoInstaller;
use App\Support\System\BackupManager;
use App\Support\System\Uninstaller;
use Illuminate\Console\Command;

/**
 * Desinstala o sistema desta pasta: backup final, tabelas do cassino, .env, uploads, logs e caches.
 * Com --drop-database também apaga o banco e o usuário do MySQL (precisa de conta administradora).
 * O código continua na pasta; o assistente (bash instalar.sh --uninstall) também apaga a pasta,
 * o servidor web, o agendador e o que mais ele tiver instalado.
 */
class SystemUninstall extends Command
{
    use InteractsWithDatabaseAdmin;

    protected $signature = 'system:uninstall
        {--force : Não pergunta nada}
        {--no-backup : Não faz o backup final}
        {--backup-dir= : Pasta do backup final (padrão: ~/casino-backups)}
        {--keep-database : Não apaga as tabelas do banco}
        {--drop-database : Apaga também o banco e o usuário do MySQL (precisa de conta administradora)}
        {--keep-uploads : Mantém os arquivos enviados (public/storage)}
        {--db-root-user= : Conta administradora do MySQL (padrão: root)}
        {--db-root-pass= : Senha da conta administradora do MySQL}';

    protected $description = 'Desinstala o sistema (banco, .env, uploads, logs e caches), com backup final';

    public function handle(Uninstaller $uninstaller, BackupManager $backups): int
    {
        $hasEnv = is_file(base_path('.env'));
        $plan = $hasEnv ? $uninstaller->plan() : ['path' => base_path(), 'database' => null, 'host' => null, 'db_user' => null, 'tables' => null, 'foreign' => [], 'uploads' => $uninstaller->measure(public_path('storage')), 'version' => null];
        $interactive = $this->input->isInteractive() && !$this->option('force');

        $dbLine = !$hasEnv ? 'sem .env: o banco não será tocado'
            : ($plan['tables'] === null ? "{$plan['database']} em {$plan['host']} (inacessível: não será tocado)"
            : "{$plan['database']} em {$plan['host']} — {$plan['tables']} tabela(s) do cassino");
        $this->table(['', ''], [
            ['Pasta', $plan['path']],
            ['Versão', $plan['version'] ?? '?'],
            ['Banco', $dbLine],
            ['Usuário do banco', $plan['db_user'] ?? '-'],
            ['Arquivos enviados', $plan['uploads']['files'] . ' arquivo(s), ' . SystemBackup::bytes($plan['uploads']['bytes']) . ($this->option('keep-uploads') ? ' (mantidos)' : '')],
        ]);
        if ($plan['foreign']) {
            $this->warn('O banco também tem tabelas de outro sistema; elas NÃO serão apagadas: ' . implode(', ', array_slice($plan['foreign'], 0, 8)) . (count($plan['foreign']) > 8 ? '...' : ''));
        }

        $this->line('Será feito:');
        $this->line('  1. ' . ($this->option('no-backup') ? 'sem backup final (--no-backup)' : 'backup final em ' . $this->backupDirectory()));
        $this->line('  2. ' . match (true) {
            !$hasEnv || $plan['tables'] === null => 'banco: nada',
            (bool) $this->option('keep-database') => 'banco: mantido (--keep-database)',
            (bool) $this->option('drop-database') => 'apagar o banco "' . $plan['database'] . '"' . ($plan['db_user'] && $uninstaller->canDropUser($plan['db_user']) ? ' e o usuário "' . $plan['db_user'] . '"' : ''),
            default => 'apagar as tabelas do cassino no banco "' . $plan['database'] . '"',
        });
        $this->line('  3. apagar .env, ' . ($this->option('keep-uploads') ? '' : 'arquivos enviados, ') . 'logs, caches e a trava do instalador');

        if ($interactive) {
            $answer = (string) $this->ask('Para confirmar, digite DESINSTALAR');
            if (mb_strtoupper(trim($answer)) !== 'DESINSTALAR') {
                $this->line('Cancelado.');
                return self::FAILURE;
            }
        } elseif (!$this->option('force')) {
            $this->error('Sem terminal para confirmar: use --force.');
            return self::FAILURE;
        }

        // 1. backup final (fora da pasta do site, que será limpa)
        $backup = null;
        if (!$this->option('no-backup') && $hasEnv && $plan['tables'] !== null) {
            try {
                $backup = $backups->create($this->backupDirectory(), true, fn (string $m) => $this->line("  · {$m}..."));
                $this->info("✔ Backup final: {$backup['path']} (" . SystemBackup::bytes($backup['size']) . ')');
            } catch (\Throwable $e) {
                $this->error('O backup final falhou: ' . $e->getMessage());
                $this->line('Nada foi apagado. Use --backup-dir=OUTRA_PASTA ou --no-backup.');
                return self::FAILURE;
            }
        }

        // 2. banco
        if ($hasEnv && $plan['tables'] !== null && !$this->option('keep-database')) {
            $dropped = false;
            if ($this->option('drop-database') && !$plan['foreign']) {
                $installer = new CasinoInstaller(base_path());
                $db = ['host' => $plan['host'], 'port' => (int) config('database.connections.' . config('database.default') . '.port', 3306), 'database' => $plan['database']];
                try {
                    $statements = $uninstaller->dropDatabaseStatements((string) $plan['database'], $plan['db_user']);
                    \Illuminate\Support\Facades\DB::purge();
                    $how = $this->runAsDatabaseAdmin($installer, $db, $statements, $interactive);
                    if ($how !== null) {
                        $dropped = true;
                        $this->info("✔ Banco \"{$plan['database']}\"" . ($uninstaller->canDropUser((string) $plan['db_user']) ? " e usuário \"{$plan['db_user']}\"" : '') . " apagados (via {$how}).");
                    } else {
                        $this->warn('Não consegui entrar como administrador do MySQL; vou apagar só as tabelas.');
                    }
                } catch (\InvalidArgumentException $e) {
                    $this->warn($e->getMessage() . ' Vou apagar só as tabelas.');
                }
            } elseif ($this->option('drop-database')) {
                $this->warn('O banco tem tabelas de outro sistema: ele não será apagado, só as tabelas do cassino.');
            }

            if (!$dropped) {
                $result = $uninstaller->dropTables();
                $this->info("✔ {$result['dropped']} tabela(s) do cassino apagadas" . ($result['kept'] ? ' (' . count($result['kept']) . ' de outro sistema mantidas)' : '') . '.');
            }
        }

        // 3. arquivos gerados pela instalação
        $removed = $uninstaller->removeFiles((bool) $this->option('keep-uploads'));
        $this->info('✔ Removidos: ' . implode(', ', $removed));

        $this->newLine();
        $this->info('Desinstalação concluída.');
        if ($backup) {
            $this->line("  Backup: {$backup['path']}");
            $this->line('  Para voltar a usar com estes dados: instale de novo e rode  php artisan system:restore ' . $backup['path']);
        }
        $this->line('  O código continua em ' . base_path() . '. Para apagar a pasta também: bash instalar.sh --uninstall (Linux/macOS) ou apague a pasta.');

        return self::SUCCESS;
    }

    private function backupDirectory(): string
    {
        if ($this->option('backup-dir')) {
            return SystemBackup::expandHome((string) $this->option('backup-dir'));
        }
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
        if ($home !== '' && is_dir($home) && is_writable($home) && !str_starts_with(realpath($home) . DIRECTORY_SEPARATOR, base_path() . DIRECTORY_SEPARATOR)) {
            return rtrim($home, '/\\') . DIRECTORY_SEPARATOR . 'casino-backups';
        }

        return dirname(base_path()) . DIRECTORY_SEPARATOR . 'casino-backups';
    }
}
