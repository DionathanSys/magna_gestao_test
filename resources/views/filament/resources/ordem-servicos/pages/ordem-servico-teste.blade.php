<x-filament-panels::page>
    <style>
        .os-summary-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .os-summary-card {
            border: 1px solid rgba(148, 163, 184, 0.3);
            border-radius: 0.75rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.7);
        }
        .os-summary-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: rgb(100 116 139);
        }
        .os-summary-value {
            display: block;
            margin-top: 0.35rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: rgb(15 23 42);
        }
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
            .os-summary-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
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
    <div class="os-summary-grid">
        <div class="os-summary-card">
            <span class="os-summary-label">Itens</span>
            <span class="os-summary-value">{{ $record->itens()->count() }}</span>
        </div>
        <div class="os-summary-card">
            <span class="os-summary-label">Pendentes</span>
            <span class="os-summary-value">{{ $record->itens()->where('status', \App\Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE)->count() }}</span>
        </div>
        <div class="os-summary-card">
            <span class="os-summary-label">Agendamentos</span>
            <span class="os-summary-value">{{ $record->agendamentosPendentes()->count() }}</span>
        </div>
        <div class="os-summary-card">
            <span class="os-summary-label">Preventivas</span>
            <span class="os-summary-value">{{ $record->planoPreventivoVinculado()->count() }}</span>
        </div>
    </div>
    <div class="os-flex-container">
        <div class="os-flex-form">
            @livewire('form-teste', ['ordemServico' => $record])
        </div>
        <div class="os-flex-item">
            @livewire('list-teste', ['ordemServico' => $record])
        </div>
    </div>
</x-filament-panels::page>

