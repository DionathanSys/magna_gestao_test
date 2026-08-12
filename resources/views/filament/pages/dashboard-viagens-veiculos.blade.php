<x-filament-panels::page>
    @php
        $veiculos = collect($cards)
            ->groupBy('placa')
            ->map(function ($items, $placa) {
                return [
                    'placa' => $placa,
                    'total' => $items->sum('total_viagens'),
                    'clientes' => $items->sortByDesc('total_viagens')->values(),
                    'principal' => $items->sortByDesc('total_viagens')->first(),
                    'viagem_atual' => $items->first()['viagem_atual'],
                    'movimento_diario' => $items->first()['movimento_diario'],
                ];
            })
            ->sortByDesc('total')
            ->values();
    @endphp

    <style>
        .trip-dashboard,
        .trip-dashboard * {
            font-family: inherit;
        }

        .trip-dashboard {
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .trip-filter-card,
        .trip-list-card {
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
            overflow: hidden;
        }

        .dark .trip-filter-card,
        .dark .trip-list-card {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .trip-filter-body {
            padding: 18px;
        }

        .trip-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }

        .trip-list-header {
            display: grid;
            grid-template-columns: 54px minmax(110px, .65fr) minmax(180px, 1.2fr) minmax(110px, .65fr) minmax(130px, .7fr) minmax(110px, .6fr) minmax(90px, .5fr) minmax(170px, 1fr) 80px;
            gap: 12px;
            padding: 12px 18px;
            background: #f8fafc;
            border-bottom: 1px solid rgba(15, 23, 42, .08);
            color: #64748b;
            font-size: 12px;
            font-weight: 600;
        }

        .dark .trip-list-header {
            background: rgba(255, 255, 255, .04);
            border-bottom-color: rgba(255, 255, 255, .1);
            color: #94a3b8;
        }

        .trip-row {
            display: grid;
            grid-template-columns: 54px minmax(110px, .65fr) minmax(180px, 1.2fr) minmax(110px, .65fr) minmax(130px, .7fr) minmax(110px, .6fr) minmax(90px, .5fr) minmax(170px, 1fr) 80px;
            gap: 12px;
            align-items: start;
            padding: 16px 18px;
            border-bottom: 1px solid rgba(15, 23, 42, .07);
        }

        .trip-row:last-child {
            border-bottom: 0;
        }

        .trip-row:hover {
            background: #f8fafc;
        }

        .dark .trip-row {
            border-bottom-color: rgba(255, 255, 255, .08);
        }

        .dark .trip-row:hover {
            background: rgba(255, 255, 255, .04);
        }

        .trip-rank {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 10px;
            background: #e2e8f0;
            color: #334155;
            font-size: 13px;
            font-weight: 700;
        }

        .dark .trip-rank {
            background: rgba(255, 255, 255, .08);
            color: #e5e7eb;
        }

        .trip-plate {
            color: #020617;
            font-size: 18px;
            font-weight: 700;
        }

        .dark .trip-plate {
            color: #fff;
        }

        .trip-client-stack {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .trip-client-name {
            display: inline-block;
            width: fit-content;
            border-radius: 999px;
            padding: 4px 10px;
            background: #f1f5f9;
            color: #334155;
            font-size: 13px;
            line-height: 1.3;
        }

        .dark .trip-client-name {
            background: rgba(255, 255, 255, .08);
            color: #e5e7eb;
        }

        .trip-total {
            color: #020617;
            font-size: 14px;
            font-weight: 700;
        }

        .dark .trip-total {
            color: #fff;
        }

        .trip-current-value {
            min-width: 0;
            overflow: hidden;
            color: #334155;
            font-size: 13px;
            line-height: 1.35;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .trip-current-value {
            color: #e5e7eb;
        }

        .trip-status {
            display: inline-flex;
            width: fit-content;
            border-radius: 999px;
            padding: 4px 9px;
            background: #ecfdf5;
            color: #047857;
            font-size: 12px;
            line-height: 1.3;
        }

        .dark .trip-status {
            background: rgba(16, 185, 129, .12);
            color: #a7f3d0;
        }

        .trip-movement {
            grid-column: 1 / -1;
            margin-top: 4px;
            padding: 12px;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 14px;
            background: #f8fafc;
        }

        .dark .trip-movement {
            border-color: rgba(255, 255, 255, .08);
            background: rgba(255, 255, 255, .04);
        }

        .trip-movement-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }

        .trip-movement-title {
            color: #334155;
            font-size: 12px;
            font-weight: 600;
        }

        .dark .trip-movement-title {
            color: #e5e7eb;
        }

        .trip-movement-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            color: #64748b;
            font-size: 12px;
        }

        .dark .trip-movement-meta {
            color: #94a3b8;
        }

        .trip-movement-track {
            display: grid;
            grid-template-columns: repeat(24, minmax(18px, 1fr));
            gap: 2px;
        }

        .trip-hour {
            min-width: 0;
        }

        .trip-hour-parts {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1px;
            overflow: hidden;
            height: 18px;
            border-radius: 4px;
            background: #e2e8f0;
        }

        .dark .trip-hour-parts {
            background: rgba(255, 255, 255, .1);
        }

        .trip-minute-status {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 2px;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            line-height: 1;
        }

        .trip-minute-status.status-0 {
            background: #2563eb;
        }

        .trip-minute-status.status-1 {
            background: #f59e0b;
        }

        .trip-minute-status.status-2 {
            background: #94a3b8;
            color: #0f172a;
        }

        .trip-hour-label {
            margin-top: 4px;
            color: #94a3b8;
            font-size: 9px;
            text-align: center;
        }

        .trip-movement-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 10px;
            color: #64748b;
            font-size: 12px;
        }

        .dark .trip-movement-legend {
            color: #94a3b8;
        }

        .trip-legend-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .trip-legend-dot {
            width: 10px;
            height: 10px;
            border-radius: 999px;
        }

        .trip-movement-empty {
            color: #64748b;
            font-size: 12px;
        }

        .dark .trip-movement-empty {
            color: #94a3b8;
        }

        .trip-empty {
            padding: 42px;
            text-align: center;
        }

        .trip-empty-title {
            color: #020617;
            font-size: 16px;
            font-weight: 700;
        }

        .dark .trip-empty-title {
            color: #fff;
        }

        .trip-empty-text {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 1050px) {
            .trip-list-header {
                display: none;
            }

            .trip-row {
                grid-template-columns: 52px 1fr;
                gap: 12px;
            }

            .trip-row > div:nth-child(n + 3) {
                grid-column: 2;
            }
        }

    </style>

    <div class="trip-dashboard">
        <form wire:submit.prevent="carregarDados" class="trip-filter-card">
            <div class="trip-filter-body">
                {{ $this->form }}

                <div class="trip-actions">
                    <x-filament::button type="submit" icon="heroicon-o-funnel">
                        Aplicar filtros
                    </x-filament::button>

                    <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="limparFiltros">
                        Voltar para hoje
                    </x-filament::button>
                </div>
            </div>
        </form>

        <section class="trip-list-card">
            @if ($veiculos->isNotEmpty())
                <div class="trip-list-header">
                    <div>Rank</div>
                    <div>Veículo</div>
                    <div>Destino</div>
                    <div>Status</div>
                    <div>Início</div>
                    <div>Duração</div>
                    <div>Km pago</div>
                    <div>Clientes trabalhados</div>
                    <div>Total</div>
                </div>

                @foreach ($veiculos as $index => $veiculo)
                    <div class="trip-row">
                        <div>
                            <span class="trip-rank">{{ $index + 1 }}</span>
                        </div>

                        <div class="trip-plate">{{ $veiculo['placa'] }}</div>

                        <div class="trip-current-value" title="{{ $veiculo['viagem_atual']['destino'] }}">
                            {{ $veiculo['viagem_atual']['destino'] }}
                        </div>

                        <div>
                            <span class="trip-status">{{ $veiculo['viagem_atual']['status'] }}</span>
                        </div>

                        <div class="trip-current-value">{{ $veiculo['viagem_atual']['inicio_humano'] }}</div>

                        <div class="trip-current-value">{{ $veiculo['viagem_atual']['duracao_viagem'] }}</div>

                        <div class="trip-current-value">{{ $veiculo['viagem_atual']['km_pago_humano'] }}</div>

                        <div class="trip-client-stack">
                            @foreach ($veiculo['clientes'] as $cliente)
                                <div class="trip-client-name">{{ $cliente['cliente'] }}</div>
                            @endforeach
                        </div>

                        <div class="trip-total">{{ number_format($veiculo['total'], 0, ',', '.') }}</div>

                        <div class="trip-movement">
                            <div class="trip-movement-head">
                                <div class="trip-movement-title">Movimento diário</div>
                                <div class="trip-movement-meta">
                                    <span>Dia: {{ $veiculo['movimento_diario']['dia'] }}</span>
                                    @if ($veiculo['movimento_diario']['disponivel'])
                                        <span>Km: {{ $veiculo['movimento_diario']['km'] }}</span>
                                        <span>Tempo movimento: {{ $veiculo['movimento_diario']['tempo_movimento'] }}</span>
                                    @endif
                                </div>
                            </div>

                            @if ($veiculo['movimento_diario']['disponivel'])
                                <div class="trip-movement-track">
                                    @foreach ($veiculo['movimento_diario']['horas'] as $hora)
                                        <div class="trip-hour">
                                            <div class="trip-hour-parts" title="{{ str_pad((string) $hora['hora'], 2, '0', STR_PAD_LEFT) }}h">
                                                @foreach ($hora['minutos'] as $status)
                                                    <div class="trip-minute-status status-{{ $status }}">{{ $status }}</div>
                                                @endforeach
                                            </div>
                                            <div class="trip-hour-label">{{ str_pad((string) $hora['hora'], 2, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="trip-movement-legend">
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #2563eb"></span>0 Movimento</span>
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #f59e0b"></span>1 Parado ligado</span>
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #94a3b8"></span>2 Desligado</span>
                                </div>
                            @else
                                <div class="trip-movement-empty">Movimento diário ainda não recebido para este veículo no dia selecionado.</div>
                            @endif
                        </div>
                    </div>
                @endforeach
            @else
                <div class="trip-empty">
                    <div class="trip-empty-title">Nenhuma viagem encontrada</div>
                    <div class="trip-empty-text">Tente ampliar o período ou remover filtros de veículo e cliente.</div>
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
