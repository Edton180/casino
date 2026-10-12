<?php

namespace App\Filament\Admin\Pages;

use App\Console\Commands\SystemBackup;
use App\Support\System\AppVersion;
use App\Support\System\BackupManager;
use App\Support\System\SystemUpdater;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * Sistema: versão, conclusão de atualizações (hospedagem sem terminal: envie os arquivos novos e
 * clique em "Concluir atualização"), backups (criar, baixar, restaurar, apagar) e desinstalação.
 * Faz o mesmo que os comandos php artisan system:update | system:backup | system:restore | system:uninstall.
 */
class SystemPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server-stack';

    protected static string $view = 'filament.pages.system-page';

    protected static ?string $navigationLabel = 'Sistema';

    protected static ?string $title = 'Sistema';

    protected static ?string $slug = 'sistema';

    public ?string $output = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    public function getSubheading(): ?string
    {
        return 'Versão, atualização sem perder dados, backups e desinstalação.';
    }

    protected function getViewData(): array
    {
        $backups = app(BackupManager::class);
        $pending = [];
        $pendingError = null;
        try {
            $pending = app(SystemUpdater::class)->pendingMigrations();
        } catch (\Throwable $e) {
            $pendingError = $e->getMessage();
        }
        $installed = AppVersion::installed();
        $free = @disk_free_space(base_path());

        return [
            'codeVersion'      => AppVersion::code(),
            'installedVersion' => AppVersion::installedVersion(),
            'installedAt'      => $installed['installed_at'] ?? null,
            'updatedAt'        => $installed['updated_at'] ?? null,
            'pending'          => $pending,
            'pendingError'     => $pendingError,
            'backups'          => $backups->list(),
            'backupDir'        => $backups->defaultDirectory(),
            'php'              => PHP_VERSION,
            'laravel'          => app()->version(),
            'environment'      => app()->environment(),
            'debug'            => (bool) config('app.debug'),
            'diskFree'         => $free === false ? null : SystemBackup::bytes((int) $free),
            'writable'         => [
                'storage'        => is_writable(storage_path()),
                'public/storage' => is_dir(public_path('storage')) ? is_writable(public_path('storage')) : is_writable(public_path()),
                'bootstrap/cache' => is_writable(base_path('bootstrap/cache')),
            ],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Atualização                                                         */
    /* ------------------------------------------------------------------ */

    public function updateAction(): Action
    {
        return Action::make('update')
            ->label('Concluir atualização')
            ->icon('heroicon-o-arrow-path')
            ->requiresConfirmation()
            ->modalHeading('Concluir a atualização')
            ->modalDescription('Faz um backup, aplica as mudanças de banco da versão nova, copia as imagens padrão novas e limpa os caches. Nenhum dado é apagado; o site fica em manutenção por alguns segundos.')
            ->modalSubmitActionLabel('Atualizar agora')
            ->action(function (): void {
                $lines = [];
                try {
                    $result = app(SystemUpdater::class)->run(true, function (string $step, string $status, ?string $detail = null) use (&$lines) {
                        if ($status !== 'start') {
                            $lines[] = ($status === 'done' ? '✔ ' : '· ') . $step . ($detail ? " — {$detail}" : '');
                        }
                    });
                    $lines[] = '';
                    $lines[] = 'Versão: ' . ($result['from'] ?? 'anterior') . ' → ' . $result['to'];
                    $this->output = implode("\n", $lines);
                    Notification::make()->title('Atualização concluída')->body('Versão ' . $result['to'] . '. Dados mantidos.')->success()->send();
                } catch (\Throwable $e) {
                    Log::error('[sistema] Falha na atualização', ['error' => $e->getMessage()]);
                    $lines[] = '✖ ' . $e->getMessage();
                    $lines[] = 'O site foi reativado. Se algo ficou errado, restaure o backup feito antes (lista abaixo).';
                    $this->output = implode("\n", $lines);
                    Notification::make()->title('A atualização falhou')->body('Veja os detalhes no quadro de saída.')->danger()->send();
                }
            });
    }

    /* ------------------------------------------------------------------ */
    /* Backups                                                             */
    /* ------------------------------------------------------------------ */

    public function backupAction(): Action
    {
        return Action::make('backup')
            ->label('Fazer backup agora')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->modalHeading('Fazer backup')
            ->modalDescription('Banco de dados, configuração (.env) e arquivos enviados num único .zip. Os 10 mais novos são mantidos.')
            ->form([
                Checkbox::make('with_files')->label('Incluir os arquivos enviados (logos, banners, imagens)')->default(true),
            ])
            ->modalSubmitActionLabel('Fazer backup')
            ->action(function (array $data): void {
                try {
                    $manager = app(BackupManager::class);
                    $backup = $manager->create(null, (bool) ($data['with_files'] ?? true));
                    $manager->rotate(10);
                    $this->output = "✔ Backup criado: {$backup['name']} (" . SystemBackup::bytes($backup['size']) . ", {$backup['tables']} tabelas, {$backup['files']} arquivo(s))";
                    Notification::make()->title('Backup criado')->body($backup['name'])->success()->send();
                } catch (\Throwable $e) {
                    $this->output = '✖ ' . $e->getMessage();
                    Notification::make()->title('O backup falhou')->body($e->getMessage())->danger()->send();
                }
            });
    }

    public function downloadBackupAction(): Action
    {
        return Action::make('downloadBackup')
            ->label('Baixar')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments) {
                $path = app(BackupManager::class)->find((string) ($arguments['name'] ?? ''));
                if ($path === null) {
                    Notification::make()->title('Backup não encontrado')->danger()->send();

                    return null;
                }

                return response()->download($path);
            });
    }

    public function restoreBackupAction(): Action
    {
        return Action::make('restoreBackup')
            ->label('Restaurar')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('warning')
            ->size('sm')
            ->modalHeading(fn (array $arguments) => 'Restaurar ' . ($arguments['name'] ?? ''))
            ->modalDescription('Os dados ATUAIS do banco e os arquivos enviados serão substituídos pelos do backup. Antes, um backup do estado atual é feito (dá para voltar atrás). Se o backup veio de outro servidor, você pode precisar entrar de novo no painel.')
            ->form([
                TextInput::make('confirm')->label('Para confirmar, digite RESTAURAR')->required(),
            ])
            ->modalSubmitActionLabel('Restaurar')
            ->action(function (array $data, array $arguments): void {
                if (mb_strtoupper(trim((string) ($data['confirm'] ?? ''))) !== 'RESTAURAR') {
                    Notification::make()->title('Confirmação incorreta')->body('Digite RESTAURAR para confirmar.')->warning()->send();

                    return;
                }
                $path = app(BackupManager::class)->find((string) ($arguments['name'] ?? ''));
                if ($path === null) {
                    Notification::make()->title('Backup não encontrado')->danger()->send();

                    return;
                }
                $code = Artisan::call('system:restore', ['file' => $path, '--force' => true]);
                $this->output = trim(Artisan::output());
                $code === 0
                    ? Notification::make()->title('Backup restaurado')->success()->send()
                    : Notification::make()->title('A restauração falhou')->body('Veja os detalhes no quadro de saída.')->danger()->send();
            });
    }

    public function deleteBackupAction(): Action
    {
        return Action::make('deleteBackup')
            ->label('Apagar')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->size('sm')
            ->requiresConfirmation()
            ->modalHeading(fn (array $arguments) => 'Apagar ' . ($arguments['name'] ?? '') . '?')
            ->modalDescription('O arquivo do backup será apagado do servidor.')
            ->action(function (array $arguments): void {
                app(BackupManager::class)->delete((string) ($arguments['name'] ?? ''))
                    ? Notification::make()->title('Backup apagado')->success()->send()
                    : Notification::make()->title('Não consegui apagar o backup')->danger()->send();
            });
    }

    /* ------------------------------------------------------------------ */
    /* Desinstalar                                                         */
    /* ------------------------------------------------------------------ */

    public function uninstallAction(): Action
    {
        return Action::make('uninstall')
            ->label('Desinstalar o sistema')
            ->icon('heroicon-o-exclamation-triangle')
            ->color('danger')
            ->modalHeading('Desinstalar o sistema')
            ->modalDescription('Faz um backup final (fora da pasta do site) e apaga as tabelas do cassino, a configuração (.env), os arquivos enviados, logs e caches. Depois disso o site volta para a tela de instalação; apague a pasta pelo gerenciador de arquivos da hospedagem se quiser remover tudo.')
            ->form([
                Checkbox::make('backup')->label('Fazer o backup final antes (recomendado)')->default(true),
                TextInput::make('password')->label('Sua senha do painel')->password()->required(),
                TextInput::make('confirm')->label('Para confirmar, digite DESINSTALAR')->required(),
            ])
            ->modalSubmitActionLabel('Desinstalar')
            ->action(function (array $data) {
                if (mb_strtoupper(trim((string) ($data['confirm'] ?? ''))) !== 'DESINSTALAR') {
                    Notification::make()->title('Confirmação incorreta')->body('Digite DESINSTALAR para confirmar.')->warning()->send();

                    return null;
                }
                if (!Hash::check((string) ($data['password'] ?? ''), (string) auth()->user()->password)) {
                    Notification::make()->title('Senha incorreta')->danger()->send();

                    return null;
                }

                $params = ['--force' => true];
                if (empty($data['backup'])) {
                    $params['--no-backup'] = true;
                }
                $code = Artisan::call('system:uninstall', $params);
                $this->output = trim(Artisan::output());
                if ($code !== 0) {
                    Notification::make()->title('A desinstalação não foi feita')->body('Veja os detalhes no quadro de saída.')->danger()->send();

                    return null;
                }

                $backup = preg_match('/(casino-backup-[0-9]{8}-[0-9]{6}\.zip)/', $this->output, $m) ? $m[1] : '';

                return redirect('/install/?desinstalado=1' . ($backup !== '' ? '&backup=' . rawurlencode($backup) : ''));
            });
    }
}
