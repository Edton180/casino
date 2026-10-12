<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Manutenção do sistema</x-slot>
        <x-slot name="description">Use depois de atualizar os arquivos do site, ou se algo parecer desatualizado.</x-slot>

        <div class="grid gap-3 md:grid-cols-3">
            <x-filament::button wire:click="runMigrate" wire:loading.attr="disabled" icon="heroicon-o-circle-stack">
                Atualizar banco de dados
            </x-filament::button>
            <x-filament::button wire:click="clearCache" wire:loading.attr="disabled" color="gray" icon="heroicon-o-arrow-path">
                Limpar cache
            </x-filament::button>
            <x-filament::button wire:click="storageLink" wire:loading.attr="disabled" color="gray" icon="heroicon-o-link">
                Criar link de uploads
            </x-filament::button>
        </div>

        @if($output)
            <pre style="margin-top:16px;overflow-x:auto;white-space:pre-wrap;border-radius:10px;background:#0b1120;color:#e5e7eb;padding:14px;font-size:12px;line-height:1.5">{{ $output }}</pre>
        @endif
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">WorldSlot — importar catálogo</x-slot>
        <x-slot name="description">Requer as credenciais em Configurações → Chaves dos Jogos. Importe os provedores primeiro e depois os jogos (os jogos novos entram ativos).</x-slot>

        <div class="grid gap-3 md:grid-cols-4">
            <x-filament::button wire:click="loadProvider('slot')" wire:loading.attr="disabled" color="gray">Provedores (Slot)</x-filament::button>
            <x-filament::button wire:click="loadProvider('casino')" wire:loading.attr="disabled" color="gray">Provedores (Cassino)</x-filament::button>
            <x-filament::button wire:click="loadProvider('pachinko')" wire:loading.attr="disabled" color="gray">Provedores (Pachinko)</x-filament::button>
            <x-filament::button wire:click="loadGames" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadGames">Importar jogos</span>
                <span wire:loading wire:target="loadGames">Importando...</span>
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">EverGame — importar catálogo</x-slot>
        <x-slot name="description">Requer as credenciais em Configurações → Chaves dos Jogos.</x-slot>

        <div class="grid gap-3 md:grid-cols-2">
            <x-filament::button wire:click="loadProviderEvergame" wire:loading.attr="disabled" color="gray">Importar provedores</x-filament::button>
            <x-filament::button wire:click="loadGamesEvergame" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadGamesEvergame">Importar jogos</span>
                <span wire:loading wire:target="loadGamesEvergame">Importando...</span>
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
