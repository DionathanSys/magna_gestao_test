<x-filament-panels::page>
    <form wire:submit="save" class="fi-page-content">
        {{ $this->form }}
        <small class="text-success">
            Last update: {{ $this->lastUpdatedAt(timezone: 'UTC', format: 'F j, Y, H:i:s') . ' UTC' ?? 'Never' }}
        </small>
    </form>
</x-filament-panels::page>