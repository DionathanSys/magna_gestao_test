<div>
    <form wire:submit="create">
        {{ $this->form }}


    </form>



    <x-filament-actions::modals />

    <div style="margin-top: 2rem;">
        <x-filament::button wire:click="edit" outlined>
            Salvar Alterações
        </x-filament::button>
    </div>
</div>
