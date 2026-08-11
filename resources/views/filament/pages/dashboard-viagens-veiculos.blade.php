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
                ];
            })
            ->sortByDesc('total')
            ->values();

        $maiorVolume = max($veiculos->max('total') ?? 0, 1);
        $lider = $veiculos->first();
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
            grid-template-columns: 64px minmax(130px, .8fr) minmax(240px, 1.5fr) 140px 1fr;
            gap: 16px;
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
            grid-template-columns: 64px minmax(130px, .8fr) minmax(240px, 1.5fr) 140px 1fr;
            gap: 16px;
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

        .trip-client-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 72px;
            gap: 12px;
            align-items: center;
        }

        .trip-client-name {
            min-width: 0;
            color: #334155;
            font-size: 13px;
            line-height: 1.3;
        }

        .dark .trip-client-name {
            color: #e5e7eb;
        }

        .trip-client-total {
            border-radius: 999px;
            padding: 4px 9px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .dark .trip-client-total {
            background: rgba(96, 165, 250, .12);
            color: #bfdbfe;
        }

        .trip-total {
            color: #020617;
            font-size: 18px;
            font-weight: 700;
        }

        .dark .trip-total {
            color: #fff;
        }

        .trip-progress {
            min-width: 120px;
            padding-top: 7px;
        }

        .trip-progress-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 7px;
            color: #64748b;
            font-size: 12px;
        }

        .dark .trip-progress-meta {
            color: #94a3b8;
        }

        .trip-progress-bar {
            overflow: hidden;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .dark .trip-progress-bar {
            background: rgba(255, 255, 255, .1);
        }

        .trip-progress-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0f172a, #475569);
        }

        .dark .trip-progress-fill {
            background: linear-gradient(90deg, #facc15, #f59e0b);
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

            .trip-row > div:nth-child(3),
            .trip-row > div:nth-child(4),
            .trip-row > div:nth-child(5) {
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
                    <div>Clientes trabalhados</div>
                    <div>Total</div>
                    <div>Volume relativo</div>
                </div>

                @foreach ($veiculos as $index => $veiculo)
                    @php
                        $percentualVolume = ($veiculo['total'] / $maiorVolume) * 100;
                    @endphp

                    <div class="trip-row">
                        <div>
                            <span class="trip-rank">{{ $index + 1 }}</span>
                        </div>

                        <div class="trip-plate">{{ $veiculo['placa'] }}</div>

                        <div class="trip-client-stack">
                            @foreach ($veiculo['clientes'] as $cliente)
                                <div class="trip-client-line">
                                    <div class="trip-client-name">{{ $cliente['cliente'] }}</div>
                                    <div class="trip-client-total">{{ number_format($cliente['total_viagens'], 0, ',', '.') }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="trip-total">{{ number_format($veiculo['total'], 0, ',', '.') }}</div>

                        <div class="trip-progress">
                            <div class="trip-progress-meta">
                                <span>{{ number_format($percentualVolume, 0, ',', '.') }}%</span>
                                <span>do maior volume</span>
                            </div>
                            <div class="trip-progress-bar">
                                <div class="trip-progress-fill" style="width: {{ $percentualVolume }}%"></div>
                            </div>
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
