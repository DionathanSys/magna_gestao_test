<x-filament-panels::page>
    <style>
        .os-mob-page { padding-bottom: 5.5rem; }
        .os-mob-card { background: #fff; border-radius: 0.85rem; padding: 0.85rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .os-mob-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 0.6rem; gap: 0.75rem; }
        .os-mob-title { font-size: 1rem; font-weight: 700; color: #0f172a; line-height: 1.2; display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center; }
        .os-mob-subtitle { font-size: 0.76rem; color: #64748b; margin-top: 0.15rem; }
        .os-mob-badge { display: inline-block; font-size: 0.7rem; font-weight: 600; padding: 0.15rem 0.5rem; border-radius: 9999px; }
        .os-mob-badge-pendente { background: #fef3c7; color: #92400e; }
        .os-mob-badge-execucao, .os-mob-badge-execução { background: #dbeafe; color: #1e40af; }
        .os-mob-badge-adiado { background: #ffedd5; color: #c2410c; }
        .os-mob-badge-concluido, .os-mob-badge-concluído { background: #d1fae5; color: #065f46; }
        .os-mob-badge-cancelado { background: #fee2e2; color: #991b1b; }
        .os-mob-actions-bar { display: none; }
        .os-mob-section { font-size: 0.85rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
        .os-mob-divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0; }
        .os-mob-item { padding: 0.55rem 0; border-bottom: 1px solid #f1f5f9; }
        .os-mob-item.is-adiado { background: #fff7ed; border-radius: 0.75rem; padding: 0.7rem; border-bottom: 0; margin-bottom: 0.35rem; }
        .os-mob-item:last-child { border-bottom: none; }
        .os-mob-item-name { font-size: 0.8rem; font-weight: 600; color: #1e293b; }
        .os-mob-item-meta { font-size: 0.73rem; color: #64748b; margin-top: 0.12rem; line-height: 1.35; }
        .os-mob-item-actions { display: flex; gap: 0.35rem; margin-top: 0.4rem; flex-wrap: wrap; }
        .os-mob-icon-actions { display: flex; gap: 0.35rem; margin-top: 0.4rem; }
        .os-mob-icon-btn { width: 2rem; height: 2rem; display: inline-flex; align-items: center; justify-content: center; border-radius: 0.65rem; border: 0; }
        .os-mob-empty { font-size: 0.8rem; color: #94a3b8; padding: 0.5rem 0; }
        .os-mob-tab-bar { display: flex; gap: 0; background: #e2e8f0; border-radius: 0.65rem; padding: 0.15rem; margin-bottom: 0.75rem; position: sticky; top: 0.75rem; z-index: 5; }
        .os-mob-tab { flex: 1; text-align: center; padding: 0.5rem 0.2rem; font-size: 0.72rem; font-weight: 600; border-radius: 0.5rem; cursor: pointer; color: #64748b; transition: all 0.15s; }
        .os-mob-tab-active { background: #fff; color: #0f172a; box-shadow: 0 1px 2px rgba(0,0,0,0.08); }
        .os-mob-cost { font-size: 0.9rem; font-weight: 600; color: #0f172a; }
        .os-mob-total { background: #f8fafc; border-radius: 0.5rem; padding: 0.75rem 1rem; margin-top: 0.5rem; display: flex; justify-content: space-between; align-items: center; }
        .os-mob-kpis { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.65rem; }
        .os-mob-kpi { border-radius: 0.7rem; background: #f8fafc; padding: 0.6rem 0.7rem; }
        .os-mob-kpi-label { display: block; font-size: 0.66rem; color: #64748b; }
        .os-mob-kpi-value { display: block; margin-top: 0.1rem; font-size: 0.77rem; font-weight: 700; color: #0f172a; }
        .os-mob-filter-stack { display: grid; gap: 0.55rem; margin: 0.75rem 0; }
        .os-mob-input, .os-mob-select { width: 100%; border: 1px solid #cbd5e1; border-radius: 0.7rem; background: #fff; padding: 0.7rem 0.8rem; font-size: 0.78rem; color: #0f172a; }
        .os-mob-bottom-bar { position: fixed; left: 0; right: 0; bottom: 0; z-index: 20; padding: 0.65rem 0.75rem calc(0.65rem + env(safe-area-inset-bottom)); background: rgba(255,255,255,0.96); border-top: 1px solid #e2e8f0; backdrop-filter: blur(10px); }
        .os-mob-bottom-actions { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 0.5rem; }
    </style>

    <div class="os-mob-page">
    {{-- Header --}}
    <div class="os-mob-card">
        <div class="os-mob-header">
            <div>
                <div class="os-mob-title">
                    <span>OS #{{ $record->id }}</span>
                    <span style="font-size:0.78rem;font-weight:600;color:#64748b;">{{ $record->tipo_manutencao?->value ?? '—' }}</span>
                </div>
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

        <div class="os-mob-kpis">
            <div class="os-mob-kpi">
                <span class="os-mob-kpi-label">KM</span>
                <span class="os-mob-kpi-value">{{ number_format($record->quilometragem ?? 0, 0, ',', '.') }}</span>
            </div>
            <div class="os-mob-kpi">
                <span class="os-mob-kpi-label">Abertura</span>
                <span class="os-mob-kpi-value">{{ $this->formatDateTime($record->data_inicio) }}</span>
            </div>
            @if($record->data_fim)
                <div class="os-mob-kpi">
                    <span class="os-mob-kpi-label">Fim</span>
                    <span class="os-mob-kpi-value">{{ $this->formatDateTime($record->data_fim) }}</span>
                </div>
            @endif
        </div>
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
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;gap:0.5rem;flex-wrap:wrap;">
                <div class="os-mob-section" style="margin-bottom:0;">
                    Serviços ({{ $record->itens->count() }})
                </div>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <x-filament::button type="button" size="sm" color="gray" wire:click="toggleFormNovoServico" :icon="$showFormNovoServico ? 'heroicon-o-x-mark' : 'heroicon-o-sparkles'">
                        {{ $showFormNovoServico ? 'Fechar Cadastro' : 'Novo Serviço' }}
                    </x-filament::button>
                    <x-filament::button type="button" size="sm" :color="$showFormServico ? 'danger' : 'primary'" wire:click="toggleFormServico" :icon="$showFormServico ? 'heroicon-o-x-mark' : 'heroicon-o-plus'">
                        {{ $showFormServico ? 'Fechar' : 'Adicionar' }}
                    </x-filament::button>
                </div>
            </div>

            @if ($showFormNovoServico)
                <hr class="os-mob-divider">
                <div style="margin-bottom:0.75rem;">
                    <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:0.5rem;">
                        Cadastrar novo serviço
                    </div>
                    <form wire:submit="salvarNovoServico">
                        {{ $this->formNovoServico }}
                    </form>
                    <div style="margin-top:0.5rem;">
                        <x-filament::button type="button" size="sm" color="success" wire:click="salvarNovoServico" style="width:100%" icon="heroicon-o-check">
                            Salvar serviço
                        </x-filament::button>
                    </div>
                </div>
                <hr class="os-mob-divider">
            @endif

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
                        <x-filament::button type="button" size="sm" color="success" wire:click="salvarServico" style="width:100%" icon="heroicon-o-check">
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
                    <div class="os-mob-item {{ ($item->status?->value ?? null) === 'ADIADO' ? 'is-adiado' : '' }}">
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
                        <div class="os-mob-icon-actions">
                            <x-filament::icon-button type="button" color="warning" size="sm" wire:click="reagendarServico({{ $item->id }})" icon="heroicon-o-calendar-days" label="Reagendar serviço" />
                            <x-filament::icon-button type="button" color="gray" size="sm" wire:click="editarServico({{ $item->id }})" icon="heroicon-o-pencil" label="Editar serviço" />
                            <x-filament::icon-button type="button" color="danger" size="sm" wire:click="excluirServico({{ $item->id }})" icon="heroicon-o-trash" label="Excluir serviço" x-on:click="return confirm('Remover este serviço?')" />
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
                Agendamentos Desta OS
            </div>

            <div style="margin-bottom:0.75rem;">
                <x-filament::button type="button" size="sm" color="primary" wire:click="abrirNovoAgendamento" icon="heroicon-o-plus">
                    Novo agendamento
                </x-filament::button>
            </div>

            @if ($showFormAgendamento)
                <div style="margin-bottom:0.9rem;">
                    <div style="font-size:0.8rem;font-weight:600;color:#475569;margin-bottom:0.5rem;">
                        {{ $editingAgendamentoId ? 'Editar agendamento' : ($reagendandoItemServicoId ? 'Reagendar serviço' : 'Criar agendamento') }}
                    </div>
                    <form wire:submit="salvarAgendamento">
                        {{ $this->formAgendamento }}
                    </form>
                    <div style="display:flex;gap:0.5rem;margin-top:0.6rem;">
                        <x-filament::button type="button" size="sm" color="gray" wire:click="fecharFormAgendamento" style="flex:1;">
                            Cancelar
                        </x-filament::button>
                        <x-filament::button type="button" size="sm" color="success" wire:click="salvarAgendamento" style="flex:1;" icon="heroicon-o-check">
                            {{ $editingAgendamentoId ? 'Salvar edição' : ($reagendandoItemServicoId ? 'Reagendar' : 'Salvar') }}
                        </x-filament::button>
                    </div>
                </div>
            @endif

            @if ($this->agendamentosDestaOs->isEmpty())
                <div class="os-mob-empty">Nenhum agendamento vinculado nesta OS.</div>
            @else
                @foreach ($this->agendamentosDestaOs as $agendamento)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                        <div class="os-mob-item-meta">
                            {{ $agendamento->categoria?->value ?? 'MANUAL' }}
                            &middot; {{ $agendamento->status?->value ?? 'N/A' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Agendado: {{ $this->formatDate($agendamento->data_agendamento, 'd/m/Y') }}
                        </div>
                        @if ($agendamento->observacao)
                            <div class="os-mob-item-meta" style="font-style:italic;">Obs.: {{ $agendamento->observacao }}</div>
                        @endif
                        <div class="os-mob-icon-actions">
                            <x-filament::icon-button type="button" color="gray" size="sm" wire:click="editarAgendamento({{ $agendamento->id }})" icon="heroicon-o-pencil-square" label="Editar agendamento" />
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="os-mob-card">
            <div class="os-mob-section">
                Outras Pendências do Veículo
            </div>

            <div class="os-mob-filter-stack">
                <input type="text" wire:model.live.debounce.300ms="agendamentoBusca" class="os-mob-input" placeholder="Buscar serviço, fornecedor ou observação">
            </div>

            @if ($this->agendamentosVeiculo->isEmpty())
                <div class="os-mob-empty">Nenhum agendamento pendente.</div>
            @else
                @foreach ($this->agendamentosVeiculo as $agendamento)
                    <div class="os-mob-item">
                        <div class="os-mob-item-name">{{ $agendamento->servico?->descricao ?? 'Serviço não informado' }}</div>
                        <div class="os-mob-item-meta">
                            Categoria: {{ $agendamento->categoria?->value ?? 'MANUAL' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Data: {{ $this->formatDate($agendamento->data_agendamento, 'd/m/Y') }}
                            &middot; Limite: {{ $this->formatDate($agendamento->data_limite, 'd/m/Y') }}
                        </div>
                        <div class="os-mob-item-meta">
                            Fornecedor: {{ $agendamento->parceiro?->nome ?? 'Serviço interno' }}
                        </div>
                        @if ($agendamento->observacao)
                            <div class="os-mob-item-meta" style="font-style:italic;">Obs.: {{ $agendamento->observacao }}</div>
                        @endif
                        <div class="os-mob-icon-actions">
                            <x-filament::icon-button type="button" color="primary" size="sm" wire:click="vincularAgendamento({{ $agendamento->id }})" icon="heroicon-o-link" label="Vincular agendamento" />
                            <x-filament::icon-button type="button" color="gray" size="sm" wire:click="editarAgendamento({{ $agendamento->id }})" icon="heroicon-o-pencil-square" label="Editar agendamento" />
                            <x-filament::icon-button type="button" color="danger" size="sm" wire:click="cancelarAgendamento({{ $agendamento->id }})" icon="heroicon-o-x-mark" label="Cancelar agendamento" x-on:click="return confirm('Cancelar este agendamento?')" />
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
                            Data: {{ $this->formatDate($lancamento->data_negociacao, 'd/m/Y') }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                            &middot; Vínculo: {{ $lancamento->tipo_vinculo === 'automatico' ? 'Automático' : 'Manual' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;gap:0.5rem;">
                            <span class="os-mob-cost">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <x-filament::icon-button type="button" color="danger" size="sm" wire:click="desvincularLancamento({{ $lancamento->id }})" icon="heroicon-o-x-mark" label="Remover vínculo" x-on:click="return confirm('Remover este vínculo?')" />
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
                            Data: {{ $this->formatDate($lancamento->data_negociacao, 'd/m/Y') }}
                            &middot; Origem: {{ $lancamento->origem ?? '-' }}
                        </div>
                        <div class="os-mob-item-meta">
                            Parceiro: {{ $lancamento->parceiro ?? 'N/A' }}
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:0.35rem;gap:0.5rem;">
                            <span class="os-mob-cost">R$ {{ number_format(($lancamento->valor_total_centavos ?? 0) / 100, 2, ',', '.') }}</span>
                            <x-filament::icon-button type="button" color="primary" size="sm" wire:click="vincularLancamento({{ $lancamento->id }})" icon="heroicon-o-link" label="Vincular custo" />
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
    @endif

    <x-filament-actions::modals />
    <div class="os-mob-bottom-bar">
        <div class="os-mob-bottom-actions">
            <x-filament::button type="button" color="success" wire:click="salvarForm" icon="heroicon-o-check" size="sm">
                Salvar
            </x-filament::button>
            <a href="{{ $this->getPdfUrl() }}" target="_blank" class="fi-btn fi-btn-size-sm inline-flex items-center justify-center gap-1 rounded-xl bg-gray-100 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-200">
                <x-heroicon-o-document-text style="width:16px;height:16px"/>
                PDF
            </a>
            {{ $this->encerrarAction }}
            <x-filament::button type="button" color="danger" wire:click="excluirOrdemServico" icon="heroicon-o-trash" size="sm" x-on:click="return confirm('Deseja excluir esta OS?')">
                Excluir
            </x-filament::button>
        </div>
    </div>
    </div>
</x-filament-panels::page>
