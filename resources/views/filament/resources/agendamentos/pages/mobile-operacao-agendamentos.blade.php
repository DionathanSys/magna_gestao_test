<x-filament-panels::page>
    <style>
        .ag-mobile-summary { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1rem; }
        .ag-mobile-cta > button { width: 100%; }
        .ag-mobile-mini-card { border-radius: 0.9rem; background: #fff; padding: 0.8rem 0.85rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }
        .ag-mobile-mini-label { font-size: 0.68rem; color: #64748b; }
        .ag-mobile-mini-value { margin-top: 0.1rem; font-size: 1rem; font-weight: 800; color: #0f172a; }
        .ag-mobile-search { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 0.9rem; background: #fff; padding: 0.85rem 1rem; font-size: 0.9rem; margin-bottom: 1rem; }
        .ag-mobile-tabs { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 0.35rem; margin-bottom: 1rem; }
        .ag-mobile-tab { border: 0; border-radius: 0.75rem; padding: 0.7rem 0.35rem; background: #e2e8f0; color: #475569; font-size: 0.7rem; font-weight: 700; text-align: center; }
        .ag-mobile-tab.is-active { background: #111827; color: #fff; }
        .ag-mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .ag-mobile-card { border-radius: 0.95rem; background: #fff; padding: 0.95rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }
        .ag-mobile-top { display: flex; align-items: start; justify-content: space-between; gap: 0.75rem; }
        .ag-mobile-title { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
        .ag-mobile-subtitle { margin-top: 0.15rem; font-size: 0.78rem; color: #64748b; }
        .ag-mobile-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.85rem; }
        .ag-mobile-meta-item { border-radius: 0.7rem; background: #f8fafc; padding: 0.55rem 0.65rem; }
        .ag-mobile-meta-label { display: block; font-size: 0.68rem; color: #64748b; }
        .ag-mobile-meta-value { display: block; margin-top: 0.1rem; font-size: 0.78rem; font-weight: 600; color: #0f172a; }
        .ag-mobile-pills { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.8rem; }
        .ag-mobile-pill { display: inline-flex; align-items: center; border-radius: 999px; padding: 0.15rem 0.5rem; font-size: 0.68rem; font-weight: 700; }
        .ag-mobile-pill.warn { background: #fef3c7; color: #92400e; }
        .ag-mobile-pill.info { background: #dbeafe; color: #1d4ed8; }
        .ag-mobile-pill.gray { background: #e2e8f0; color: #334155; }
        .ag-mobile-pill.danger { background: #fee2e2; color: #b91c1c; }
        .ag-mobile-actions { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.85rem; }
        .ag-mobile-empty { border-radius: 0.9rem; background: #fff; padding: 1rem; color: #64748b; text-align: center; }
        .ag-mobile-modal-backdrop { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.45); z-index: 80; display: flex; align-items: center; justify-content: center; padding: 1rem; }
        .ag-mobile-modal-panel { width: min(100%, 720px); max-height: calc(100vh - 2rem); overflow: auto; border-radius: 1rem; background: #fff; box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28); padding: 1rem; }
        .ag-mobile-modal-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; }
        .ag-mobile-modal-title { font-size: 1rem; font-weight: 700; color: #0f172a; }
        .ag-mobile-modal-actions { display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1rem; }
        @media (max-width: 640px) {
            .ag-mobile-summary { grid-template-columns: 1fr; }
        }
    </style>

    <div class="ag-mobile-summary">
        <div class="ag-mobile-cta">
            <x-filament::button type="button" wire:click="openCreateAgendamentoModal" icon="heroicon-o-plus">Novo agendamento</x-filament::button>
        </div>

        <div class="ag-mobile-mini-card">
            <div class="ag-mobile-mini-label">Em execução</div>
            <div class="ag-mobile-mini-value">{{ $this->getExecucaoCount() }}</div>
        </div>
    </div>

    <input wire:model.live.debounce.300ms="busca" class="ag-mobile-search" placeholder="Buscar placa, serviço, fornecedor ou observação">

    <div class="ag-mobile-tabs">
        <button type="button" wire:click="$set('activeTab', 'atrasados')" class="ag-mobile-tab {{ $activeTab === 'atrasados' ? 'is-active' : '' }}">Atras.<br>{{ $this->getAtrasadosCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'hoje')" class="ag-mobile-tab {{ $activeTab === 'hoje' ? 'is-active' : '' }}">Hoje<br>{{ $this->getHojeCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'execucao')" class="ag-mobile-tab {{ $activeTab === 'execucao' ? 'is-active' : '' }}">Exec.<br>{{ $this->getExecucaoCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'amanha')" class="ag-mobile-tab {{ $activeTab === 'amanha' ? 'is-active' : '' }}">Amanhã<br>{{ $this->getAmanhaCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'sem-data')" class="ag-mobile-tab {{ $activeTab === 'sem-data' ? 'is-active' : '' }}">Sem data<br>{{ $this->getSemDataCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'abertos')" class="ag-mobile-tab {{ $activeTab === 'abertos' ? 'is-active' : '' }}">Abertos<br>{{ $this->getAbertosCount() }}</button>
    </div>

    <div class="ag-mobile-list">
        @forelse ($this->agendamentos as $agendamento)
            <div class="ag-mobile-card">
                <div class="ag-mobile-top">
                    <div>
                        <div class="ag-mobile-title">{{ $agendamento->veiculo?->placa ?? 'Sem placa' }} - {{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                        <div class="ag-mobile-subtitle">{{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</div>
                    </div>

                    <x-filament::badge :color="$this->getStatusBadgeColor($agendamento)">
                        {{ $agendamento->status?->value ?? 'Sem status' }}
                    </x-filament::badge>
                </div>

                <div class="ag-mobile-meta">
                    <div class="ag-mobile-meta-item">
                        <span class="ag-mobile-meta-label">Agendado</span>
                        <span class="ag-mobile-meta-value">{{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}</span>
                    </div>
                    <div class="ag-mobile-meta-item">
                        <span class="ag-mobile-meta-label">Limite</span>
                        <span class="ag-mobile-meta-value">{{ $agendamento->data_limite?->format('d/m/Y') ?? 'Sem data' }}</span>
                    </div>
                </div>

                @if ($agendamento->observacao)
                    <div class="ag-mobile-subtitle" style="margin-top:0.75rem;">{{ $agendamento->observacao }}</div>
                @endif

                <div class="ag-mobile-pills">
                    <span class="ag-mobile-pill gray">{{ $this->formatCategoria($agendamento->categoria) }}</span>
                    @if ($agendamento->ordem_servico_id)
                        <span class="ag-mobile-pill info">OS #{{ $agendamento->ordem_servico_id }}</span>
                    @else
                        <span class="ag-mobile-pill danger">Sem OS</span>
                    @endif
                    @if ($agendamento->servico?->controla_posicao && blank($agendamento->posicao))
                        <span class="ag-mobile-pill danger">Posição pendente</span>
                    @elseif (filled($agendamento->posicao))
                        <span class="ag-mobile-pill gray">Posição {{ $agendamento->posicao }}</span>
                    @endif
                </div>

                <div class="ag-mobile-actions">
                    @if ($this->canVincular($agendamento))
                        <x-filament::button size="sm" color="primary" wire:click="vincularAgendamento({{ $agendamento->id }})">Vincular</x-filament::button>
                    @endif
                    <x-filament::button size="sm" color="warning" wire:click="openReprogramarModal({{ $agendamento->id }})">Reprogramar</x-filament::button>
                    <x-filament::button size="sm" color="success" wire:click="encerrarAgendamento({{ $agendamento->id }})">Encerrar</x-filament::button>
                    @if ($agendamento->status === \App\Enum\OrdemServico\StatusOrdemServicoEnum::PENDENTE && $agendamento->ordem_servico_id === null)
                        <x-filament::button size="sm" color="danger" wire:click="cancelarAgendamento({{ $agendamento->id }})">Cancelar</x-filament::button>
                    @endif
                    <x-filament::button size="sm" color="gray" wire:click="openEditAgendamentoModal({{ $agendamento->id }})">Editar</x-filament::button>
                    <x-filament::button size="sm" color="gray" tag="a" :href="$this->getListUrl()">Lista</x-filament::button>
                </div>
            </div>
        @empty
            <div class="ag-mobile-empty">Nenhum agendamento encontrado neste filtro.</div>
        @endforelse
    </div>

    @if ($showReprogramarModal)
        <div class="ag-mobile-modal-backdrop" wire:click.self="closeReprogramarModal">
            <div class="ag-mobile-modal-panel">
                <div class="ag-mobile-modal-header">
                    <div class="ag-mobile-modal-title">Reprogramar Agendamento</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeReprogramarModal" />
                </div>

                <form wire:submit="saveReprogramacao">
                    {{ $this->reprogramarForm }}

                    <div class="ag-mobile-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeReprogramarModal">Cancelar</x-filament::button>
                        <x-filament::button type="submit" color="primary">Salvar</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCreateAgendamentoModal)
        <div class="ag-mobile-modal-backdrop" wire:click.self="closeCreateAgendamentoModal">
            <div class="ag-mobile-modal-panel">
                <div class="ag-mobile-modal-header">
                    <div class="ag-mobile-modal-title">Novo Agendamento</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeCreateAgendamentoModal" />
                </div>

                <form wire:submit="saveCreateAgendamento">
                    {{ $this->createAgendamentoForm }}

                    <div class="ag-mobile-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeCreateAgendamentoModal">Cancelar</x-filament::button>
                        <x-filament::button type="submit" color="primary">Salvar</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showEditAgendamentoModal)
        <div class="ag-mobile-modal-backdrop" wire:click.self="closeEditAgendamentoModal">
            <div class="ag-mobile-modal-panel">
                <div class="ag-mobile-modal-header">
                    <div class="ag-mobile-modal-title">Editar Agendamento</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeEditAgendamentoModal" />
                </div>

                <form wire:submit="saveEditAgendamento">
                    {{ $this->editAgendamentoForm }}

                    <div class="ag-mobile-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeEditAgendamentoModal">Cancelar</x-filament::button>
                        <x-filament::button type="submit" color="primary">Salvar</x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
