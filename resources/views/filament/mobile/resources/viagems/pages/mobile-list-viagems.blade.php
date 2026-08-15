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
        .vg-mobile-empty { border-radius: 1rem; background: #fff; padding: 1rem; color: #64748b; text-align: center; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); }
        .vg-mobile-pagination { margin-top: 1rem; }
        @media (max-width: 380px) {
            .vg-mobile-summary { grid-template-columns: 1fr; }
        }
    </style>

    <div
        x-data="{
            abrirDetalhes(id) {
                $wire.selecionarViagem(id)
            },
        }"
    >
        <div class="vg-mobile-hero">
            <div class="vg-mobile-hero-kicker">Painel mobile</div>
            <div class="vg-mobile-hero-title">Viagens na palma da mão</div>
            <div class="vg-mobile-hero-subtitle">Toque em uma viagem para abrir os detalhes em um painel inferior.</div>

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
                <x-mobile.record-card
                    wire:key="viagem-mobile-{{ $viagem->id }}"
                    :click="'abrirDetalhes('.$viagem->id.')'"
                >
                    <x-slot:title>
                        {{ $viagem->numero_viagem ?? 'Viagem #'.$viagem->id }} · {{ $viagem->veiculo?->placa ?? 'Sem placa' }}
                    </x-slot:title>

                    <x-slot:subtitle>
                        {{ $viagem->documento_transporte ? 'Doc. '.$viagem->documento_transporte : 'Sem documento de transporte' }}
                    </x-slot:subtitle>

                    <x-slot:badge>
                        <x-filament::badge :color="$this->getConferenciaColor($viagem)">
                            {{ $this->getConferenciaLabel($viagem) }}
                        </x-filament::badge>
                    </x-slot:badge>

                    <x-slot:meta>
                        <div class="mb-record-card-meta-item">
                            <span class="mb-record-card-meta-label">Início</span>
                            <span class="mb-record-card-meta-value">{{ $this->formatDate($viagem->data_inicio) }}</span>
                        </div>
                        <div class="mb-record-card-meta-item">
                            <span class="mb-record-card-meta-label">Fim</span>
                            <span class="mb-record-card-meta-value">{{ $this->formatDate($viagem->data_fim) }}</span>
                        </div>
                        <div class="mb-record-card-meta-item">
                            <span class="mb-record-card-meta-label">Km rodado</span>
                            <span class="mb-record-card-meta-value">{{ $this->formatNumber($viagem->km_rodado, 1) }} km</span>
                        </div>
                        <div class="mb-record-card-meta-item">
                            <span class="mb-record-card-meta-label">Km pago</span>
                            <span class="mb-record-card-meta-value">{{ $this->formatNumber($viagem->km_pago, 1) }} km</span>
                        </div>
                    </x-slot:meta>

                    <x-slot:footer>
                        <div class="mb-record-card-route">{{ $this->getIntegradosResumo($viagem) }}</div>
                        <div class="mb-record-card-footer">
                            <x-filament::badge :color="$this->getDispersaoColor($viagem)">
                                Dispersão {{ $this->formatNumber($viagem->km_dispersao, 1) }} km
                            </x-filament::badge>
                            <x-filament::badge color="gray">{{ $viagem->cargas_count }} carga(s)</x-filament::badge>
                            <x-filament::badge color="gray">{{ $viagem->documentos_count }} frete(s)</x-filament::badge>
                            @if ($viagem->numero_interno)
                                <x-filament::badge color="gray">Interno {{ $viagem->numero_interno }}</x-filament::badge>
                            @endif
                        </div>
                    </x-slot:footer>
                </x-mobile.record-card>
            @empty
                <div class="vg-mobile-empty">Nenhuma viagem encontrada para este filtro.</div>
            @endforelse
        </div>

        <div class="vg-mobile-pagination">
            {{ $this->viagens->links() }}
        </div>

        <x-mobile.bottom-sheet
            name="trip-details"
            :height="60"
            open-state="$wire.entangle('sheetOpen').live"
        >
            <x-slot:header>
                <div class="mb-sheet-title">{{ $this->selectedViagem?->numero_viagem ?? 'Detalhes da viagem' }}</div>
                <button
                    type="button"
                    class="mb-sheet-close"
                    x-ref="closeButton"
                    x-on:click="hide()"
                    aria-label="Fechar"
                >
                    <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                </button>
            </x-slot:header>

            @if ($this->selectedViagem)
                @php
                    $viagem = $this->selectedViagem;
                @endphp

                <div class="mb-detail-hero">
                    <div class="mb-detail-value">{{ $this->formatValor($viagem) }}</div>
                    <div class="mb-detail-caption">Valor do frete</div>
                </div>

                <div class="mb-detail-grid">
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Veículo</span>
                        <span class="mb-detail-value2">{{ $viagem->veiculo?->placa ?? 'Sem veículo' }}</span>
                    </div>
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Motorista</span>
                        <span class="mb-detail-value2">{{ $viagem->motorista1 ?: ($viagem->motorista2 ?: '—') }}</span>
                    </div>
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Km rodado</span>
                        <span class="mb-detail-value2">{{ $this->formatNumber($viagem->km_rodado, 1) }} km</span>
                    </div>
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Km pago</span>
                        <span class="mb-detail-value2">{{ $this->formatNumber($viagem->km_pago, 1) }} km</span>
                    </div>
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Início</span>
                        <span class="mb-detail-value2">{{ $this->formatDate($viagem->data_inicio) }}</span>
                    </div>
                    <div class="mb-detail-item">
                        <span class="mb-detail-label">Conferência</span>
                        <span class="mb-detail-value2">{{ $this->getConferenciaLabel($viagem) }}</span>
                    </div>
                </div>

                <div class="mb-detail-section">
                    <span class="mb-detail-label">Origem</span>
                    <div class="mb-detail-route">Chapecó - SC</div>
                </div>

                <div class="mb-detail-section">
                    <span class="mb-detail-label">Destino(s)</span>
                    <div class="mb-detail-route">{{ $this->getIntegradosResumo($viagem) ?: 'Sem destino vinculado' }}</div>
                </div>

                <div class="mb-detail-section">
                    <span class="mb-detail-label">Documento de transporte</span>
                    <div class="mb-detail-route">{{ $viagem->documento_transporte ?? 'Sem documento de transporte' }}</div>
                </div>

                @if ($viagem->pendencias)
                    <div class="mb-detail-section">
                        <span class="mb-detail-label">Pendências</span>
                        <div class="mb-detail-route">{{ implode('; ', array_filter((array) $viagem->pendencias)) }}</div>
                    </div>
                @endif

                <x-slot:footer>
                    <x-filament::button
                        tag="a"
                        :href="$this->getDetalheCompletoUrl($viagem)"
                        size="lg"
                        class="w-full"
                    >
                        Abrir registro completo
                    </x-filament::button>
                </x-slot:footer>
            @else
                <x-mobile.skeleton :lines="2" />

                <div class="mb-detail-grid" style="margin-top: 1rem;">
                    <x-mobile.skeleton :lines="1" />
                </div>

                <x-mobile.skeleton :lines="3" />

                <div class="mb-detail-hero" style="margin-top: 1rem;">
                    <x-mobile.skeleton :lines="1" />
                </div>
            @endif
        </x-mobile.bottom-sheet>
    </div>
</x-filament-panels::page>
