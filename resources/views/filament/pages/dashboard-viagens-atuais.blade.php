<x-filament-panels::page>
    @php
        $maisRecente = $this->viagemMaisRecente;
    @endphp

    <style>
        .live-trip-dashboard {
            display: flex;
            flex-direction: column;
            gap: 22px;
        }

        .live-trip-hero {
            position: relative;
            overflow: hidden;
            border-radius: 30px;
            padding: 34px;
            color: #fff;
            background:
                radial-gradient(circle at 10% 12%, rgba(34, 197, 94, .38), transparent 25%),
                radial-gradient(circle at 92% 78%, rgba(14, 165, 233, .34), transparent 30%),
                linear-gradient(135deg, #06111f 0%, #0f172a 52%, #020617 100%);
            box-shadow: 0 26px 70px rgba(2, 6, 23, .28);
        }

        .live-trip-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, .8fr);
            gap: 28px;
            align-items: end;
        }

        .live-trip-eyebrow {
            display: inline-flex;
            border: 1px solid rgba(255, 255, 255, .18);
            border-radius: 999px;
            padding: 7px 12px;
            background: rgba(255, 255, 255, .1);
            color: #bbf7d0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .live-trip-title {
            max-width: 840px;
            margin: 18px 0 0;
            font-size: clamp(32px, 5vw, 58px);
            font-weight: 950;
            letter-spacing: -.055em;
            line-height: .95;
        }

        .live-trip-subtitle {
            max-width: 680px;
            margin: 18px 0 0;
            color: #cbd5e1;
            font-size: 15px;
            line-height: 1.6;
        }

        .live-trip-panel {
            border: 1px solid rgba(255, 255, 255, .16);
            border-radius: 24px;
            padding: 22px;
            background: rgba(255, 255, 255, .09);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, .1);
            backdrop-filter: blur(14px);
        }

        .live-trip-panel-label {
            color: #cbd5e1;
            font-size: 13px;
            font-weight: 800;
        }

        .live-trip-panel-main {
            margin-top: 12px;
            font-size: 36px;
            font-weight: 950;
            letter-spacing: -.04em;
            line-height: 1;
        }

        .live-trip-panel-sub {
            margin-top: 9px;
            color: #d1d5db;
            font-size: 13px;
        }

        .live-trip-actions {
            display: flex;
            justify-content: flex-end;
        }

        .live-payload-example {
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 24px;
            background: #0f172a;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .08);
        }

        .live-payload-example-head {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, .09);
            padding: 16px 18px;
        }

        .live-payload-example-title {
            color: #fff;
            font-size: 14px;
            font-weight: 950;
        }

        .live-payload-example-subtitle {
            margin-top: 2px;
            color: #94a3b8;
            font-size: 12px;
        }

        .live-payload-endpoint {
            border-radius: 999px;
            padding: 7px 10px;
            background: rgba(34, 197, 94, .14);
            color: #bbf7d0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .live-payload-code {
            overflow-x: auto;
            margin: 0;
            padding: 18px;
            color: #dbeafe;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
            line-height: 1.65;
        }

        .live-trip-kpis {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .live-trip-kpi {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 24px;
            padding: 22px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .05);
        }

        .dark .live-trip-kpi {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .live-trip-kpi::before {
            content: '';
            position: absolute;
            right: -30px;
            top: -34px;
            width: 120px;
            height: 120px;
            border-radius: 999px;
            background: var(--accent, rgba(34, 197, 94, .18));
        }

        .live-trip-kpi-label {
            position: relative;
            color: #64748b;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .dark .live-trip-kpi-label {
            color: #94a3b8;
        }

        .live-trip-kpi-value {
            position: relative;
            margin-top: 8px;
            color: #020617;
            font-size: 42px;
            font-weight: 950;
            letter-spacing: -.05em;
            line-height: 1;
        }

        .dark .live-trip-kpi-value {
            color: #fff;
        }

        .live-trip-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
        }

        .live-truck-card {
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .07);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .live-truck-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .13);
        }

        .dark .live-truck-card {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .live-truck-head {
            padding: 22px;
            color: #fff;
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, .3), transparent 34%),
                linear-gradient(135deg, #020617, #0f172a 70%);
        }

        .live-truck-top {
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: flex-start;
        }

        .live-truck-label {
            color: #bbf7d0;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .22em;
            text-transform: uppercase;
        }

        .live-truck-plate {
            margin-top: 7px;
            font-size: 31px;
            font-weight: 950;
            letter-spacing: -.045em;
            line-height: 1;
        }

        .live-trip-number {
            display: block;
            max-width: 180px;
            overflow: hidden;
            color: #e0f2fe;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 18px;
            font-weight: 900;
            letter-spacing: -.03em;
            line-height: 1;
            text-align: right;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .live-trip-number span {
            display: block;
            margin-top: 5px;
            color: #93c5fd;
            font-size: 9px;
            font-weight: 900;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .live-status-badge {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            margin-top: 12px;
            border: 1px solid rgba(187, 247, 208, .22);
            border-radius: 999px;
            padding: 7px 10px;
            background: rgba(34, 197, 94, .13);
            color: #bbf7d0;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .live-truck-destination {
            margin-top: 22px;
        }

        .live-truck-destination-label {
            display: flex;
            justify-content: space-between;
            color: #d1d5db;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .live-destination {
            margin-top: 9px;
            color: #fff;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -.035em;
            line-height: 1.15;
        }

        .live-truck-body {
            padding: 22px;
        }

        .live-meta-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .live-meta {
            border-radius: 18px;
            padding: 13px;
            background: #f8fafc;
        }

        .dark .live-meta {
            background: rgba(255, 255, 255, .07);
        }

        .live-meta-label {
            color: #64748b;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .live-meta-value {
            margin-top: 5px;
            color: #0f172a;
            font-size: 14px;
            font-weight: 900;
        }

        .dark .live-meta-value {
            color: #f8fafc;
        }

        .live-updated {
            margin-top: 16px;
            color: #64748b;
            font-size: 12px;
        }

        .live-empty {
            border: 1px dashed #cbd5e1;
            border-radius: 28px;
            padding: 42px;
            background: #fff;
            text-align: center;
        }

        .dark .live-empty {
            border-color: rgba(255, 255, 255, .18);
            background: #111827;
        }

        .live-empty-title {
            color: #020617;
            font-size: 18px;
            font-weight: 950;
        }

        .dark .live-empty-title {
            color: #fff;
        }

        .live-empty-text {
            margin-top: 6px;
            color: #64748b;
            font-size: 13px;
        }

        @media (max-width: 1280px) {
            .live-trip-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .live-trip-hero-grid,
            .live-trip-kpis,
            .live-trip-cards {
                grid-template-columns: 1fr;
            }

            .live-trip-hero {
                padding: 24px;
            }
        }

        @media (max-width: 560px) {
            .live-truck-top {
                flex-direction: column;
            }

            .live-trip-number {
                text-align: left;
            }
        }
    </style>

    <div class="live-trip-dashboard" wire:poll.60s="carregarDados">
        <section class="live-trip-hero">
            <div class="live-trip-hero-grid">
                <div>
                    <div class="live-trip-eyebrow">Operação em tempo real</div>
                    <h2 class="live-trip-title">Caminhões em viagem agora, direto do WebScraper.</h2>
                    <p class="live-trip-subtitle">
                        Leitura temporária em cache para dashboard operacional. Cada chamada da API substitui a viagem atual do veículo.
                    </p>
                </div>

                <div class="live-trip-panel">
                    <div class="live-trip-panel-label">Última atualização recebida</div>

                    @if ($maisRecente)
                        <div class="live-trip-panel-main">{{ $maisRecente['placa'] }}</div>
                        <div class="live-trip-panel-sub">
                            Viagem {{ $maisRecente['numero_viagem'] }} · {{ $maisRecente['status'] }} · recebida em {{ $maisRecente['recebido_em_humano'] }}
                        </div>
                    @else
                        <div class="live-trip-panel-main">Sem dados</div>
                        <div class="live-trip-panel-sub">Aguardando primeira chamada em /api/integracoes/viagem-atual.</div>
                    @endif
                </div>
            </div>
        </section>

        <div class="live-trip-actions">
            <x-filament::button type="button" icon="heroicon-o-arrow-path" wire:click="carregarDados">
                Atualizar agora
            </x-filament::button>
        </div>

        <section class="live-payload-example">
            <div class="live-payload-example-head">
                <div>
                    <div class="live-payload-example-title">Exemplo de payload para o WebScraper</div>
                    <div class="live-payload-example-subtitle">Enviar com assinatura HMAC nos headers da integração.</div>
                </div>
                <div class="live-payload-endpoint">POST /api/integracoes/viagem-atual</div>
            </div>
            <pre class="live-payload-code">{
  "veiculo": "ABC1D23",
  "nro_viagem": "EXT-12345",
  "destino": "Chapeco/SC",
  "km_pago": 118.0,
  "km_sugerido": 120.5,
  "inicio": "2026-08-11 08:00:00",
  "status": "em_rota"
}</pre>
        </section>

        <section class="live-trip-kpis">
            <div class="live-trip-kpi" style="--accent: rgba(34, 197, 94, .2)">
                <div class="live-trip-kpi-label">Veículos monitorados</div>
                <div class="live-trip-kpi-value">{{ number_format($this->totalVeiculos, 0, ',', '.') }}</div>
            </div>

            <div class="live-trip-kpi" style="--accent: rgba(14, 165, 233, .18)">
                <div class="live-trip-kpi-label">Km pago total</div>
                <div class="live-trip-kpi-value">{{ number_format($this->totalKmPago, 1, ',', '.') }}</div>
            </div>

            <div class="live-trip-kpi" style="--accent: rgba(250, 204, 21, .2)">
                <div class="live-trip-kpi-label">Km sugerido total</div>
                <div class="live-trip-kpi-value">{{ number_format($this->totalKmSugerido, 1, ',', '.') }}</div>
            </div>
        </section>

        @if (filled($viagens))
            <section class="live-trip-cards">
                @foreach ($viagens as $viagem)
                    <article class="live-truck-card">
                        <div class="live-truck-head">
                            <div class="live-truck-top">
                                <div>
                                    <div class="live-truck-label">Caminhão</div>
                                    <div class="live-truck-plate">{{ $viagem['placa'] }}</div>
                                </div>

                                <div class="live-trip-number">
                                    {{ $viagem['numero_viagem'] }}
                                    <span>viagem</span>
                                </div>
                            </div>

                            <div class="live-status-badge">{{ $viagem['status'] }}</div>

                            <div class="live-truck-destination">
                                <div class="live-truck-destination-label">Destino</div>
                                <div class="live-destination">{{ $viagem['destino'] }}</div>
                            </div>
                        </div>

                        <div class="live-truck-body">
                            <div class="live-meta-grid">
                                <div class="live-meta">
                                    <div class="live-meta-label">Km pago</div>
                                    <div class="live-meta-value">{{ number_format($viagem['km_pago'], 1, ',', '.') }}</div>
                                </div>

                                <div class="live-meta">
                                    <div class="live-meta-label">Km sugerido</div>
                                    <div class="live-meta-value">{{ number_format($viagem['km_sugerido'], 1, ',', '.') }}</div>
                                </div>

                                <div class="live-meta">
                                    <div class="live-meta-label">Status</div>
                                    <div class="live-meta-value">{{ $viagem['status'] }}</div>
                                </div>

                                <div class="live-meta">
                                    <div class="live-meta-label">Início</div>
                                    <div class="live-meta-value">{{ $viagem['inicio_humano'] }}</div>
                                </div>

                                <div class="live-meta">
                                    <div class="live-meta-label">Duração viagem</div>
                                    <div class="live-meta-value">{{ $viagem['duracao_viagem'] }}</div>
                                </div>

                                <div class="live-meta">
                                    <div class="live-meta-label">Atualizado</div>
                                    <div class="live-meta-value">
                                        @if ($viagem['minutos_desde_atualizacao'] !== null)
                                            há {{ $viagem['minutos_desde_atualizacao'] }} min
                                        @else
                                            N/A
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="live-updated">Recebido em {{ $viagem['recebido_em_humano'] }}</div>
                        </div>
                    </article>
                @endforeach
            </section>
        @else
            <section class="live-empty">
                <div class="live-empty-title">Nenhuma viagem atual registrada</div>
                <div class="live-empty-text">A tela será preenchida assim que o WebScraper enviar dados para o endpoint de viagem atual.</div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
