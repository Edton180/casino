<?php

namespace App\Support\System;

use Illuminate\Support\Facades\Artisan;
use RuntimeException;

/**
 * Conclui uma atualização SEM perder dados: backup automático, site em manutenção,
 * migrações (só acrescentam/alteram estrutura), imagens padrão novas, caches e versão.
 *
 * Os arquivos do código são trocados antes (git pull / rsync no assistente, ou o ZIP novo
 * extraído por cima na hospedagem). .env, banco e uploads (public/storage) nunca são apagados.
 */
class SystemUpdater
{
    public function __construct(private BackupManager $backups)
    {
    }

    /** @return list<string> nomes das migrações que ainda não rodaram */
    public function pendingMigrations(): array
    {
        $migrator = app('migrator');
        if (!$migrator->repositoryExists()) {
            return ['(o banco ainda não tem a tabela de migrações)'];
        }

        $paths = array_merge([database_path('migrations')], $migrator->paths());
        $files = $migrator->getMigrationFiles($paths);
        $ran = $migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    /**
     * @param  callable(string $step, string $status, ?string $detail): void|null  $report  status: start|done|skip
     * @return array{backup: ?array, migrated: list<string>, from: ?string, to: string}
     */
    public function run(bool $backup = true, ?callable $report = null): array
    {
        @set_time_limit(0);
        @ignore_user_abort(true);
        $report ??= static fn (string $step, string $status, ?string $detail = null) => null;

        $result = [
            'backup'   => null,
            'migrated' => [],
            'from'     => AppVersion::installedVersion(),
            'to'       => AppVersion::code(),
        ];

        if ($backup) {
            $report('Backup do banco e dos arquivos', 'start', null);
            $result['backup'] = $this->backups->create();
            $this->backups->rotate(10);
            $report('Backup do banco e dos arquivos', 'done', $result['backup']['name']);
        } else {
            $report('Backup do banco e dos arquivos', 'skip', 'pulado (--no-backup)');
        }

        $wasDown = app()->isDownForMaintenance();
        if (!$wasDown) {
            Artisan::call('down', ['--retry' => 60, '--refresh' => 15]);
        }

        try {
            $pending = $this->pendingMigrations();
            $report('Atualizando o banco de dados', 'start', count($pending) ? count($pending) . ' migração(ões) nova(s)' : null);
            $code = Artisan::call('migrate', ['--force' => true]);
            if ($code !== 0) {
                throw new RuntimeException('As migrações falharam: ' . trim(Artisan::output()));
            }
            $result['migrated'] = $pending;
            $report('Atualizando o banco de dados', 'done', count($pending) ? count($pending) . ' aplicada(s)' : 'já estava em dia');

            $report('Arquivos padrão, caches e versão', 'start', null);
            $this->copyDefaults();
            Artisan::call('optimize:clear');
            try {
                Artisan::call('filament:upgrade');   // republica CSS/JS do painel se o Filament mudou
            } catch (\Throwable) {
                // não é essencial: os arquivos do painel já vêm no pacote
            }
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
            AppVersion::stamp(['updated_at' => date('c')]);
            $report('Arquivos padrão, caches e versão', 'done', 'versão ' . AppVersion::code());
        } finally {
            if (!$wasDown) {
                Artisan::call('up');
            }
        }

        return $result;
    }

    /** Imagens padrão (logos/banners) que versões novas trouxeram; nunca sobrescreve as existentes. */
    private function copyDefaults(): void
    {
        $source = database_path('install/defaults');
        $target = public_path('storage/defaults');
        if (!is_dir($source)) {
            return;
        }
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }
        foreach (glob($source . '/*') ?: [] as $file) {
            $dest = $target . '/' . basename($file);
            if (is_file($file) && !is_file($dest)) {
                @copy($file, $dest);
            }
        }
    }
}
