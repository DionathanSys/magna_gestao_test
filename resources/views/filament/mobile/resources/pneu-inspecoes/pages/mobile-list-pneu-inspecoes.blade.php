<x-filament-panels::page>
    <style>
        .pi-mobile-hero { border-radius: 1.15rem; background: linear-gradient(135deg, #0f172a 0%, #1e293b 48%, #334155 100%); padding: 1rem; color: #fff; margin-bottom: 0.85rem; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.2); }
        .pi-mobile-hero-kicker { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.64); }
        .pi-mobile-hero-title { margin-top: 0.25rem; font-size: 1.2rem; font-weight: 800; line-height: 1.15; }
        .pi-mobile-hero-subtitle { margin-top: 0.35rem; font-size: 0.78rem; color: rgba(255, 255, 255, 0.74); }
        .pi-mobile-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; margin-top: 0.9rem; }
        .pi-mobile-stat { border-radius: 0.85rem; background: rgba(255, 255, 255, 0.1); padding: 0.7rem; }
        .pi-mobile-stat-label { font-size: 0.62rem; color: rgba(255, 255, 255, 0.66); }
        .pi-mobile-stat-value { margin-top: 0.1rem; font-size: 1.05rem; font-weight: 800; }
        .pi-mobile-search { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 0.95rem; background: #fff; padding: 0.85rem 1rem; font-size: 0.92rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); }
        .pi-mobile-tabs { display: flex; gap: 0.45rem; overflow-x: auto; padding-bottom: 0.35rem; margin-bottom: 0.75rem; scrollbar-width: none; }
        .pi-mobile-tabs::-webkit-scrollbar { display: none; }
        .pi-mobile-tab { flex: 0 0 auto; border: 0; border-radius: 999px; padding: 0.62rem 0.78rem; background: #e2e8f0; color: #475569; font-size: 0.72rem; font-weight: 800; white-space: nowrap; }
        .pi-mobile-tab.is-active { background: #111827; color: #fff; }
        .pi-mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .pi-mobile-card { border-radius: 1rem; background: #fff; padding: 0.95rem; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.09); }
        .pi-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
        .pi-mobile-title { font-size: 1rem; font-weight: 850; color: #0f172a; line-height: 1.15; }
        .pi-mobile-subtitle { margin-top: 0.18rem; font-size: 0.76rem; color: #64748b; }
        .pi-mobile-groove { margin-top: 0.85rem; border-radius: 0.9rem; background: #f8fafc; padding: 0.75rem; }
        .pi-mobile-groove-title { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; font-size: 0.72rem; color: #64748b; }
        .pi-mobile-groove-value { font-size: 1.15rem; font-weight: 850; color: #0f172a; }
        .pi-mobile-groove-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.4rem; margin-top: 0.55rem; }
        .pi-mobile-groove-item { border-radius: 0.65rem; background: #fff; padding: 0.5rem; }
        .pi-mobile-groove-label { display: block; font-size: 0.62rem; color: #64748b; }
        .pi-mobile-groove-number { display: block; margin-top: 0.08rem; font-size: 0.78rem; font-weight: 800; color: #0f172a; }
        .pi-mobile-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.85rem; }
        .pi-mobile-meta-item { border-radius: 0.75rem; background: #f8fafc; padding: 0.58rem 0.65rem; }
        .pi-mobile-meta-label { display: block; font-size: 0.66rem; color: #64748b; }
        .pi-mobile-meta-value { display: block; margin-top: 0.12rem; font-size: 0.8rem; font-weight: 750; color: #0f172a; }
        .pi-mobile-pills { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.8rem; }
        .pi-mobile-note { margin-top: 0.75rem; font-size: 0.78rem; color: #475569; }
        .pi-mobile-empty { border-radius: 1rem; background: #fff; padding: 1rem; color: #64748b; text-align: center; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); }
        .pi-mobile-pagination { margin-top: 1rem; }
        @media (max-width: 380px) {
            .pi-mobile-summary { grid-template-columns: 1fr; }
            .pi-mobile-meta { grid-template-columns: 1fr; }
        }
    </style>

    <div class="pi-mobile-hero">
        <div class="pi-mobile-hero-kicker">Pneus mobile</div>
        <div class="pi-mobile-hero-title">Inspeções rápidas de pneus</div>
        <div class="pi-mobile-hero-subtitle">Acompanhe sulcos, resultado, veículo e recapagem sem rolagem lateral.</div>

        <div class="pi-mobile-summary">
            <div class="pi-mobile-stat">
                <div class="pi-mobile-stat-label">Hoje</div>
                <div class="pi-mobile-stat-value">{{ $this->getHojeCount() }}</div>
            </div>
            <div class="pi-mobile-stat">
                <div class="pi-mobile-stat-label">Monitorar</div>
                <div class="pi-mobile-stat-value">{{ $this->getMonitorarCount() }}</div>
            </div>
            <div class="pi-mobile-stat">
                <div class="pi-mobile-stat-label">Críticas</div>
                <div class="pi-mobile-stat-value">{{ $this->getCriticasCount() }}</div>
            </div>
        </div>
    </div>

    <input wire:model.live.debounce.350ms="busca" class="pi-mobile-search" placeholder="Buscar pneu, placa, parceiro ou observação">

    <div class="pi-mobile-tabs">
        <button type="button" wire:click="$set('activeTab', 'recentes')" class="pi-mobile-tab {{ $activeTab === 'recentes' ? 'is-active' : '' }}">Recentes {{ $this->getRecentesCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'hoje')" class="pi-mobile-tab {{ $activeTab === 'hoje' ? 'is-active' : '' }}">Hoje {{ $this->getHojeCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'monitorar')" class="pi-mobile-tab {{ $activeTab === 'monitorar' ? 'is-active' : '' }}">Monitorar {{ $this->getMonitorarCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'criticas')" class="pi-mobile-tab {{ $activeTab === 'criticas' ? 'is-active' : '' }}">Críticas {{ $this->getCriticasCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'recapagem')" class="pi-mobile-tab {{ $activeTab === 'recapagem' ? 'is-active' : '' }}">Recapagem {{ $this->getRecapagemCount() }}</button>
    </div>

    <div class="pi-mobile-list">
        @forelse ($this->inspecoes as $inspecao)
            <div class="pi-mobile-card" wire:key="pneu-inspecao-mobile-{{ $inspecao->id }}">
                <div class="pi-mobile-top">
                    <div>
                        <div class="pi-mobile-title">Pneu {{ $inspecao->pneu?->numero_fogo ?? 'N/A' }}</div>
                        <div class="pi-mobile-subtitle">
                            {{ $inspecao->veiculo?->placa ?? 'Sem veículo' }}
                            @if ($inspecao->posicaoVeiculo)
                                - {{ $inspecao->posicaoVeiculo->eixo }} eixo / {{ $inspecao->posicaoVeiculo->posicao }}
                            @endif
                        </div>
                    </div>

                    <x-filament::badge :color="$this->getResultadoColor($inspecao)">
                        {{ $inspecao->resultado?->value ?? 'Sem resultado' }}
                    </x-filament::badge>
                </div>

                <div class="pi-mobile-groove">
                    <div class="pi-mobile-groove-title">
                        <span>Menor sulco</span>
                        <x-filament::badge :color="$this->getSulcoColor($inspecao)">
                            {{ $this->formatNumber($this->getMenorSulco($inspecao), 1) }} mm
                        </x-filament::badge>
                    </div>
                    <div class="pi-mobile-groove-grid">
                        <div class="pi-mobile-groove-item">
                            <span class="pi-mobile-groove-label">Interno</span>
                            <span class="pi-mobile-groove-number">{{ $this->formatNumber($inspecao->sulco_interno, 1) }}</span>
                        </div>
                        <div class="pi-mobile-groove-item">
                            <span class="pi-mobile-groove-label">Centro</span>
                            <span class="pi-mobile-groove-number">{{ $this->formatNumber($inspecao->sulco_centro, 1) }}</span>
                        </div>
                        <div class="pi-mobile-groove-item">
                            <span class="pi-mobile-groove-label">Externo</span>
                            <span class="pi-mobile-groove-number">{{ $this->formatNumber($inspecao->sulco_externo, 1) }}</span>
                        </div>
                    </div>
                </div>

                <div class="pi-mobile-meta">
                    <div class="pi-mobile-meta-item">
                        <span class="pi-mobile-meta-label">Data</span>
                        <span class="pi-mobile-meta-value">{{ $this->formatDate($inspecao->data_inspecao) }}</span>
                    </div>
                    <div class="pi-mobile-meta-item">
                        <span class="pi-mobile-meta-label">KM referência</span>
                        <span class="pi-mobile-meta-value">{{ $this->formatNumber($inspecao->km_referencia) }}</span>
                    </div>
                    <div class="pi-mobile-meta-item">
                        <span class="pi-mobile-meta-label">Ciclo</span>
                        <span class="pi-mobile-meta-value">{{ $inspecao->ciclo?->numero ? 'Ciclo '.$inspecao->ciclo->numero : 'N/A' }}</span>
                    </div>
                    <div class="pi-mobile-meta-item">
                        <span class="pi-mobile-meta-label">Parceiro</span>
                        <span class="pi-mobile-meta-value">{{ $inspecao->parceiro?->nome ?? 'Interno' }}</span>
                    </div>
                </div>

                <div class="pi-mobile-pills">
                    <x-filament::badge :color="$this->getTipoColor($inspecao)">
                        {{ $inspecao->tipo?->value ?? 'Sem tipo' }}
                    </x-filament::badge>
                    @if ($inspecao->apto_recapagem)
                        <x-filament::badge color="info">Apto recapagem</x-filament::badge>
                    @endif
                </div>

                @if ($inspecao->observacao)
                    <div class="pi-mobile-note">{{ $inspecao->observacao }}</div>
                @endif
            </div>
        @empty
            <div class="pi-mobile-empty">Nenhuma inspeção encontrada para este filtro.</div>
        @endforelse
    </div>

    <div class="pi-mobile-pagination">
        {{ $this->inspecoes->links() }}
    </div>
</x-filament-panels::page>
