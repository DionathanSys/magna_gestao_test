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
        .os-secondary-lists {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        .os-list-panel {
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            padding: 1rem;
        }
        .os-list-title {
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        .os-simple-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        .os-simple-item {
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
            padding-bottom: 0.75rem;
        }
        .os-simple-item:last-child {
            border-bottom: 0;
            padding-bottom: 0;
        }
        .os-simple-item strong {
            display: block;
            font-size: 0.9rem;
        }
        .os-simple-item span {
            display: block;
            font-size: 0.82rem;
            color: rgb(71 85 105);
            margin-top: 0.2rem;
        }
        .os-empty-list {
            font-size: 0.85rem;
            color: rgb(100 116 139);
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
        @media (min-width: 900px) {
            .os-secondary-lists {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
    <div class="os-flex-container">
        <div class="os-flex-form">
            @livewire('form-teste', ['ordemServico' => $record])
        </div>
        <div class="os-flex-item">
            @livewire('list-teste', ['ordemServico' => $record])

            <div class="os-secondary-lists">
                <section class="os-list-panel">
                    <div class="os-list-title">Agendamentos Pendentes</div>

                    @if ($record->agendamentosPendentes->isEmpty())
                        <div class="os-empty-list">Nenhum agendamento pendente.</div>
                    @else
                        <div class="os-simple-list">
                            @foreach ($record->agendamentosPendentes->sortBy('data_agendamento') as $agendamento)
                                <div class="os-simple-item">
                                    <strong>{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</strong>
                                    <span>Data: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}</span>
                                    <span>Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? 'Sem data' }}</span>
                                    <span>Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</span>
                                    @if ($agendamento->observacao)
                                        <span>Obs.: {{ $agendamento->observacao }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>

                <section class="os-list-panel">
                    <div class="os-list-title">Planos Preventivos Vinculados</div>

                    @if ($record->planoPreventivoVinculado->isEmpty())
                        <div class="os-empty-list">Nenhum plano preventivo vinculado.</div>
                    @else
                        <div class="os-simple-list">
                            @foreach ($record->planoPreventivoVinculado as $planoVinculado)
                                <div class="os-simple-item">
                                    <strong>{{ $planoVinculado->planoPreventivo?->descricao ?? 'Plano não informado' }}</strong>
                                    <span>Plano ID: {{ $planoVinculado->plano_preventivo_id }}</span>
                                    <span>Intervalo: {{ $planoVinculado->planoPreventivo?->intervalo ?? 'N/A' }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </div>
    </div>
</x-filament-panels::page>
