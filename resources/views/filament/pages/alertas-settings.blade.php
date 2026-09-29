<x-filament-panels::page>
    <form wire:submit="save" class="fi-page-content">
        {{ $this->form }}
        <small class="text-success">
            {{ $this->lastUpdatedAt(timezone: 'UTC', format: 'd/m/Y H:i:s') ?? 'Nunca atualizado' }}
        </small>
    </form>
</x-filament-panels::page>
