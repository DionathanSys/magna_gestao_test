<x-filament-panels::page>
    <style>
        .os-flex-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            align-items: flex-start;
        }
        .os-flex-form {
            width: 100%;
            min-width: 0;
        }
        .os-flex-item {
            width: 100%;
            min-width: 0;
        }
        @media (min-width: 640px) { /* sm breakpoint */
            .os-flex-container {
                flex-direction: row;
            }
            .os-flex-form {
                width: 30%;
                min-width: 0;
            }
            .os-flex-item {
                width: 70%;
            }
        }
    </style>
    <div class="os-flex-container">
        <div class="os-flex-form">
            @livewire('form-teste', ['ordemServico' => $record])
        </div>
        <div class="os-flex-item">
            @livewire('list-teste', ['ordemServico' => $record])
        </div>
    </div>
</x-filament-panels::page>


