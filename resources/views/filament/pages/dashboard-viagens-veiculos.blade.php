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
        .trip-dashboard {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .trip-hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 34px;
            color: #fff;
            background:
                radial-gradient(circle at 12% 8%, rgba(250, 204, 21, .42), transparent 24%),
                radial-gradient(circle at 92% 82%, rgba(59, 130, 246, .36), transparent 30%),
                linear-gradient(135deg, #080b13 0%, #111827 48%, #020617 100%);
            box-shadow: 0 26px 70px rgba(2, 6, 23, .28);
        }

        .trip-hero::after {
            content: '';
            position: absolute;
            inset: auto 0 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(253, 224, 71, .9), transparent);
        }

        .trip-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            gap: 28px;
            align-items: end;
        }

        .trip-eyebrow {
            display: inline-flex;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            padding: 7px 12px;
            background: rgba(255, 255, 255, .1);
            color: #fef08a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .trip-title {
            max-width: 860px;
            margin: 18px 0 0;
            font-size: clamp(32px, 5vw, 58px);
            font-weight: 950;
            letter-spacing: -.055em;
            line-height: .95;
        }

        .trip-subtitle {
            max-width: 660px;
            margin: 18px 0 0;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
        }

        .trip-leader {
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 24px;
            padding: 22px;
            background: rgba(255, 255, 255, .09);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .1);
            backdrop-filter: blur(14px);
        }

        .trip-leader-label {
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 700;
        }

        .trip-leader-row {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: end;
            margin-top: 12px;
        }

        .trip-leader-plate {
            font-size: 40px;
            font-weight: 950;
            letter-spacing: -.04em;
            line-height: 1;
        }

        .trip-leader-client {
            margin-top: 8px;
            color: #d1d5db;
            font-size: 13px;
        }

        .trip-leader-total {
            color: #fef08a;
            font-size: 44px;
            font-weight: 950;
            line-height: .9;
            text-align: right;
        }

        .trip-leader-total span {
            display: block;
            margin-top: 7px;
            color: #9ca3af;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .trip-filter-card {
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 22px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .06);
        }

        .dark .trip-filter-card {
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

        .trip-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .trip-kpi {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 24px;
            padding: 22px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        .dark .trip-kpi {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .trip-kpi::before {
            content: '';
            position: absolute;
            right: -30px;
            top: -34px;
            width: 120px;
            height: 120px;
            border-radius: 999px;
            background: var(--accent, rgba(250, 204, 21, .18));
        }

        .trip-kpi-label {
            position: relative;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .dark .trip-kpi-label {
            color: #94a3b8;
        }

        .trip-kpi-value {
            position: relative;
            margin-top: 8px;
            color: #020617;
            font-size: 42px;
            font-weight: 950;
            letter-spacing: -.05em;
            line-height: 1;
        }

        .dark .trip-kpi-value {
            color: #fff;
        }

        .trip-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .truck-card {
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .07);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .truck-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .13);
        }

        .dark .truck-card {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .truck-card-head {
            position: relative;
            overflow: hidden;
            padding: 22px;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(250, 204, 21, .3), transparent 34%),
                linear-gradient(135deg, #020617, #111827 70%);
        }

        .truck-card-top {
            position: relative;
            z-index: 1;
            display: flex;
            justify-content: space-between;
            gap: 18px;
        }

        .truck-label {
            color: #fef08a;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .truck-plate {
            margin-top: 7px;
            font-size: 31px;
            font-weight: 950;
            letter-spacing: -.045em;
            line-height: 1;
        }

        .truck-total-pill {
            min-width: 92px;
            border-radius: 18px;
            padding: 12px 14px;
            background: #fff;
            color: #020617;
            text-align: right;
            box-shadow: 0 18px 34px rgba(0, 0, 0, .28);
        }

        .truck-total-pill strong {
            display: block;
            font-size: 30px;
            font-weight: 950;
            line-height: .9;
        }

        .truck-total-pill span {
            display: block;
            margin-top: 5px;
            color: #64748b;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .truck-ranking {
            position: relative;
            z-index: 1;
            margin-top: 22px;
        }

        .truck-ranking-row {
            display: flex;
            justify-content: space-between;
            color: #d1d5db;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .bar {
            overflow: hidden;
            height: 8px;
            margin-top: 9px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .12);
        }

        .bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #fef08a, #facc15, #f59e0b);
        }

        .truck-card-body {
            padding: 22px;
        }

        .truck-body-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: center;
            margin-bottom: 18px;
        }

        .truck-body-title {
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .dark .truck-body-title {
            color: #fff;
        }

        .truck-body-subtitle {
            margin-top: 2px;
            color: #64748b;
            font-size: 12px;
        }

        .client-count {
            border-radius: 999px;
            padding: 7px 10px;
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .dark .client-count {
            background: rgba(255, 255, 255, .1);
            color: #e5e7eb;
        }

        .client-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .client-row-main {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .client-name {
            min-width: 0;
            color: #1e293b;
            font-size: 13px;
            font-weight: 850;
            line-height: 1.25;
        }

        .dark .client-name {
            color: #f8fafc;
        }

        .client-total {
            flex: 0 0 auto;
            border-radius: 999px;
            padding: 5px 9px;
            background: #eff6ff;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 950;
        }

        .client-bar {
            overflow: hidden;
            height: 7px;
            margin-top: 9px;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .dark .client-bar {
            background: rgba(255, 255, 255, .1);
        }

        .client-bar-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #111827, #475569);
        }

        .dark .client-bar-fill {
            background: linear-gradient(90deg, #fef08a, #facc15);
        }

        .trip-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 28px;
            padding: 42px;
            background: #fff;
            text-align: center;
        }

        .dark .trip-empty {
            border-color: rgba(255, 255, 255, .18);
            background: #111827;
        }

        .trip-empty-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 20px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 28px;
        }

        .trip-empty-title {
            margin-top: 16px;
            color: #020617;
            font-size: 18px;
            font-weight: 950;
        }

        .dark .trip-empty-title {
            color: #fff;
        }

        .trip-empty-text {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 1280px) {
            .trip-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .trip-hero-grid,
            .trip-kpis,
            .trip-cards {
                grid-template-columns: 1fr;
            }

            .trip-hero {
                padding: 24px;
            }
        }

        @media (max-width: 560px) {
            .trip-leader-row,
            .truck-card-top {
                align-items: flex-start;
                flex-direction: column;
            }

            .trip-leader-total,
            .truck-total-pill {
                text-align: left;
            }
        }
    </style>

    <div class="trip-dashboard">
        <section class="trip-hero">
            <div class="trip-hero-grid">
                <div>
                    <div class="trip-eyebrow">Operação de viagens</div>
                    <h2 class="trip-title">Performance por caminhão, cliente por cliente.</h2>
                    <p class="trip-subtitle">
                        Volume de viagens registrado direto da tabela viagens, com leitura rápida por veículo, ranking operacional e participação por cliente.
                    </p>
                </div>

                <div class="trip-leader">
                    <div class="trip-leader-label">Veículo líder do período</div>

                    @if ($lider)
                        <div class="trip-leader-row">
                            <div>
                                <div class="trip-leader-plate">{{ $lider['placa'] }}</div>
                                <div class="trip-leader-client">Principal cliente: {{ $lider['principal']['cliente'] }}</div>
                            </div>
                            <div class="trip-leader-total">
                                {{ number_format($lider['total'], 0, ',', '.') }}
                                <span>viagens</span>
                            </div>
                        </div>
                    @else
                        <div class="trip-leader-row">
                            <div class="trip-leader-plate">Sem dados</div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

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

        <section class="trip-kpis">
            <div class="trip-kpi" style="--accent: rgba(250, 204, 21, .22)">
                <div class="trip-kpi-label">Total de viagens</div>
                <div class="trip-kpi-value">{{ number_format($this->getTotalViagens(), 0, ',', '.') }}</div>
            </div>

            <div class="trip-kpi" style="--accent: rgba(59, 130, 246, .18)">
                <div class="trip-kpi-label">Veículos em rota</div>
                <div class="trip-kpi-value">{{ number_format($this->getTotalVeiculos(), 0, ',', '.') }}</div>
            </div>

            <div class="trip-kpi" style="--accent: rgba(16, 185, 129, .18)">
                <div class="trip-kpi-label">Clientes atendidos</div>
                <div class="trip-kpi-value">{{ number_format($this->getTotalClientes(), 0, ',', '.') }}</div>
            </div>
        </section>

        @if ($veiculos->isNotEmpty())
            <section class="trip-cards">
                @foreach ($veiculos as $index => $veiculo)
                    @php
                        $percentualVolume = ($veiculo['total'] / $maiorVolume) * 100;
                    @endphp

                    <article class="truck-card">
                        <div class="truck-card-head">
                            <div class="truck-card-top">
                                <div>
                                    <div class="truck-label">Caminhão</div>
                                    <div class="truck-plate">{{ $veiculo['placa'] }}</div>
                                </div>

                                <div class="truck-total-pill">
                                    <strong>{{ number_format($veiculo['total'], 0, ',', '.') }}</strong>
                                    <span>viagens</span>
                                </div>
                            </div>

                            <div class="truck-ranking">
                                <div class="truck-ranking-row">
                                    <span>Volume relativo</span>
                                    <span>#{{ $index + 1 }} no ranking</span>
                                </div>
                                <div class="bar">
                                    <div class="bar-fill" style="width: {{ $percentualVolume }}%"></div>
                                </div>
                            </div>
                        </div>

                        <div class="truck-card-body">
                            <div class="truck-body-head">
                                <div>
                                    <div class="truck-body-title">Clientes trabalhados</div>
                                    <div class="truck-body-subtitle">Participação dentro do veículo</div>
                                </div>

                                <div class="client-count">
                                    {{ $veiculo['clientes']->count() }} cliente{{ $veiculo['clientes']->count() === 1 ? '' : 's' }}
                                </div>
                            </div>

                            <div class="client-list">
                                @foreach ($veiculo['clientes'] as $cliente)
                                    @php
                                        $percentualCliente = ($cliente['total_viagens'] / max($veiculo['total'], 1)) * 100;
                                    @endphp

                                    <div>
                                        <div class="client-row-main">
                                            <div class="client-name">{{ $cliente['cliente'] }}</div>
                                            <div class="client-total">{{ number_format($cliente['total_viagens'], 0, ',', '.') }}</div>
                                        </div>
                                        <div class="client-bar">
                                            <div class="client-bar-fill" style="width: {{ $percentualCliente }}%"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="trip-empty">
                <div class="trip-empty-icon">⌁</div>
                <div class="trip-empty-title">Nenhuma viagem encontrada</div>
                <div class="trip-empty-text">Tente ampliar o período ou remover filtros de veículo e cliente.</div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
