<div class="os-mobile-container">
    <style>
        .os-mobile-container { max-width: 100%; padding: 0.75rem; background: #f1f5f9; min-height: 100vh; }
        .os-mobile-card { background: #fff; border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .os-mobile-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; }
        .os-mobile-title { font-size: 1.125rem; font-weight: 700; color: #0f172a; }
        .os-mobile-subtitle { font-size: 0.8rem; color: #64748b; }
        .os-mobile-badge { display: inline-block; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; }
        .os-badge-pendente { background: #fef3c7; color: #92400e; }
        .os-badge-execucao { background: #dbeafe; color: #1e40af; }
        .os-badge-concluido { background: #d1fae5; color: #065f46; }
        .os-badge-cancelado { background: #fee2e2; color: #991b1b; }
        .os-mobile-actions-bar { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
        .os-mob-btn { display: inline-flex; align-items: center; gap: 0.35rem; padding: 0.5rem 0.75rem; border-radius: 0.5rem; font-size: 0.75rem; font-weight: 500; border: none; cursor: pointer; transition: all 0.15s; text-decoration: none; }
        .os-mob-btn-primary { background: #3b82f6; color: #fff; }
        .os-mob-btn-primary:hover { background: #2563eb; }
        .os-mob-btn-success { background: #10b981; color: #fff; }
        .os-mob-btn-success:hover { background: #059669; }
        .os-mob-btn-warning { background: #f59e0b; color: #fff; }
        .os-mob-btn-warning:hover { background: #d97706; }
        .os-mob-btn-danger { background: #ef4444; color: #fff; }
        .os-mob-btn-danger:hover { background: #dc2626; }
        .os-mob-btn-gray { background: #e2e8f0; color: #475569; }
        .os-mob-btn-gray:hover { background: #cbd5e1; }
        .os-mob-btn-outline { background: transparent; color: #475569; border: 1px solid #d1d5db; }
        .os-mob-btn-outline:hover { background: #f8fafc; }
        .os-mob-btn-sm { padding: 0.35rem 0.5rem; font-size: 0.7rem; }
        .os-mobile-section-title { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.35rem; }
        .os-mobile-divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0; }
        .os-servico-item { padding: 0.65rem 0; border-bottom: 1px solid #f1f5f9; }
        .os-servico-item:last-child { border-bottom: none; }
        .os-servico-item-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.5rem; }
        .os-servico-item-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
        .os-servico-item-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.15rem; }
        .os-servico-item-actions { display: flex; gap: 0.35rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .os-agend-item { padding: 0.65rem 0; border-bottom: 1px solid #f1f5f9; }
        .os-agend-item:last-child { border-bottom: none; }
        .os-agend-item-name { font-size: 0.85rem; font-weight: 600; color: #1e293b; }
        .os-agend-item-meta { font-size: 0.75rem; color: #64748b; margin-top: 0.15rem; }
        .os-agend-item-actions { display: flex; gap: 0.35rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .os-empty { font-size: 0.8rem; color: #94a3b8; padding: 0.5rem 0; }
        .os-form-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 50; display: flex; align-items: flex-end; }
        .os-form-sheet { background: #fff; width: 100%; max-height: 85vh; overflow-y: auto; border-radius: 1rem 1rem 0 0; padding: 1rem; }
        .os-form-sheet-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid #e2e8f0; }
        .os-form-sheet-title { font-size: 1rem; font-weight: 600; }
        .os-tab-bar { display: flex; gap: 0; background: #e2e8f0; border-radius: 0.5rem; padding: 0.15rem; margin-bottom: 0.75rem; }
        .os-tab { flex: 1; text-align: center; padding: 0.45rem 0.5rem; font-size: 0.75rem; font-weight: 500; border-radius: 0.375rem; cursor: pointer; color: #64748b; transition: all 0.15s; }
        .os-tab-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.08); }
        .os-cost-value { font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .os-cost-row { display: flex; justify-content: space-between; align-items: center; padding: 0.4rem 0; }
        .os-cost-label { font-size: 0.8rem; color: #64748b; }
        .os-total-bar { background: #f8fafc; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
        .os-total-label { font-size: 0.85rem; font-weight: 600; color: #334155; }
        .os-total-value { font-size: 1rem; font-weight: 700; color: #0f172a; }
    </style>

    @if (! $isEditing)
        {{-- ═══ CRIAÇÃO ═══ --}}
        <div class="os-mobile-card">
            <div class="os-mobile-header">
                <div class="os-mobile-title">Nova Ordem de Serviço</div>
            </div>

            <form wire:submit="salvarForm">
                {{ $this->form }}
            </form>

            <div style="margin-top:1rem;">
                <button wire:click="salvarForm" class="os-mob-btn os-mob-btn-success" style="width:100%; justify-content:center;">
                    <x-heroicon-o-check style="width:16px;height:16px"/>
                    Criar Ordem de Serviço
                </button>
            </div>
        </div>

    @else
    {{-- ═══ EDITAR / OPERACIONAL ═══ --}}

    {{-- Header --}}
    <div class="os-mobile-card">
        <div class="os-mobile-header">
            <div>
                <div class="os-mobile-title">OS #{{ $ordemServico->id }}</div>
                <div class="os-mobile-subtitle">
                    {{ $ordemServico->veiculo?->placa ?? '—' }}
                    &middot;
                    <span class="os-mobile-badge os-badge-{{ strtolower($ordemServico->status?->value ?? 'pendente') }}">
                        {{ $ordemServico->status?->value ?? 'Pendente' }}
                    </span>
                </div>
            </div>
            <div style="display:flex;gap:0.35rem;">
                <a href="{{ $this->getDesktopUrl() }}" class="os-mob-btn os-mob-btn-outline os-mob-btn-sm" title="Abrir no desktop">
                    <x-heroicon-o-computer-desktop style="width:14px;height:14px"/>
                </a>
            </div>
        </div>

        {{-- Quick Info --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.5rem;font-size:0.78rem;color:#475569;">
            <div><strong>Tipo:</strong> {{ $ordemServico->tipo_manutencao?->value ?? '—' }}</div>
            <div><strong>KM:</strong> {{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }}</div>
            <div><strong>Abertura:</strong> {{ $ordemServico->data_inicio?->format('d/m/Y H:i') ?? '—' }}</div>
            @if($ordemServico->data_fim)
                <div><strong>Fim:</strong> {{ $ordemServico->data_fim?->format('d/m/Y H:i') ?? '—' }}</div>
            @endif
        </div>
    </div>

    {{-- Action Bar --}}
    <div class="os-mobile-actions-bar">
        <button wire:click="salvarForm" class="os-mob-btn os-mob-btn-success os-mob-btn-sm">
            <x-heroicon-o-check style="width:14px;height:14px"/> Salvar
        </button>
        <button wire:click="encerrar" class="os-mob-btn os-mob-btn-warning os-mob-btn-sm"
            onclick="return confirm('Deseja encerrar esta OS?')">
            <x-heroicon-o-check-circle style="width:14px;height:14px"/> Encerrar
        </button>
        <a href="{{ $this->getPdfUrl() }}" target="_blank" class="os-mob-btn os-mob-btn-gray os-mob-btn-sm">
            <x-heroicon-o-document-text style="width:14px;height:14px"/> PDF
        </a>
    </div>

    {{-- Tab Bar --}}
    <div class="os-tab-bar">
        <div class="os-tab {{ $activeTab === 'servicos' ? 'os-tab-active' : '' }}" wire:click="$set('activeTab','servicos')">Serviços</div>
        <div class="os-tab {{ $activeTab === 'form' ? 'os-tab-active' : '' }}" wire:click="$set('activeTab','form')">Dados</div>
        <div class="os-tab {{ $activeTab === 'agendamentos' ? 'os-tab-active' : '' }}" wire:click="$set('activeTab','agendamentos')">Agendamentos</div>
        <div class="os-tab {{ $activeTab === 'custos' ? 'os-tab-active' : '' }}" wire:click="$set('activeTab','custos')">Custos</div>
    </div>

    {{-- ═══ TAB: Serviços ═══ --}}
    @if ($activeTab === 'servicos')
        <div class="os-mobile-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">
                <div class="os-mobile-section-title" style="margin-bottom:0;">
                    <x-heroicon-o-wrench style="width:16px;height:16px"/>
                    Serviços ({{ $ordemServico->itens->count() }})
                </div>
                <button wire:click="toggleFormServico" class="os-mob-btn {{ $showFormServico ? 'os-mob-btn-danger' : 'os-mob-btn-primary' }} os-mob-btn-sm">
                    @if($showFormServico)
                        <x-heroicon-o-x-mark style="width:14px;height:14px"/> Fechar
                    @else
                        <x-heroicon-o-plus style="width:14px;height:14px"/> Adicionar
                    @endif
                </button>
            </div>

            {{-- Form Adicionar/Editar Serviço --}}
            @if ($showFormServico)
                <hr class="os-mobile-divider">
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:0.5rem;">
                        {{ $editandoItemServicoId ? 'Editar Serviço' : 'Novo Serviço' }}
                    </div>
                    <form wire:submit="salvarServico">
                        {{ $this->formServico }}
                    </form>
                    <div style="margin-top:0.5rem;">
                        <button wire:click="salvarServico" class="os-mob-btn os-mob-btn-success" style="width:100%;justify-content:center;">
                            <x-heroicon-o-check style="width:14px;height:14px"/>
                            {{ $editandoItemServicoId ? 'Atualizar' : 'Vincular' }}
                        </button>
                    </div>
                </div>
                <hr class="os-mobile-divider">
            @endif

            {{-- Lista de Serviços --}}
            @if ($ordemServico->itens->isEmpty())
                <div class="os-empty">Nenhum serviço vinculado.</div>
            @else
                @foreach ($ordemServico->itens as $item)
                    <div class="os-servico-item">
                        <div class="os-servico-item-header">
                            <div>
                                <div class="os-servico-item-name">{{ $item->servico?->descricao ?? 'Serviço #' . $item->servico_id }}</div>
                                <div class="os-servico-item-meta">
                                    {{ $item->servico?->codigo }}
                                    @if($item->posicao) &middot; Pos: {{ $item->posicao }} @endif
                                    &middot;
                                    <span class="os-mobile-badge os-badge-{{ strtolower($item->status?->value ?? 'pendente') }}">
                                        {{ $item->status?->value ?? 'Pendente' }}
                                    </span>
                                </div>
                                @if($item->observacao)
                                    <div class="os-servico-item-meta" style="font-style:italic;">{{ $item->observacao }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="os-servico-item-actions">
                            <button wire:click="editarServico({{ $item->id }})" class="os-mob-btn os-mob-btn-gray os-mob-btn-sm">
                                <x-heroicon-o-pencil style="width:12px;height:12px"/> Editar
                            </button>
                            <button wire:click="excluirServico({{ $item->id }})" class="os-mob-btn os-mob-btn-danger os-mob-btn-sm"
                                onclick="return confirm('Remover este serviço?')">
                                <x-heroicon-o-trash style="width:12px;height:12px"/> Excluir
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- ═══ TAB: Dados ═══ --}}
    @if ($activeTab === 'form')
        <div class="os-mobile-card">
            <form wire:submit="salvarForm">
                {{ $this->form }}
            </form>
        </div>
    @endif

    {{-- ═══ TAB: Agendamentos ═══ --}}
    @if ($activeTab === 'agendamentos')
        {{-- Agendamentos Pendentes --}}
        <div class="os-mobile-card">
            <div class="os-mobile-section-title">
                <x-heroicon-o-calendar style="width:16px;height:16px"/>
                Agendamentos Pendentes
            </div>

            @if ($ordemServico->agendamentosPendentes->isEmpty())
                <div class="os-empty">Nenhum agendamento pendente.</div>
            @else
                @foreach ($ordemServico->agendamentosPendentes->sortBy('data_agendamento') as $agendamento)
                    <div class="os-agend-item">
                        <div class="os-agend-item-name">{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                        <div class="os-agend-item-meta">
                            Data: {{ $agendamento->data_agendamento?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Limite: {{ $agendamento->data_limite?->format('d/m/Y') ?? '—' }}
                        </div>
                        <div class="os-agend-item-meta">
                            Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}
                        </div>
                        @if ($agendamento->observacao)
                            <div class="os-agend-item-meta" style="font-style:italic;">Obs.: {{ $agendamento->observacao }}</div>
                        @endif
                        <div class="os-agend-item-actions">
                            <button wire:click="vincularAgendamento({{ $agendamento->id }})" class="os-mob-btn os-mob-btn-primary os-mob-btn-sm">
                                <x-heroicon-o-link style="width:12px;height:12px"/> Vincular
                            </button>
                            <button wire:click="cancelarAgendamento({{ $agendamento->id }})" class="os-mob-btn os-mob-btn-danger os-mob-btn-sm"
                                onclick="return confirm('Cancelar este agendamento?')">
                                <x-heroicon-o-x-circle style="width:12px;height:12px"/> Cancelar
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Planos Preventivos Vinculados --}}
        <div class="os-mobile-card">
            <div class="os-mobile-section-title">
                <x-heroicon-o-clipboard-document-list style="width:16px;height:16px"/>
                Planos Preventivos
            </div>

            @if ($ordemServico->planoPreventivoVinculado->isEmpty())
                <div class="os-empty">Nenhum plano preventivo vinculado.</div>
            @else
                @foreach ($ordemServico->planoPreventivoVinculado as $planoVinculado)
                    <div class="os-servico-item">
                        <div class="os-servico-item-name">{{ $planoVinculado->planoPreventivo?->descricao ?? 'Plano não informado' }}</div>
                        <div class="os-servico-item-meta">
                            Intervalo: {{ $planoVinculado->planoPreventivo?->intervalo ?? 'N/A' }}
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    {{-- ═══ TAB: Custos ═══ --}}
    @if ($activeTab === 'custos')
        {{-- Custos Vinculados --}}
        <div class="os-mobile-card">
            <div class="os-mobile-section-title">
                <x-heroicon-o-banknotes style="width:16px;height:16px"/>
                Custos Vinculados
            </div>

            @if ($ordemServico->manutencaoLancamentos->isEmpty())
                <div class="os-empty">Nenhum custo vinculado.</div>
            @else
                @php $totalVinculados = 0; @endphp
                @foreach ($ordemServico->manutencaoLancamentos->sortByDesc('data_negociacao') as $lancamento)
                    @php $totalVinculados += ($lancamento->valor_total_centavos ?? 0) / 100; @endphp
                    <div class="os-servico-item">
                        <div class="os-servico-item-name">{{ $lancamento->produto }}</div>
                        <div class="os-servico-item-meta">
                            Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-servico-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                            &middot; Vínculo: {{ $lancamento->tipo_vinculo === 'automatico' ? 'Automático' : 'Manual' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;">
                            <span class="os-cost-value">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <button wire:click="desvincularLancamento({{ $lancamento->id }})" class="os-mob-btn os-mob-btn-danger os-mob-btn-sm"
                                onclick="return confirm('Remover este vínculo?')">
                                <x-heroicon-o-x-mark style="width:12px;height:12px"/> Remover
                            </button>
                        </div>
                    </div>
                @endforeach

                <div class="os-total-bar">
                    <div class="os-total-label">Total Vinculado</div>
                    <div class="os-total-value">R$ {{ number_format($totalVinculados, 2, ',', '.') }}</div>
                </div>
            @endif
        </div>

        {{-- Custos Pendentes --}}
        <div class="os-mobile-card">
            <div class="os-mobile-section-title">
                <x-heroicon-o-clock style="width:16px;height:16px"/>
                Custos Pendentes do Veículo
            </div>

            @if ($this->lancamentosPendentes->isEmpty())
                <div class="os-empty">Nenhum custo pendente para este veículo.</div>
            @else
                @foreach ($this->lancamentosPendentes as $lancamento)
                    <div class="os-servico-item">
                        <div class="os-servico-item-name">{{ $lancamento->produto }}</div>
                        <div class="os-servico-item-meta">
                            Data: {{ $lancamento->data_negociacao?->format('d/m/Y') ?? 'Sem data' }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-servico-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;">
                            <span class="os-cost-value">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <button wire:click="vincularLancamento({{ $lancamento->id }})" class="os-mob-btn os-mob-btn-primary os-mob-btn-sm">
                                <x-heroicon-o-link style="width:12px;height:12px"/> Vincular
                            </button>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
    @endif
</div>