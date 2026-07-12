<x-filament-panels::page>
    <style>
        .ag-op-shell { display: flex; flex-direction: column; gap: 1rem; }
        .ag-op-top { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.75rem; }
        .ag-op-kpi { border-radius: 1rem; padding: 1rem; background: linear-gradient(180deg, #fff, #f8fafc); border: 1px solid rgba(148, 163, 184, 0.2); }
        .ag-op-kpi strong { display: block; font-size: 1.4rem; color: #0f172a; }
        .ag-op-kpi span { display: block; margin-top: 0.2rem; font-size: 0.82rem; color: #64748b; }
        .ag-op-search { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 0.9rem; background: #fff; padding: 0.85rem 1rem; font-size: 0.9rem; }
        .ag-op-bottom { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 1rem; }
        .ag-op-panel { border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 1rem; background: #fff; overflow: hidden; }
        .ag-op-header { padding: 1rem 1rem 0.85rem; border-bottom: 1px solid rgba(226, 232, 240, 0.9); display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; }
        .ag-op-title { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
        .ag-op-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 2rem; padding: 0.2rem 0.55rem; border-radius: 999px; font-size: 0.75rem; font-weight: 700; background: #e2e8f0; color: #334155; }
        .ag-op-list { display: flex; flex-direction: column; }
        .ag-op-item { padding: 1rem; border-bottom: 1px solid rgba(241, 245, 249, 0.95); }
        .ag-op-item:last-child { border-bottom: 0; }
        .ag-op-item-title { font-size: 0.9rem; font-weight: 700; color: #0f172a; }
        .ag-op-meta { margin-top: 0.25rem; font-size: 0.8rem; color: #64748b; }
        .ag-op-pills { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.5rem; }
        .ag-op-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.72rem; font-weight: 700; }
        .ag-op-pill.warn { background: #fef3c7; color: #92400e; }
        .ag-op-pill.info { background: #dbeafe; color: #1d4ed8; }
        .ag-op-pill.gray { background: #e2e8f0; color: #334155; }
        .ag-op-pill.danger { background: #fee2e2; color: #b91c1c; }
        .ag-op-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-top: 0.75rem; }
        .ag-op-empty { padding: 1rem; font-size: 0.85rem; color: #94a3b8; }
        .ag-op-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 80; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .ag-op-modal-panel { width: min(100%, 720px); max-height: calc(100vh - 2rem); overflow: auto; border-radius: 1rem; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); padding: 1rem; }
        .ag-op-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .ag-op-modal-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .ag-op-modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
        @media (max-width: 1024px) {
            .ag-op-top, .ag-op-bottom { grid-template-columns: 1fr 1fr; }
        }
        @media (max-width: 640px) {
            .ag-op-top, .ag-op-bottom { grid-template-columns: 1fr; }
        }
    </style>

    @php
        $topSections = [
            'Atrasados' => ['items' => $this->atrasados, 'count' => $this->resumo['atrasados'], 'pill' => 'danger'],
            'Hoje' => ['items' => $this->hoje, 'count' => $this->resumo['hoje'], 'pill' => 'info'],
            'Amanhã' => ['items' => $this->amanha, 'count' => $this->resumo['amanha'], 'pill' => 'info'],
        ];

        $bottomSections = [
            'Sem Data' => ['items' => $this->semData, 'count' => $this->resumo['sem_data'], 'pill' => 'gray'],
            'Checklist' => ['items' => $this->checklist, 'count' => $this->resumo['checklist'], 'pill' => 'warn'],
            'Próximos Dias' => ['items' => $this->alemDeAmanha, 'count' => $this->resumo['alem_de_amanha'], 'pill' => 'info'],
        ];
    @endphp

    <div class="ag-op-shell">
        <input wire:model.live.debounce.300ms="busca" class="ag-op-search" placeholder="Buscar por placa, serviço, fornecedor ou observação">

        <div class="ag-op-top">
            @foreach ($topSections as $title => $section)
                <section class="ag-op-panel">
                    <div class="ag-op-header">
                        <div class="ag-op-title">{{ $title }}</div>
                        <span class="ag-op-badge">{{ $section['count'] }}</span>
                    </div>

                    @if ($section['items']->isEmpty())
                        <div class="ag-op-empty">Nenhum item nesta fila.</div>
                    @else
                        <div class="ag-op-list">
                            @foreach ($section['items'] as $agendamento)
                                <div class="ag-op-item">
                                    <div class="ag-op-item-title">{{ $agendamento->veiculo?->placa ?? 'Sem veículo' }} · {{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                                    <div class="ag-op-meta">Agendado: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }} · Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? 'Sem data' }}</div>
                                    <div class="ag-op-meta">Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</div>
                                    @if ($agendamento->observacao)
                                        <div class="ag-op-meta">Obs.: {{ $agendamento->observacao }}</div>
                                    @endif
                                    <div class="ag-op-pills">
                                        <span class="ag-op-pill {{ $section['pill'] }}">{{ $title }}</span>
                                        <span class="ag-op-pill gray">{{ $this->formatCategoria($agendamento->categoria) }}</span>
                                        @if ($agendamento->ordem_servico_id)
                                            <span class="ag-op-pill info">OS #{{ $agendamento->ordem_servico_id }}</span>
                                        @else
                                            <span class="ag-op-pill danger">Sem OS</span>
                                        @endif
                                        @if ($agendamento->servico?->controla_posicao && blank($agendamento->posicao))
                                            <span class="ag-op-pill danger">Posição pendente</span>
                                        @elseif (filled($agendamento->posicao))
                                            <span class="ag-op-pill gray">Posição {{ $agendamento->posicao }}</span>
                                        @endif
                                    </div>
                                    <div class="ag-op-actions">
                                        @if ($this->canVincular($agendamento))
                                            <x-filament::button size="xs" color="primary" wire:click="vincularAgendamento({{ $agendamento->id }})">Vincular OS</x-filament::button>
                                        @endif
                                        <x-filament::button size="xs" color="warning" wire:click="openReprogramarModal({{ $agendamento->id }})">Reprogramar</x-filament::button>
                                        <x-filament::button size="xs" color="success" wire:click="encerrarAgendamento({{ $agendamento->id }})">Encerrar</x-filament::button>
                                        @if ($agendamento->status === \App\Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $agendamento->ordem_servico_id === null)
                                            <x-filament::button size="xs" color="danger" wire:click="cancelarAgendamento({{ $agendamento->id }})">Cancelar</x-filament::button>
                                        @endif
                                        <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Resources\Agendamentos\AgendamentoResource::getUrl('edit', ['record' => $agendamento->id])">Abrir</x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>

        <div class="ag-op-bottom">
            @foreach ($bottomSections as $title => $section)
                <section class="ag-op-panel">
                    <div class="ag-op-header">
                        <div class="ag-op-title">{{ $title }}</div>
                        <span class="ag-op-badge">{{ $section['count'] }}</span>
                    </div>

                    @if ($section['items']->isEmpty())
                        <div class="ag-op-empty">Nenhum item nesta fila.</div>
                    @else
                        <div class="ag-op-list">
                            @foreach ($section['items'] as $agendamento)
                                <div class="ag-op-item">
                                    <div class="ag-op-item-title">{{ $agendamento->veiculo?->placa ?? 'Sem veículo' }} · {{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                                    <div class="ag-op-meta">Agendado: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }} · Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? 'Sem data' }}</div>
                                    <div class="ag-op-meta">Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</div>
                                    @if ($agendamento->observacao)
                                        <div class="ag-op-meta">Obs.: {{ $agendamento->observacao }}</div>
                                    @endif
                                    <div class="ag-op-pills">
                                        <span class="ag-op-pill {{ $section['pill'] }}">{{ $title }}</span>
                                        <span class="ag-op-pill gray">{{ $this->formatCategoria($agendamento->categoria) }}</span>
                                        @if ($agendamento->ordem_servico_id)
                                            <span class="ag-op-pill info">OS #{{ $agendamento->ordem_servico_id }}</span>
                                        @else
                                            <span class="ag-op-pill danger">Sem OS</span>
                                        @endif
                                        @if ($agendamento->servico?->controla_posicao && blank($agendamento->posicao))
                                            <span class="ag-op-pill danger">Posição pendente</span>
                                        @elseif (filled($agendamento->posicao))
                                            <span class="ag-op-pill gray">Posição {{ $agendamento->posicao }}</span>
                                        @endif
                                    </div>
                                    <div class="ag-op-actions">
                                        @if ($this->canVincular($agendamento))
                                            <x-filament::button size="xs" color="primary" wire:click="vincularAgendamento({{ $agendamento->id }})">Vincular OS</x-filament::button>
                                        @endif
                                        <x-filament::button size="xs" color="warning" wire:click="openReprogramarModal({{ $agendamento->id }})">Reprogramar</x-filament::button>
                                        <x-filament::button size="xs" color="success" wire:click="encerrarAgendamento({{ $agendamento->id }})">Encerrar</x-filament::button>
                                        @if ($agendamento->status === \App\Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $agendamento->ordem_servico_id === null)
                                            <x-filament::button size="xs" color="danger" wire:click="cancelarAgendamento({{ $agendamento->id }})">Cancelar</x-filament::button>
                                        @endif
                                        <x-filament::button size="xs" color="gray" tag="a" :href="\App\Filament\Resources\Agendamentos\AgendamentoResource::getUrl('edit', ['record' => $agendamento->id])">Abrir</x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </div>

    @if ($showReprogramarModal)
        <div class="ag-op-modal-backdrop" wire:click.self="closeReprogramarModal">
            <div class="ag-op-modal-panel">
                <div class="ag-op-modal-header">
                    <div class="ag-op-modal-title">Reprogramar Agendamento</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeReprogramarModal" />
                </div>

                <form wire:submit="saveReprogramacao">
                    {{ $this->reprogramarForm }}

                    <div class="ag-op-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeReprogramarModal">Cancelar</x-filament::button>
                        <x-filament::button type="submit" color="primary">Salvar</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCreateAgendamentoModal)
        <div class="ag-op-modal-backdrop" wire:click.self="closeCreateAgendamentoModal">
            <div class="ag-op-modal-panel">
                <div class="ag-op-modal-header">
                    <div class="ag-op-modal-title">Novo Agendamento</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeCreateAgendamentoModal" />
                </div>

                <form wire:submit="saveCreateAgendamento">
                    {{ $this->createAgendamentoForm }}

                    <div class="ag-op-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeCreateAgendamentoModal">Cancelar</x-filament::button>
                        <x-filament::button type="submit" color="primary">Salvar</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
