<x-filament-panels::page>
    <div class="space-y-6">
        {{ $this->form }}

        <div class="flex gap-3">
            <x-filament::button
                wire:click="gerarRelatorio"
                icon="heroicon-o-arrow-down-tray"
            >
                Baixar PDF
            </x-filament::button>

            <x-filament::button
                wire:click="visualizarRelatorio"
                color="info"
                icon="heroicon-o-eye"
            >
                Visualizar PDF
            </x-filament::button>
        </div>
    </div>
</x-filament-panels::page>
