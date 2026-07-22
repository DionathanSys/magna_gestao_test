<x-filament-panels::page>
    <style>
        .os-flex-container {
            display: flex;
            flex-direction: column;
            gap: 2rem;
            align-items: flex-start;
        }
        [x-cloak] {
            display: none !important;
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
        }
        .os-collapsible {
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.7);
            overflow: hidden;
        }
        .os-collapsible + .os-collapsible {
            margin-top: 1rem;
        }
        .os-collapsible-summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            cursor: pointer;
            list-style: none;
            padding: 1rem;
        }
        .os-collapsible-summary::-webkit-details-marker {
            display: none;
        }
        .os-collapsible-icon {
            color: rgb(100 116 139);
            font-size: 0.85rem;
            transition: transform 0.2s ease;
        }
        .os-collapsible[open] .os-collapsible-icon {
            transform: rotate(180deg);
        }
        .os-collapsible-body {
            padding: 0 1rem 1rem;
        }
        .os-tab-list {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
        }
        .os-tab-button {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 999px;
            padding: 0.45rem 0.8rem;
            background: rgb(248 250 252);
            font-size: 0.82rem;
            font-weight: 600;
            color: rgb(51 65 85);
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .os-tab-button.is-active {
            background: rgb(37 99 235);
            border-color: rgb(37 99 235);
            color: #fff;
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
        .os-item-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 0.65rem;
        }
        .os-filter-row {
            display: block;
            margin-bottom: 0.9rem;
        }
        .os-filter-input {
            width: 100%;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 0.75rem;
            padding: 0.7rem 0.85rem;
            background: #fff;
            font-size: 0.85rem;
        }
        .os-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            margin-top: 0.45rem;
            width: fit-content;
        }
        .os-pill-checklist { background: #fef3c7; color: #92400e; }
        .os-pill-manual { background: #e2e8f0; color: #334155; }
        .os-pill-reagendamento { background: #dbeafe; color: #1d4ed8; }
        .os-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            z-index: 80;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .os-modal-panel {
            width: min(100%, 980px);
            max-height: calc(100vh - 2rem);
            overflow: auto;
            border-radius: 1rem;
            background: #fff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.28);
            padding: 1rem;
        }
        .os-modal-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .os-modal-title {
            font-size: 1rem;
            font-weight: 700;
            color: rgb(15 23 42);
        }
        .os-modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1rem;
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
                <details class="os-collapsible" open>
                    <summary class="os-collapsible-summary">
                        <span class="os-list-title">Agendamentos Já Vinculados Nesta OS</span>
                        <span class="os-collapsible-icon">▼</span>
                    </summary>

                    <div class="os-collapsible-body">
                        @if ($this->agendamentosDestaOs->isEmpty())
                            <div class="os-empty-list">Nenhum agendamento foi vinculado a esta ordem ainda.</div>
                        @else
                            <div class="os-simple-list">
                                @foreach ($this->agendamentosDestaOs as $agendamento)
                                    <div class="os-simple-item">
                                        <strong>{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</strong>
                                        <span class="os-pill os-pill-{{ strtolower($agendamento->categoria?->value ?? 'manual') }}">
                                            {{ $agendamento->categoria?->value ?? 'MANUAL' }}
                                        </span>
                                        <span>Status: {{ $agendamento->status?->value ?? 'N/A' }}</span>
                                        <span>Agendado para: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}</span>
                                        <span>Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</span>
                                        @if ($agendamento->observacao)
                                            <span>Obs.: {{ $agendamento->observacao }}</span>
                                        @endif
                                        <div class="os-item-actions">
                                            <x-filament::button size="xs" color="gray" tag="a" :href="$this->getAgendamentoEditUrl($agendamento->id)">
                                                Abrir
                                            </x-filament::button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>

                <details class="os-collapsible" open>
                    <summary class="os-collapsible-summary">
                        <span class="os-list-title">Outras Pendências Abertas do Veículo</span>
                        <span class="os-collapsible-icon">▼</span>
                    </summary>

                    <div class="os-collapsible-body">
                        <div class="os-filter-row">
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="agendamentoBusca"
                                class="os-filter-input"
                                placeholder="Buscar por serviço, fornecedor, observação..."
                            >
                        </div>

                        @if ($this->agendamentosVeiculo->isEmpty())
                            <div class="os-empty-list">Nenhum agendamento pendente.</div>
                        @else
                            <div class="os-simple-list">
                                @foreach ($this->agendamentosVeiculo as $agendamento)
                                    <div class="os-simple-item">
                                        <strong>{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</strong>
                                        <span class="os-pill os-pill-{{ strtolower($agendamento->categoria?->value ?? 'manual') }}">
                                            {{ $agendamento->categoria?->value ?? 'MANUAL' }}
                                        </span>
                                        <span>Data: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}</span>
                                        <span>Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? 'Sem data' }}</span>
                                        <span>Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}</span>
                                        @if ($agendamento->observacao)
                                            <span>Obs.: {{ $agendamento->observacao }}</span>
                                        @endif
                                        <div class="os-item-actions">
                                            <x-filament::button size="xs" color="primary" wire:click="vincularAgendamento({{ $agendamento->id }})">
                                                Vincular
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="gray" wire:click="openEditAgendamentoModal({{ $agendamento->id }})">
                                                Editar
                                            </x-filament::button>
                                            <x-filament::button size="xs" color="danger" wire:click="cancelarAgendamento({{ $agendamento->id }})">
                                                Cancelar
                                            </x-filament::button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>

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

                <section class="os-list-panel">
                    <div class="os-list-title">Apontamentos da Oficina</div>

                    <div class="os-simple-list" style="margin-top: 0.75rem;">
                        <div class="os-simple-item">
                            <strong>Trabalhando agora</strong>

                            @if ($record->apontamentosAbertosOficina->isEmpty())
                                <span>Nenhum apontamento em aberto.</span>
                            @else
                                @foreach ($record->apontamentosAbertosOficina->sortBy('iniciado_em') as $apontamento)
                                    <span>
                                        {{ trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo . ' - ' : '') . ($apontamento->colaborador?->nome ?? 'Responsável não informado')) }}
                                        desde {{ $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-' }}
                                        @if ($apontamento->iniciado_em)
                                            ({{ $apontamento->iniciado_em->diffForHumans(now(), true) }})
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        </div>

                        <div class="os-simple-item">
                            <strong>Histórico</strong>

                            @if ($record->apontamentosOficina->isEmpty())
                                <span>Nenhum apontamento registrado.</span>
                            @else
                                @foreach ($record->apontamentosOficina->sortByDesc('iniciado_em') as $apontamento)
                                    @php
                                        $servicos = $apontamento->itens
                                            ->map(fn ($item) => trim(($item->servico?->codigo ? $item->servico->codigo . ' - ' : '') . ($item->servico?->descricao ?? '')))
                                            ->filter()
                                            ->join(', ');
                                    @endphp

                                    <div class="os-simple-item">
                                        <strong>{{ trim(($apontamento->colaborador?->codigo ? $apontamento->colaborador->codigo . ' - ' : '') . ($apontamento->colaborador?->nome ?? 'Responsável não informado')) }}</strong>
                                        <span>Início: {{ $apontamento->iniciado_em?->format('d/m/Y H:i') ?? '-' }}</span>
                                        <span>Fim: {{ $apontamento->encerrado_em?->format('d/m/Y H:i') ?? 'Aberto' }}</span>
                                        <span>
                                            Duração:
                                            @if ($apontamento->iniciado_em && $apontamento->encerrado_em)
                                                {{ $apontamento->iniciado_em->diffForHumans($apontamento->encerrado_em, true) }}
                                            @elseif ($apontamento->iniciado_em)
                                                {{ $apontamento->iniciado_em->diffForHumans(now(), true) }} em andamento
                                            @else
                                                -
                                            @endif
                                        </span>
                                        <span>Serviços: {{ $servicos ?: '-' }}</span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </section>

                <section class="os-list-panel" x-data="{ activeTab: 'vinculados' }">
                    <div class="os-list-title">Custos</div>

                    <div class="os-tab-list" role="tablist" aria-label="Abas de custos">
                        <button type="button" class="os-tab-button" :class="{ 'is-active': activeTab === 'vinculados' }" x-on:click="activeTab = 'vinculados'">
                            Vinculados ({{ $record->manutencaoLancamentos->count() }})
                        </button>
                        <button type="button" class="os-tab-button" :class="{ 'is-active': activeTab === 'pendentes' }" x-on:click="activeTab = 'pendentes'">
                            Pendentes ({{ $this->lancamentosPendentes->count() }})
                        </button>
                    </div>

                    <div x-show="activeTab === 'vinculados'" x-cloak>
                        @if ($record->manutencaoLancamentos->isEmpty())
                            <div class="os-empty-list">Nenhum custo vinculado.</div>
                        @else
                            <div class="os-simple-list">
                                @foreach ($record->manutencaoLancamentos->sortByDesc('data_negociacao') as $lancamento)
                                    <div class="os-simple-item">
                                        <strong>{{ $lancamento->produto }}</strong>
                                        <span>Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}</span>
                                        <span>Origem: {{ $lancamento->origem ?? '-' }} | Nro: {{ $lancamento->nr_os_nf ?: '-' }}</span>
                                        <span>Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}</span>
                                        <span>Valor: R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                                        <span>Vínculo: {{ $lancamento->tipo_vinculo === 'automatico' ? 'Automático' : 'Manual' }}</span>
                                        <div class="os-item-actions">
                                            <x-filament::button size="xs" color="danger" wire:click="desvincularLancamento({{ $lancamento->id }})">
                                                Desvincular
                                            </x-filament::button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div x-show="activeTab === 'pendentes'" x-cloak>
                        @if ($this->lancamentosPendentes->isEmpty())
                            <div class="os-empty-list">Nenhum custo pendente para este veículo.</div>
                        @else
                            <div class="os-simple-list">
                                @foreach ($this->lancamentosPendentes as $lancamento)
                                    <div class="os-simple-item">
                                        <strong>{{ $lancamento->produto }}</strong>
                                        <span>Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}</span>
                                        <span>Origem: {{ $lancamento->origem ?? '-' }} | Nro: {{ $lancamento->nr_os_nf ?: '-' }}</span>
                                        <span>Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}</span>
                                        <span>Valor: R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                                    <div class="os-item-actions">
                                        <x-filament::button size="xs" color="primary" wire:click="vincularLancamento({{ $lancamento->id }})">
                                            Vincular nesta OS
                                        </x-filament::button>
                                        <x-filament::button size="xs" color="warning" wire:click="dispensarLancamento({{ $lancamento->id }})">
                                            Dispensar
                                        </x-filament::button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    </div>
                </section>
            </div>
        </div>
    </div>

    @if ($showEditAgendamentoModal)
        <div class="os-modal-backdrop" wire:click.self="closeEditAgendamentoModal">
            <div class="os-modal-panel">
                <div class="os-modal-header">
                    <div class="os-modal-title">Editar Pendência Aberta</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeEditAgendamentoModal" />
                </div>

                <form wire:submit="saveEditAgendamento">
                    {{ $this->editAgendamentoForm }}

                    <div class="os-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeEditAgendamentoModal">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button type="submit" color="primary">
                            Salvar
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @if ($showCreateAgendamentoModal)
        <div class="os-modal-backdrop" wire:click.self="closeCreateAgendamentoModal">
            <div class="os-modal-panel">
                <div class="os-modal-header">
                    <div class="os-modal-title">{{ $reagendandoItemServicoId ? 'Reagendar Serviço' : 'Novo Agendamento' }}</div>
                    <x-filament::icon-button icon="heroicon-o-x-mark" color="gray" label="Fechar modal" wire:click="closeCreateAgendamentoModal" />
                </div>

                <form wire:submit="saveCreateAgendamento">
                    {{ $this->createAgendamentoForm }}

                    <div class="os-modal-actions">
                        <x-filament::button type="button" color="gray" wire:click="closeCreateAgendamentoModal">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button type="submit" color="primary">
                            {{ $reagendandoItemServicoId ? 'Reagendar' : 'Criar agendamento' }}
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</x-filament-panels::page>
