<x-filament-panels::page>
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
            grid-template-columns: 1fr;
            gap: 28px;
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

        .live-trip-actions {
            display: flex;
            justify-content: flex-end;
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
                    <h2 class="live-trip-title">Viagens em Andamento.</h2>
                </div>
            </div>
        </section>

        <div class="live-trip-actions">
            <x-filament::button type="button" icon="heroicon-o-arrow-path" wire:click="carregarDados">
                Atualizar agora
            </x-filament::button>
        </div>

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
                                    <div class="live-meta-label">Status</div>
                                    <div class="live-meta-value">{{ $viagem['status'] }}</div>
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
