<?php

namespace App\Console\Commands;

use App\Support\System\BackupManager;
use Illuminate\Console\Command;

/**
 * Backup completo (banco + .env + arquivos enviados) num único .zip.
 *
 *   php artisan system:backup                     → storage/app/backups (mantém os 10 mais novos)
 *   php artisan system:backup --output=~/backups  → em outra pasta
 *   php artisan system:backup --list              → lista os backups
 */
class SystemBackup extends Command
{
    protected $signature = 'system:backup
        {--output= : Pasta onde salvar (padrão: storage/app/backups)}
        {--keep=10 : Quantos backups manter na pasta padrão}
        {--no-files : Só o banco e o .env, sem os arquivos enviados}
        {--list : Lista os backups da pasta padrão}';

    protected $description = 'Faz o backup do banco de dados, do .env e dos arquivos enviados';

    public function handle(BackupManager $backups): int
    {
        if ($this->option('list')) {
            $items = $backups->list();
            if (!$items) {
                $this->line('Nenhum backup em ' . $backups->defaultDirectory());
                return self::SUCCESS;
            }
            $this->table(['Arquivo', 'Data', 'Tamanho', 'Versão', 'Linhas'], array_map(fn ($b) => [
                $b['name'],
                date('d/m/Y H:i', $b['created_at']),
                self::bytes($b['size']),
                $b['manifest']['version'] ?? '?',
                $b['manifest']['rows'] ?? '?',
            ], $items));
            return self::SUCCESS;
        }

        $output = $this->option('output') ? self::expandHome((string) $this->option('output')) : null;

        try {
            $result = $backups->create($output, !$this->option('no-files'), fn (string $m) => $this->line("  · {$m}..."));
        } catch (\Throwable $e) {
            $this->error('Falha no backup: ' . $e->getMessage());
            return self::FAILURE;
        }

        if ($output === null) {
            $backups->rotate(max(1, (int) $this->option('keep')));
        }

        $this->info("✔ Backup salvo: {$result['path']}");
        $this->line(sprintf('  %s · %d tabelas · %d linhas · %d arquivos · %ss',
            self::bytes($result['size']), $result['tables'], $result['rows'], $result['files'], $result['seconds']));
        $this->line('  Para restaurar: php artisan system:restore ' . ($output === null ? $result['name'] : $result['path']));

        return self::SUCCESS;
    }

    public static function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }

        return number_format($value, $i ? 1 : 0, ',', '.') . ' ' . $units[$i];
    }

    public static function expandHome(string $path): string
    {
        $home = getenv('HOME') ?: getenv('USERPROFILE') ?: '';
        if ($home !== '' && ($path === '~' || str_starts_with($path, '~/'))) {
            return $home . substr($path, 1);
        }

        return $path;
    }
}
