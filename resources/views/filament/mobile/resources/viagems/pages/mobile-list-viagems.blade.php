<x-filament-panels::page>
    <style>
        .vg-mobile-hero { border-radius: 1.15rem; background: radial-gradient(circle at top left, #334155 0, #111827 46%, #020617 100%); padding: 1rem; color: #fff; margin-bottom: 0.85rem; box-shadow: 0 18px 45px rgba(15, 23, 42, 0.2); }
        .vg-mobile-hero-kicker { font-size: 0.68rem; text-transform: uppercase; letter-spacing: 0.08em; color: rgba(255, 255, 255, 0.64); }
        .vg-mobile-hero-title { margin-top: 0.25rem; font-size: 1.2rem; font-weight: 800; line-height: 1.15; }
        .vg-mobile-hero-subtitle { margin-top: 0.35rem; font-size: 0.78rem; color: rgba(255, 255, 255, 0.74); }
        .vg-mobile-summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 0.5rem; margin-top: 0.9rem; }
        .vg-mobile-stat { border-radius: 0.85rem; background: rgba(255, 255, 255, 0.1); padding: 0.7rem; }
        .vg-mobile-stat-label { font-size: 0.62rem; color: rgba(255, 255, 255, 0.66); }
        .vg-mobile-stat-value { margin-top: 0.1rem; font-size: 1.05rem; font-weight: 800; }
        .vg-mobile-search { width: 100%; border: 1px solid rgba(148, 163, 184, 0.35); border-radius: 0.95rem; background: #fff; padding: 0.85rem 1rem; font-size: 0.92rem; margin-bottom: 0.75rem; box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06); }
        .vg-mobile-tabs { display: flex; gap: 0.45rem; overflow-x: auto; padding-bottom: 0.35rem; margin-bottom: 0.75rem; scrollbar-width: none; }
        .vg-mobile-tabs::-webkit-scrollbar { display: none; }
        .vg-mobile-tab { flex: 0 0 auto; border: 0; border-radius: 999px; padding: 0.62rem 0.78rem; background: #e2e8f0; color: #475569; font-size: 0.72rem; font-weight: 800; white-space: nowrap; }
        .vg-mobile-tab.is-active { background: #111827; color: #fff; }
        .vg-mobile-list { display: flex; flex-direction: column; gap: 0.75rem; }
        .vg-mobile-card { border-radius: 1rem; background: #fff; padding: 0.95rem; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.09); }
        .vg-mobile-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
        .vg-mobile-title { font-size: 1rem; font-weight: 850; color: #0f172a; line-height: 1.15; }
        .vg-mobile-subtitle { margin-top: 0.18rem; font-size: 0.76rem; color: #64748b; }
        .vg-mobile-route { margin-top: 0.75rem; border-left: 3px solid #111827; padding-left: 0.65rem; font-size: 0.8rem; font-weight: 700; color: #334155; }
        .vg-mobile-meta { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 0.45rem; margin-top: 0.85rem; }
        .vg-mobile-meta-item { border-radius: 0.75rem; background: #f8fafc; padding: 0.58rem 0.65rem; }
        .vg-mobile-meta-label { display: block; font-size: 0.66rem; color: #64748b; }
        .vg-mobile-meta-value { display: block; margin-top: 0.12rem; font-size: 0.8rem; font-weight: 750; color: #0f172a; }
        .vg-mobile-pills { display: flex; gap: 0.35rem; flex-wrap: wrap; margin-top: 0.8rem; }
        .vg-mobile-empty { border-radius: 1rem; background: #fff; padding: 1rem; color: #64748b; text-align: center; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); }
        .vg-mobile-pagination { margin-top: 1rem; }
        @media (max-width: 380px) {
            .vg-mobile-summary { grid-template-columns: 1fr; }
            .vg-mobile-meta { grid-template-columns: 1fr; }
        }
    </style>

    <div class="vg-mobile-hero">
        <div class="vg-mobile-hero-kicker">Painel mobile</div>
        <div class="vg-mobile-hero-title">Viagens na palma da mão</div>
        <div class="vg-mobile-hero-subtitle">Lista rápida com placa, documento, integrado, datas e situação de conferência.</div>

        <div class="vg-mobile-summary">
            <div class="vg-mobile-stat">
                <div class="vg-mobile-stat-label">Hoje</div>
                <div class="vg-mobile-stat-value">{{ $this->getHojeCount() }}</div>
            </div>
            <div class="vg-mobile-stat">
                <div class="vg-mobile-stat-label">Pendências</div>
                <div class="vg-mobile-stat-value">{{ $this->getPendenciasCount() }}</div>
            </div>
            <div class="vg-mobile-stat">
                <div class="vg-mobile-stat-label">Não conf.</div>
                <div class="vg-mobile-stat-value">{{ $this->getNaoConferidasCount() }}</div>
            </div>
        </div>
    </div>

    <input wire:model.live.debounce.350ms="busca" class="vg-mobile-search" placeholder="Buscar viagem, placa, doc. ou integrado">

    <div class="vg-mobile-tabs">
        <button type="button" wire:click="$set('activeTab', 'recentes')" class="vg-mobile-tab {{ $activeTab === 'recentes' ? 'is-active' : '' }}">Recentes {{ $this->getRecentesCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'hoje')" class="vg-mobile-tab {{ $activeTab === 'hoje' ? 'is-active' : '' }}">Hoje {{ $this->getHojeCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'pendencias')" class="vg-mobile-tab {{ $activeTab === 'pendencias' ? 'is-active' : '' }}">Pendências {{ $this->getPendenciasCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'nao-conferidas')" class="vg-mobile-tab {{ $activeTab === 'nao-conferidas' ? 'is-active' : '' }}">Não conferidas {{ $this->getNaoConferidasCount() }}</button>
        <button type="button" wire:click="$set('activeTab', 'sem-documento')" class="vg-mobile-tab {{ $activeTab === 'sem-documento' ? 'is-active' : '' }}">Sem doc. {{ $this->getSemDocumentoCount() }}</button>
    </div>

    <div class="vg-mobile-list">
        @forelse ($this->viagens as $viagem)
            <div class="vg-mobile-card" wire:key="viagem-mobile-{{ $viagem->id }}">
                <div class="vg-mobile-top">
                    <div>
                        <div class="vg-mobile-title">{{ $viagem->numero_viagem ?? 'Viagem #'.$viagem->id }} - {{ $viagem->veiculo?->placa ?? 'Sem placa' }}</div>
                        <div class="vg-mobile-subtitle">{{ $viagem->documento_transporte ? 'Doc. '.$viagem->documento_transporte : 'Sem documento de transporte' }}</div>
                    </div>

                    <x-filament::badge :color="$this->getConferenciaColor($viagem)">
                        {{ $this->getConferenciaLabel($viagem) }}
                    </x-filament::badge>
                </div>

                <div class="vg-mobile-route">{{ $this->getIntegradosResumo($viagem) }}</div>

                <div class="vg-mobile-meta">
                    <div class="vg-mobile-meta-item">
                        <span class="vg-mobile-meta-label">Início</span>
                        <span class="vg-mobile-meta-value">{{ $this->formatDate($viagem->data_inicio) }}</span>
                    </div>
                    <div class="vg-mobile-meta-item">
                        <span class="vg-mobile-meta-label">Fim</span>
                        <span class="vg-mobile-meta-value">{{ $this->formatDate($viagem->data_fim) }}</span>
                    </div>
                    <div class="vg-mobile-meta-item">
                        <span class="vg-mobile-meta-label">Km rodado</span>
                        <span class="vg-mobile-meta-value">{{ $this->formatNumber($viagem->km_rodado, 1) }} km</span>
                    </div>
                    <div class="vg-mobile-meta-item">
                        <span class="vg-mobile-meta-label">Km pago</span>
                        <span class="vg-mobile-meta-value">{{ $this->formatNumber($viagem->km_pago, 1) }} km</span>
                    </div>
                </div>

                <div class="vg-mobile-pills">
                    <x-filament::badge :color="$this->getDispersaoColor($viagem)">
                        Dispersão {{ $this->formatNumber($viagem->km_dispersao, 1) }} km
                    </x-filament::badge>
                    <x-filament::badge color="gray">{{ $viagem->cargas_count }} carga(s)</x-filament::badge>
                    <x-filament::badge color="gray">{{ $viagem->documentos_count }} frete(s)</x-filament::badge>
                    @if ($viagem->numero_interno)
                        <x-filament::badge color="gray">Interno {{ $viagem->numero_interno }}</x-filament::badge>
                    @endif
                </div>
            </div>
        @empty
            <div class="vg-mobile-empty">Nenhuma viagem encontrada para este filtro.</div>
        @endforelse
    </div>

    <div class="vg-mobile-pagination">
        {{ $this->viagens->links() }}
    </div>
</x-filament-panels::page>
