<?php

namespace App\Filament\Admin\Pages;

use App\Models\Game;
use App\Models\GamesKey;
use App\Models\Provider;
use App\Traits\Providers\EvergameTrait;
use App\Traits\Providers\WorldSlotTrait;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/**
 * Manutenção: importar provedores/jogos das APIs e tarefas do sistema
 * (migrações, limpeza de cache, link de uploads).
 */
class AdvancedPage extends Page
{
    use WorldSlotTrait, EvergameTrait;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static string $view = 'filament.pages.advanced-page';

    protected static ?string $navigationLabel = 'Opções Avançadas';

    protected static ?string $modelLabel = 'Opções Avançadas';

    protected static ?string $title = 'Opções Avançadas';

    protected static ?string $slug = 'advanced-options';

    public ?string $output = null;

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public function loadProvider(string $type): void
    {
        if (!in_array($type, ['slot', 'casino', 'pachinko'], true)) {
            return;
        }

        $this->import('WorldSlot', ['worldslot_agent_code', 'worldslot_agent_token', 'worldslot_api_endpoint'], 'worldslot',
            fn () => self::getProviderWorldslot($type), Provider::class);
    }

    public function loadGames(): void
    {
        $this->import('WorldSlot', ['worldslot_agent_code', 'worldslot_agent_token', 'worldslot_api_endpoint'], 'worldslot',
            fn () => self::getGamesWorldslot(), Game::class);
    }

    public function loadProviderEvergame(): void
    {
        $this->import('EverGame', ['evergame_agent_code', 'evergame_agent_token', 'evergame_api_endpoint'], 'evergame',
            fn () => self::getProviderEvergame(), Provider::class);
    }

    public function loadGamesEvergame(): void
    {
        $this->import('EverGame', ['evergame_agent_code', 'evergame_agent_token', 'evergame_api_endpoint'], 'evergame',
            fn () => self::getGamesEvergame(), Game::class);
    }

    public function runMigrate(): void
    {
        $this->artisan('migrate', ['--force' => true], 'Banco de dados atualizado');
    }

    public function clearCache(): void
    {
        $this->artisan('optimize:clear', [], 'Cache limpo');
    }

    public function storageLink(): void
    {
        if (file_exists(public_path('storage'))) {
            Notification::make()->title('Link de uploads')->body('O link public/storage já existe.')->success()->send();
            return;
        }

        $this->artisan('storage:link', [], 'Link de uploads criado');
    }

    /**
     * Chama o importador de um provedor e informa quantos registros foram criados.
     */
    private function import(string $label, array $keys, string $distribution, callable $loader, string $model): void
    {
        $settings = GamesKey::first();
        foreach ($keys as $key) {
            if (empty($settings?->getAttributes()[$key] ?? null)) {
                Notification::make()
                    ->title($label . ' não configurado')
                    ->body('Preencha as credenciais em Configurações → Chaves dos Jogos.')
                    ->warning()
                    ->send();
                return;
            }
        }

        @set_time_limit(0);
        $before = $model::where('distribution', $distribution)->count();

        try {
            $loader();
        } catch (\Throwable $e) {
            Log::error('[advanced] Falha ao importar ' . $label, ['error' => $e->getMessage()]);
            Notification::make()->title('Erro ao importar')->body('Não foi possível falar com a API ' . $label . '. Confira as credenciais e o endpoint.')->danger()->send();
            return;
        }

        $created = $model::where('distribution', $distribution)->count() - $before;
        $what    = $model === Game::class ? 'jogo(s)' : 'provedor(es)';

        Notification::make()
            ->title('Importação concluída')
            ->body($created > 0 ? "{$created} {$what} novo(s) de {$label}." : "Nenhum {$what} novo retornado por {$label}.")
            ->success()
            ->send();
    }

    private function artisan(string $command, array $params, string $title): void
    {
        try {
            Artisan::call($command, $params);
            $this->output = trim(Artisan::output()) ?: 'Concluído.';
            Notification::make()->title($title)->success()->send();
        } catch (\Throwable $e) {
            $this->output = $e->getMessage();
            Notification::make()->title('Erro')->body('O comando falhou; veja a saída abaixo.')->danger()->send();
        }
    }
}
