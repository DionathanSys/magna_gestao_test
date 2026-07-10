<x-filament-panels::page>
    <style>
        .os-mob-card { background: #fff; border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .os-mob-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
        .os-mob-title { font-size: 1.125rem; font-weight: 700; color: #0f172a; }
        .os-mob-subtitle { font-size: 0.8rem; color: #64748b; }
        .os-mob-badge { display: inline-block; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; }
        .os-mob-badge-pendente { background: #fef3c7; color: #92400e; }
        .os-mob-badge-execucao, .os-mob-badge-execução { background: #dbeafe; color: #1e40af; }
        .os-mob-badge-concluido, .os-mob-badge-concluído { background: #d1fae5; color: #065f46; }
        .os-mob-badge-cancelado { background: #fee2e2; color: #991b1b; }
        .os-mob-actions-bar { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
        .os-mob-section { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
        .os-mob-divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0; }
        .os-mob-item { padding: 0.65rem 0; border-bottom: 1px solid #f1f5f9; }
        .os-mob-item:last-child { border-bottom: none; }
        .os-mob-item-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
        .os-mob-item-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.15rem; }
        .os-mob-item-actions { display: flex; gap: 0.35rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .os-mob-empty { font-size: 0.8rem; color: #94a3b8; padding: 0.5rem 0; }
        .os-mob-tab-bar { display: flex; gap: 0; background: #e2e8f0; border-radius: 0.5rem; padding: 0.15rem; margin-bottom: 0.75rem; }
        .os-mob-tab { flex: 1; text-align: center; padding: 0.45rem 0.3rem; font-size: 0.72rem; font-weight: 500; border-radius: 0.375rem; cursor: pointer; color: #64748b; transition: all 0.15s; }
        .os-mob-tab-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.08); }
        .os-mob-cost { font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .os-mob-total { background: #f8fafc; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
    </style>

    {{-- Header --}}
    <div class="os-mob-card">
        <div class="os-mob-header">
            <div>
                <div class="os-mob-title">OS #{{ $record->id }}</div>
                <div class="os-mob-subtitle">
                    {{ $record->veiculo?->placa ?? '—' }}
                    &middot;
                    <span class="os-mob-badge os-mob-badge-{{ strtolower($record->status?->value ?? 'pendente') }}">
                        {{ $record->status?->value ?? 'Pendente' }}
                    </span>
                </div>
            </div>
            <a href="{{ $this->getListUrl() }}" class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 transition hover:bg-gray-50">
                <x-heroicon-o-arrow-left style="width:16px;height:16px"/>
            </a>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.78rem;color:#475569;">
            <div><strong>Tipo:</strong> {{ $record->tipo_manutencao?->value ?? '—' }}</div>
            <div><strong>KM:</strong> {{ number_format($record->quilometragem ?? 0, 0, ',', '.') }}</div>
            <div><strong>Abertura:</strong> {{ $record->data_inicio?->format('d/m/Y H:i') ?? '—' }}</div>
            @if($record->data_fim)
                <div><strong>Fim:</strong> {{ $record->data_fim?->format('d/m/Y H:i') ?? '—' }}</div>
            @endif
        </div>
    </div>

    {{-- Action Bar --}}
    <div class="os-mob-actions-bar">
        <x-filament::button size="sm" color="success" wire:click="salvarForm" icon="heroicon-o-check">
            Salvar
        </x-filament::button>
        <x-filament::button size="sm" color="warning" wire:click="encerrar" icon="heroicon-o-check-circle" x-on:click="return confirm('Deseja encerrar esta OS?')">
            Encerrar
        </x-filament::button>
        <a href="{{ $this->getPdfUrl() }}" target="_blank" class="fi-btn fi-btn-size-sm inline-flex items-center gap-1 rounded-lg bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-200">
            <x-heroicon-o-document-text style="width:16px;height:16px"/>
            PDF
        </a>
    </div>

    {{-- Tab Bar --}}
    <div class="os-mob-tab-bar">
        <div class="os-mob-tab {{ $activeTab === 'servicos' ? 'os-mob-tab-active' : '' }}" wire:click="$set('activeTab','servicos')">Serviços</div>
        <div class="os-mob-tab {{ $activeTab === 'form' ? 'os-mob-tab-active' : '' }}" wire:click="$set('activeTab','form')">Dados</div>
        <div class="os-mob-tab {{ $activeTab === 'agendamentos' ? 'os-mob-tab-active' : '' }}" wire:click="$set('activeTab','agendamentos')">Agend.</div>
        <div class="os-mob-tab {{ $activeTab === 'custos' ? 'os-mob-tab-active' : '' }}" wire:click="$set('activeTab','custos')">Custos</div>
    </div>

    {{-- ═══ TAB: Serviços ═══ --}}
    @if ($activeTab === 'servicos')
        <div class="os-mob-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <div class="os-mob-section" style="margin-bottom:0;">
                    Serviços ({{ $record->itens->count() }})
                </div>
                <x-filament::button size="sm" :color="$showFormServico ? 'danger' : 'primary'" wire:click="toggleFormServico" :icon="$showFormServico ? 'heroicon-o-x-mark' : 'heroicon-o-plus'">
                    {{ $showFormServico ? 'Fechar' : 'Adicionar' }}
                </x-filament::button>
            </div>

            @if ($showFormServico)
                <hr class="os-mob-divider">
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:0.5rem;">
                        {{ $editandoItemServicoId ? 'Editar Serviço' : 'Novo Serviço' }}
                    </div>
                    <form wire:submit="salvarServico">
                        {{ $this->formServico }}
                    </form>
                    <div style="margin-top:0.5rem;">
                        <x-filament::button size="sm" color="success" wire:click="salvarServico" style="width:100%" icon="heroicon-o-check">
                            {{ $editandoItemServicoId ? 'Atualizar' : 'Vincular' }}
                        </x-filament::button>
                    </div>
                </div>
                <hr class="os-mob-divider">
            @endif

            @if ($record->itens->isEmpty())
                <div class="os-mob-empty">Nenhum serviço vinculado.</div>
            @else
                @foreach ($record->itens as $item)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $item->servico?->descricao ?? 'Serviço #' . $item->servico_id }}</div>
                        <div class="os-mob-item-meta">
                            {{ $item->servico?->codigo }}
                            @if($item->posicao) &middot; Pos: {{ $item->posicao }} @endif
                            &middot;
                            <span class="os-mob-badge os-mob-badge-{{ strtolower($item->status?->value ?? 'pendente') }}">
                                {{ $item->status?->value ?? 'Pendente' }}
                            </span>
                        </div>
                        @if($item->observacao)
                            <div class="os-mob-item-meta" style="font-style:italic;">{{ $item->observacao }}</div>
                        @endif
                        <div class="os-mob-item-actions">
                            <x-filament::button size="sm" color="gray" wire:click="editarServico({{ $item->id }})" icon="heroicon-o-pencil">
                                Editar
                            </x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="excluirServico({{ $item->id }})" icon="heroicon-o-trash" x-on:click="return confirm('Remover este serviço?')">
                                Excluir
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- ═══ TAB: Dados ═══ --}}
    @if ($activeTab === 'form')
        <div class="os-mob-card">
            <form wire:submit="salvarForm">
                {{ $this->form }}
            </form>
        </div>
    @endif

    {{-- ═══ TAB: Agendamentos ═══ --}}
    @if ($activeTab === 'agendamentos')
        <div class="os-mob-card">
            <div class="os-mob-section">
                Agendamentos Pendentes
            </div>

            @if ($record->agendamentosPendentes->isEmpty())
                <div class="os-mob-empty">Nenhum agendamento pendente.</div>
            @else
                @foreach ($record->agendamentosPendentes->sortBy('data_agendamento') as $agendamento)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                        <div class="os-mob-item-meta">
                            Data: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}
                        </div>
                        @if ($agendamento->observacao)
                            <div class="os-mob-item-meta" style="font-style:italic;">Obs.: {{ $agendamento->observacao }}</div>
                        @endif
                        <div class="os-mob-item-actions">
                            <x-filament::button size="sm" color="primary" wire:click="vincularAgendamento({{ $agendamento->id }})" icon="heroicon-o-link">
                                Vincular
                            </x-filament::button>
                            <x-filament::button size="sm" color="danger" wire:click="cancelarAgendamento({{ $agendamento->id }})" icon="heroicon-o-x-mark" x-on:click="return confirm('Cancelar este agendamento?')">
                                Cancelar
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="os-mob-card">
            <div class="os-mob-section">
                Planos Preventivos
            </div>

            @if ($record->planoPreventivoVinculado->isEmpty())
                <div class="os-mob-empty">Nenhum plano preventivo vinculado.</div>
            @else
                @foreach ($record->planoPreventivoVinculado as $planoVinculado)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $planoVinculado->planoPreventivo?->descricao ?? 'Plano não informado' }}</div>
                        <div class="os-mob-item-meta">
                            Intervalo: {{ $planoVinculado->planoPreventivo?->intervalo ?? 'N/A' }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- ═══ TAB: Custos ═══ --}}
    @if ($activeTab === 'custos')
        <div class="os-mob-card">
            <div class="os-mob-section">
                Custos Vinculados
            </div>

            @if ($record->manutencaoLancamentos->isEmpty())
                <div class="os-mob-empty">Nenhum custo vinculado.</div>
            @else
                @php $totalVinculados = 0; @endphp
                @foreach ($record->manutencaoLancamentos->sortByDesc('data_negociacao') as $lancamento)
                    @php $totalVinculados += ($lancamento->valor_total_centavos ?? 0) / 100; @endphp
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $lancamento->produto }}</div>
                        <div class="os-mob-item-meta">
                            Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                            &middot; Vínculo: {{ $lancamento->tipo_vinculo === 'automatico' ? 'Automático' : 'Manual' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;">
                            <span class="os-mob-cost">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <x-filament::button size="sm" color="danger" wire:click="desvincularLancamento({{ $lancamento->id }})" icon="heroicon-o-x-mark" x-on:click="return confirm('Remover este vínculo?')">
                                Remover
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach

                <div class="os-mob-total">
                    <span style="font-size:0.85rem;font-weight:600;color:#334155;">Total Vinculado</span>
                    <span style="font-size:1rem;font-weight:700;color:#0f172a;">R$ {{ number_format($totalVinculados, 2, ',', '.') }}</span>
                </div>
            @endif
        </div>

        <div class="os-mob-card">
            <div class="os-mob-section">
                Custos Pendentes do Veículo
            </div>

            @if ($this->lancamentosPendentes->isEmpty())
                <div class="os-mob-empty">Nenhum custo pendente para este veículo.</div>
            @else
                @foreach ($this->lancamentosPendentes as $lancamento)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $lancamento->produto }}</div>
                        <div class="os-mob-item-meta">
                            Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;">
                            <span class="os-mob-cost">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <x-filament::button size="sm" color="primary" wire:click="vincularLancamento({{ $lancamento->id }})" icon="heroicon-o-link">
                                Vincular
                            </x-filament::button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>