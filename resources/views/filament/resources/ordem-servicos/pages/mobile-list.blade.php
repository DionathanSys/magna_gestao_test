<x-filament-panels::page>
    <style>
        .os-mobile-summary { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 0.75rem; margin-bottom: 1rem; }
        .os-mobile-hero { border-radius: 1rem; background: linear-gradient(135deg, #111827 0%, #1f2937 100%); padding: 1rem; color: #fff; }
        .os-mobile-hero-title { font-size: 1rem; font-weight: 700; }
        .os-mobile-hero-subtitle { margin-top: 0.25rem; font-size: 0.78rem; color: rgba(255,255,255,0.78); }
        .os-mobile-hero-count { margin-top: 0.85rem; font-size: 1.5rem; font-weight: 800; line-height: 1; }
        .os-mobile-cta { display: flex; align-items: stretch; }
        .os-mobile-cta > a, .os-mobile-cta > button { width: 100%; }
        .os-mobile-mini-grid { display: grid; grid-template-columns: 1fr; gap: 0.55rem; }
        .os-mobile-mini-card { border-radius: 0.9rem; background: #fff; padding: 0.8rem 0.85rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); }
        .os-mobile-mini-label { font-size: 0.68rem; color: #64748b; }
        .os-mobile-mini-value { margin-top: 0.1rem; font-size: 1rem; font-weight: 800; color: #0f172a; }
        .os-mobile-tabs { display: flex; gap: 0.35rem; margin-bottom: 1rem; }
        .os-mobile-tab { flex: 1; border: 0; border-radius: 0.75rem; padding: 0.7rem 0.5rem; background: #e2e8f0; color: #475569; font-size: 0.75rem; font-weight: 600; }
        .os-mobile-tab.is-active { background: #111827; color: #fff; }
        .os-mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .os-mobile-card { display: block; border-radius: 0.9rem; background: #fff; padding: 0.95rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08); text-decoration: none; color: inherit; }
        .os-mobile-top { display: flex; align-items: start; justify-content: space-between; gap: 0.75rem; }
        .os-mobile-title { font-size: 0.95rem; font-weight: 700; color: #0f172a; }
        .os-mobile-subtitle { margin-top: 0.15rem; font-size: 0.78rem; color: #64748b; }
        .os-mobile-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.85rem; }
        .os-mobile-meta-item { border-radius: 0.7rem; background: #f8fafc; padding: 0.55rem 0.65rem; }
        .os-mobile-meta-label { display: block; font-size: 0.68rem; color: #64748b; }
        .os-mobile-meta-value { display: block; margin-top: 0.1rem; font-size: 0.78rem; font-weight: 600; color: #0f172a; }
        .os-mobile-services { margin-top: 0.8rem; font-size: 0.74rem; color: #475569; }
        .os-mobile-empty { border-radius: 0.9rem; background: #fff; padding: 1rem; color: #64748b; text-align: center; }
    </style>

    <div class="os-mobile-summary">
        <div class="os-mobile-hero">
            <div class="os-mobile-hero-title">Ordens Abertas</div>
            <div class="os-mobile-hero-subtitle">Acompanhe e abra OS pelo celular sem rolagem lateral.</div>
            <div class="os-mobile-hero-count">{{ $this->getTodasCount() }}</div>
        </div>

        <div class="os-mobile-mini-grid">
            <div class="os-mobile-cta">
                <x-filament::button tag="a" :href="$this->getCreateUrl()" icon="heroicon-o-plus">
                    Nova OS
                </x-filament::button>
            </div>
            <div class="os-mobile-mini-card">
                <div class="os-mobile-mini-label">Pendentes</div>
                <div class="os-mobile-mini-value">{{ $this->getPendenteCount() }}</div>
            </div>
        </div>
    </div>

    <div class="os-mobile-tabs">
        <button type="button" wire:click="$set('activeTab', 'hoje')" class="os-mobile-tab {{ $activeTab === 'hoje' ? 'is-active' : '' }}">
            Hoje<br>{{ $this->getHojeCount() }}
        </button>
        <button type="button" wire:click="$set('activeTab', 'pendente')" class="os-mobile-tab {{ $activeTab === 'pendente' ? 'is-active' : '' }}">
            Pendentes<br>{{ $this->getPendenteCount() }}
        </button>
        <button type="button" wire:click="$set('activeTab', 'todas')" class="os-mobile-tab {{ $activeTab === 'todas' ? 'is-active' : '' }}">
            Todas<br>{{ $this->getTodasCount() }}
        </button>
    </div>

    <div class="os-mobile-list">
        @forelse ($this->ordensServico as $ordemServico)
            <a href="{{ $this->getDetailUrl($ordemServico) }}" class="os-mobile-card">
                <div class="os-mobile-top">
                    <div>
                        <div class="os-mobile-title">OS #{{ $ordemServico->id }} - {{ $ordemServico->veiculo?->placa ?? 'Sem placa' }}</div>
                        <div class="os-mobile-subtitle">{{ $ordemServico->tipo_manutencao?->value ?? 'Tipo não informado' }}</div>
                    </div>

                    <x-filament::badge :color="$this->getStatusBadgeColor($ordemServico)">
                        {{ $ordemServico->status?->value ?? 'Sem status' }}
                    </x-filament::badge>
                </div>

                <div class="os-mobile-meta">
                    <div class="os-mobile-meta-item">
                        <span class="os-mobile-meta-label">Abertura</span>
                        <span class="os-mobile-meta-value">{{ $ordemServico->data_inicio?->format('d/m H:i') ?? 'Sem data' }}</span>
                    </div>
                    <div class="os-mobile-meta-item">
                        <span class="os-mobile-meta-label">Quilometragem</span>
                        <span class="os-mobile-meta-value">{{ number_format($ordemServico->quilometragem ?? 0, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="os-mobile-services">{{ $ordemServico->itens->count() }} servico(s)</div>
            </a>
        @empty
            <div class="os-mobile-empty">Nenhuma ordem encontrada neste filtro.</div>
        @endforelse
    </div>
</x-filament-panels::page>
