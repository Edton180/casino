<?php

namespace App\Filament\Admin\Pages;

use App\Traits\Providers\EvergameTrait;
use App\Traits\Providers\WorldSlotTrait;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Artisan;
use Livewire\WithFileUploads;

class AdvancedPage extends Page implements HasForms
{
    use InteractsWithForms, WorldSlotTrait, WithFileUploads, EvergameTrait;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.advanced-page';

    protected static ?string $navigationLabel = 'Opções Avançada';

    protected static ?string $modelLabel = 'Opções Avançada';

    protected static ?string $title = 'Opções Avançada';

    protected static ?string $slug = 'advanced-options';

    public ?array $data = [];
    public $output;

    /**
     * @dev @victormsalatiel
     * @return bool
     */
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    /**
     * @return void
     */
    public function mount(): void
    {

    }

    /**
     * @param $type
     * @return void
     */
    public function loadProvider($type)
    {
        Notification::make()
            ->title('Sucesso')
            ->body('Provedores Carregados com sucesso')
            ->success()
            ->send();
    }


    /**
     * @return void
     */
    public function loadGames()
    {
        Notification::make()
            ->title('Sucesso')
            ->body('Jogos Carregados com sucesso')
            ->success()
            ->send();
    }




    /**
     * @return void
     */

    public function loadProviderEvergame()
    {
        Notification::make()
            ->title('Sucesso')
            ->body('Provedores Carregados com sucesso')
            ->success()
            ->send();
    }


    /**
     * @return void
     */
    public function loadGamesEvergame()
    {
        Notification::make()
            ->title('Sucesso')
            ->body('Jogos Carregados com sucesso')
            ->success()
            ->send();
    }

    /**
     * @return void
     */
    public function upload()
    {

    }

    /**
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {

        return $data;
    }

    /**
     * @param Form $form
     * @return Form
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Atualização')
                    ->description('Carregue aqui seu arquivo de atualização no formato zip')
                    ->schema([
                        FileUpload::make('update')
                    ])

            ])
            ->statePath('data');
    }

    /**
     * @return void
     */
    public function submit(): void
    {

                Notification::make()
                    ->title('Sr."HACKER":')
                    ->body('Lambe meus ovo.')
                    ->success()
                    ->send();

    }

    /**
     * @return void
     */
    public function runMigrate()
    {
        
        Notification::make()
            ->title('Sr."HACKER":')
            ->body('Lambe meus ovo.')
            ->success()
            ->send();
    }

    /**
     * @return void
     */
    public function runMigrateWithSeed()
    {

        Notification::make()
            ->title('Sr."HACKER":')
            ->body('Lambe meus ovo.')
            ->success()
            ->send();
    }
}
