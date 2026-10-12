<?php

namespace App\Console\Commands;

use App\Support\System\BackupManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Restaura um backup (banco + arquivos enviados) e aplica as migrações mais novas que ele.
 * Antes de restaurar, salva um backup do estado atual (dá para voltar atrás).
 *
 *   php artisan system:restore                         → escolhe entre os backups da pasta padrão
 *   php artisan system:restore casino-backup-....zip   → um backup da pasta padrão
 *   php artisan system:restore /caminho/backup.zip     → qualquer arquivo
 */
class SystemRestore extends Command
{
    protected $signature = 'system:restore
        {file? : Arquivo .zip do backup (padrão: escolher entre os existentes)}
        {--no-files : Restaura só o banco, sem os arquivos enviados}
        {--keep-keys : Não copia APP_KEY/JWT_SECRET/PRIVATE_KEY do backup para o .env}
        {--full-env : Usa o .env do backup inteiro (mantém a conexão com o banco atual)}
        {--no-safety-backup : Não faz o backup do estado atual antes de restaurar}
        {--force : Não pergunta nada}';

    protected $description = 'Restaura um backup feito com system:backup (banco de dados e arquivos)';

    public function handle(BackupManager $backups): int
    {
        $path = $this->resolveFile($backups);
        if ($path === null) {
            return self::FAILURE;
        }

        $manifest = $backups->manifest($path);
        if (($manifest['format'] ?? null) !== BackupManager::FORMAT) {
            $this->error("{$path} não é um backup do Casino Platform.");
            return self::FAILURE;
        }

        $this->table(['', ''], [
            ['Arquivo', $path],
            ['Criado em', $manifest['created_at'] ?? '?'],
            ['Versão', $manifest['version'] ?? '?'],
            ['Banco de origem', ($manifest['database'] ?? '?') . ' (' . ($manifest['tables'] ?? '?') . ' tabelas, ' . ($manifest['rows'] ?? '?') . ' linhas)'],
            ['Arquivos enviados', $this->option('no-files') ? 'não serão restaurados' : (($manifest['files'] ?? 0) . ' arquivo(s)')],
        ]);
        $this->warn('Os dados ATUAIS do banco serão substituídos pelos do backup.');

        if (!$this->option('force') && !$this->confirm('Restaurar este backup?', false)) {
            $this->line('Cancelado.');
            return self::FAILURE;
        }

        if (!$this->option('no-safety-backup')) {
            try {
                $safety = $backups->create();
                $this->line("  · Estado atual salvo antes (para voltar atrás): {$safety['name']}");
            } catch (\Throwable $e) {
                $this->error('Não consegui salvar o estado atual antes de restaurar: ' . $e->getMessage());
                $this->line('Use --no-safety-backup para restaurar mesmo assim.');
                return self::FAILURE;
            }
        }

        $wasDown = app()->isDownForMaintenance();
        if (!$wasDown) {
            Artisan::call('down', ['--retry' => 60]);
        }

        try {
            $result = $backups->restore($path, [
                'files'    => !$this->option('no-files'),
                'keys'     => !$this->option('keep-keys'),
                'full_env' => (bool) $this->option('full-env'),
            ], fn (string $m) => $this->line("  · {$m}..."));
        } catch (\Throwable $e) {
            $this->error('Falha na restauração: ' . $e->getMessage());
            if (isset($safety)) {
                $this->line("O estado anterior está em {$safety['path']} (php artisan system:restore {$safety['name']}).");
            }
            return self::FAILURE;
        } finally {
            if (!$wasDown) {
                Artisan::call('up');
            }
        }

        $this->info('✔ Backup restaurado.');
        $this->line("  {$result['tables']} tabelas, {$result['files']} arquivo(s); .env: {$result['env']}.");

        return self::SUCCESS;
    }

    private function resolveFile(BackupManager $backups): ?string
    {
        $file = $this->argument('file');
        if ($file) {
            $file = SystemBackup::expandHome((string) $file);
            if (is_file($file)) {
                return realpath($file) ?: $file;
            }
            if ($found = $backups->find($file)) {
                return $found;
            }
            $this->error("Backup não encontrado: {$file}");
            return null;
        }

        $items = $backups->list();
        if (!$items) {
            $this->error('Nenhum backup em ' . $backups->defaultDirectory() . '. Informe o arquivo: php artisan system:restore /caminho/backup.zip');
            return null;
        }
        if (!$this->input->isInteractive()) {
            return $items[0]['path'];
        }
        $choices = array_map(fn ($b) => $b['name'] . '  (' . date('d/m/Y H:i', $b['created_at']) . ', ' . SystemBackup::bytes($b['size']) . ')', $items);
        $chosen = $this->choice('Qual backup restaurar?', $choices, 0);

        return $items[array_search($chosen, $choices, true)]['path'];
    }
}
