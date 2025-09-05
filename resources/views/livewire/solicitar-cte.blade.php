<div>
     <form wire:submit="create">
        {{ $this->form }}

<div style="margin-top: 2rem;">
        <x-filament::button wire:click="create" outlined>
            Salvar Alterações
        </x-filament::button>
    </div>
    </form>

    <x-filament-actions::modals />


</div>
