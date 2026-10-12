<?php

namespace App\Console\Commands;

use App\Install\CasinoInstaller;
use App\Support\System\AppVersion;
use App\Support\System\SystemUpdater;
use Illuminate\Console\Command;

/**
 * Conclui a atualização depois que os arquivos novos foram copiados (git pull, assistente ou ZIP
 * extraído por cima na hospedagem). Não apaga dados: faz backup, aplica as migrações novas,
 * limpa caches e registra a versão.
 *
 *   php artisan system:update            → backup + migrações + caches
 *   php artisan system:update --check    → só mostra a versão e o que falta aplicar
 */
class SystemUpdate extends Command
{
    protected $signature = 'system:update
        {--check : Só mostra a versão instalada e as migrações pendentes}
        {--no-backup : Não faz o backup antes de atualizar}
        {--force : Não pergunta nada}';

    protected $description = 'Atualiza o banco e os caches para a versão nova do código, sem perder dados (com backup automático)';

    public function handle(SystemUpdater $updater): int
    {
        if (!(new CasinoInstaller(base_path()))->isInstalled()) {
            $this->error('O sistema ainda não está instalado. Use: php artisan system:install');
            return self::FAILURE;
        }

        $from = AppVersion::installedVersion() ?? 'anterior a 9.4';
        $to = AppVersion::code();
        $pending = $updater->pendingMigrations();

        $this->line("Versão instalada: <info>{$from}</info>   código: <info>{$to}</info>");
        $this->line('Migrações pendentes: ' . (count($pending) ? '<comment>' . count($pending) . '</comment>' : 'nenhuma'));
        foreach ($pending as $migration) {
            $this->line("  · {$migration}");
        }

        if ($this->option('check')) {
            return self::SUCCESS;
        }

        if (!$this->option('force') && $this->input->isInteractive()
            && !$this->confirm('Atualizar agora? (o site fica em manutenção por alguns segundos)', true)) {
            $this->line('Cancelado.');
            return self::FAILURE;
        }

        $current = null;
        try {
            $result = $updater->run(!$this->option('no-backup'), function (string $step, string $status, ?string $detail) use (&$current) {
                $current = $step;
                if ($status === 'start') {
                    $this->line("  · {$step}...");
                } elseif ($status === 'done') {
                    $this->line("  <info>✔</info> {$step}" . ($detail ? " — {$detail}" : ''));
                } else {
                    $this->line("  <comment>-</comment> {$step}" . ($detail ? " — {$detail}" : ''));
                }
            });
        } catch (\Throwable $e) {
            $this->error("Falha em \"{$current}\": " . $e->getMessage());
            $this->line('O site foi reativado. Nenhum dado foi apagado.');
            $this->line('Se precisar voltar ao estado anterior: php artisan system:restore  (escolha o backup mais recente)');
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('✔ Sistema atualizado' . ($result['from'] && $result['from'] !== $result['to'] ? " de {$result['from']} para {$result['to']}" : " (versão {$result['to']})") . '.');
        if ($result['backup']) {
            $this->line('  Backup feito antes: ' . $result['backup']['path']);
        }

        return self::SUCCESS;
    }
}
