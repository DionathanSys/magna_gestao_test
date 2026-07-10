<x-filament-panels::page>
    <form wire:submit="salvar">
        {{ $this->form }}
    </form>

    <div style="margin-top:1rem;">
        <x-filament::button wire:click="salvar" style="width:100%" icon="heroicon-o-check" size="lg">
            Criar Ordem de Serviço
        </x-filament::button>
    </div>

    <div style="margin-top:0.75rem;text-align:center;">
        <a href="{{ $this->getListUrl() }}" class="text-sm text-gray-500 hover:text-gray-700 underline">
            Voltar para lista
        </a>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>