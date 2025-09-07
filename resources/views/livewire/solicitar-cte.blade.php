<div>
     <form wire:submit="create">
        {{ $this->form }}

        <div style="margin-top: 2rem;">
            <x-filament::button wire:click="handle" outlined>
                Enviar
            </x-filament::button>
        </div>
    </form>

    <x-filament-actions::modals />


</div>
