<x-filament-panels::page>
    @php
        $veiculos = collect($cards)
            ->groupBy('placa')
            ->map(function ($items, $placa) {
                return [
                    'placa' => $placa,
                    'total' => $items->sum('total_viagens'),
                    'total_encerradas' => $items->sum('total_viagens_encerradas'),
                    'clientes' => $items->sortByDesc('total_viagens')->values(),
                    'principal' => $items->sortByDesc('total_viagens')->first(),
                    'viagem_atual' => $items->first()['viagem_atual'],
                    'movimento_diario' => $items->first()['movimento_diario'],
                ];
            })
            ->sortByDesc('total')
            ->values();
        $graficoViagensEncerradas = $veiculos->sortBy('placa')->values();
        $maiorTotalEncerradas = max(1, $graficoViagensEncerradas->max('total_encerradas'));
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
        }

        .trip-list-card {
            overflow: hidden;
        }

        .trip-chart-card {
            padding: 20px;
        }

        .trip-totalizer {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 18px 20px;
        }

        .trip-totalizer-label {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
        }

        .trip-totalizer-value {
            color: #2563eb;
            font-size: 30px;
            font-weight: 700;
            line-height: 1;
        }

        .dark .trip-filter-card,
        .dark .trip-list-card,
        .dark .trip-chart-card {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .trip-filter-body {
            padding: 18px;
        }

        .trip-chart-title {
            color: #020617;
            font-size: 16px;
            font-weight: 700;
        }

        .trip-chart-subtitle {
            margin-top: 4px;
            color: #64748b;
            font-size: 13px;
        }

        .trip-chart-bars {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(72px, 1fr));
            align-items: end;
            gap: 14px;
            margin-top: 18px;
        }

        .trip-chart-row {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .trip-chart-label,
        .trip-chart-value {
            color: #334155;
            font-size: 13px;
            font-weight: 600;
        }

        .trip-chart-track {
            position: relative;
            width: 100%;
            height: 180px;
            border-bottom: 1px solid #cbd5e1;
        }

        .trip-chart-dot {
            position: absolute;
            z-index: 2;
            left: 50%;
            width: 14px;
            height: 14px;
            border: 3px solid #dbeafe;
            border-radius: 999px;
            background: #2563eb;
            transform: translateX(-50%);
        }

        .trip-chart-line {
            position: absolute;
            z-index: 1;
            top: 0;
            left: 50%;
            width: calc(100% + 14px);
            height: 100%;
            overflow: visible;
        }

        .trip-chart-line path {
            fill: none;
            stroke: #2563eb;
            stroke-width: 2;
        }

        .dark .trip-chart-title,
        .dark .trip-chart-label,
        .dark .trip-chart-value {
            color: #fff;
        }

        .dark .trip-totalizer-label {
            color: #94a3b8;
        }

        .dark .trip-totalizer-value {
            color: #60a5fa;
        }

        .dark .trip-chart-subtitle {
            color: #94a3b8;
        }

        .dark .trip-chart-track {
            border-bottom-color: rgba(255, 255, 255, .2);
        }

        .dark .trip-chart-dot {
            border-color: #1e3a8a;
        }

        .dark .trip-chart-line path {
            stroke: #60a5fa;
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

        .trip-current-details {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .trip-current-detail {
            color: #64748b;
            font-size: 12px;
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
            min-width: 2px;
        }

        .trip-minute-status.status-0 {
            background: #94a3b8;
            color: #0f172a;
        }

        .trip-minute-status.status-1 {
            background: #2563eb;
        }

        .trip-minute-status.status-2 {
            background: #f59e0b;
            color: #fff;
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

        @media (max-width: 640px) {
            .trip-chart-bars {
                grid-template-columns: repeat(auto-fit, minmax(58px, 1fr));
                gap: 10px;
            }

            .trip-chart-track {
                height: 140px;
            }

            .trip-chart-label,
            .trip-chart-value {
                font-size: 11px;
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

                    <x-filament::button type="button" color="success" icon="heroicon-o-document-arrow-down" wire:click="gerarPdf">
                        Relatório PDF
                    </x-filament::button>
                </div>
            </div>
        </form>

        <section class="trip-filter-card trip-totalizer">
            <div class="trip-totalizer-label">Total de viagens encerradas</div>
            <div class="trip-totalizer-value">{{ number_format($this->getTotalViagensEncerradas(), 0, ',', '.') }}</div>
        </section>

        @if ($graficoViagensEncerradas->isNotEmpty())
            <section class="trip-filter-card trip-chart-card">
                <div class="trip-chart-title">Viagens encerradas por veículo</div>
                <div class="trip-chart-subtitle">Quantidade de viagens encerradas no período selecionado.</div>

                <div class="trip-chart-bars">
                    @foreach ($graficoViagensEncerradas as $veiculo)
                        @php
                            $percentualEncerradas = ($veiculo['total_encerradas'] / $maiorTotalEncerradas) * 100;
                            $proximoVeiculo = $graficoViagensEncerradas->get($loop->index + 1);
                        @endphp
                        <div class="trip-chart-row">
                            <div class="trip-chart-value">{{ number_format($veiculo['total_encerradas'], 0, ',', '.') }}</div>
                            <div class="trip-chart-track">
                                @if ($proximoVeiculo)
                                    @php
                                        $proximoPercentualEncerradas = ($proximoVeiculo['total_encerradas'] / $maiorTotalEncerradas) * 100;
                                    @endphp
                                    <svg class="trip-chart-line" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                                        <path d="M 0 {{ 100 - $percentualEncerradas }} L 100 {{ 100 - $proximoPercentualEncerradas }}" />
                                    </svg>
                                @endif
                                <div class="trip-chart-dot" style="bottom: calc({{ $percentualEncerradas }}% - 7px)"></div>
                            </div>
                            <div class="trip-chart-label">{{ $veiculo['placa'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

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

                        <div class="trip-current-details">
                            <div class="trip-current-value" title="{{ $veiculo['viagem_atual']['destino'] }}">
                                {{ $veiculo['viagem_atual']['destino'] }}
                            </div>
                            <div class="trip-current-detail" title="{{ $veiculo['viagem_atual']['local_atual'] }}">
                                Local: {{ $veiculo['viagem_atual']['local_atual'] }}
                            </div>
                            <div class="trip-current-detail">Peso: {{ $veiculo['viagem_atual']['peso_humano'] }}</div>
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
                                                    <div class="trip-minute-status status-{{ $status }}"></div>
                                                @endforeach
                                            </div>
                                            <div class="trip-hour-label">{{ str_pad((string) $hora['hora'], 2, '0', STR_PAD_LEFT) }}</div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="trip-movement-legend">
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #2563eb"></span>1 Movimento</span>
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #94a3b8"></span>0 Desligado</span>
                                    <span class="trip-legend-item"><span class="trip-legend-dot" style="background: #f59e0b"></span>2 Parado ligado</span>
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
