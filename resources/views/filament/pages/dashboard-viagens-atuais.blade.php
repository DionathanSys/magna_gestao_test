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

        .live-trip-list {
            overflow: hidden;
            border: 1px solid rgba(15, 23, 42, .08);
            border-radius: 28px;
            background: #fff;
            box-shadow: 0 14px 38px rgba(15, 23, 42, .07);
        }

        .dark .live-trip-list {
            border-color: rgba(255, 255, 255, .1);
            background: #111827;
        }

        .live-trip-row {
            display: grid;
            grid-template-columns: minmax(130px, .7fr) minmax(130px, .7fr) minmax(220px, 1.3fr) minmax(90px, .45fr) minmax(120px, .65fr) minmax(140px, .75fr) minmax(130px, .7fr) minmax(145px, .75fr);
            gap: 14px;
            align-items: center;
            border-bottom: 1px solid rgba(15, 23, 42, .07);
            padding: 16px 18px;
        }

        .live-trip-row:last-child {
            border-bottom: 0;
        }

        .live-trip-row:hover {
            background: #f8fafc;
        }

        .dark .live-trip-row {
            border-color: rgba(255, 255, 255, .08);
        }

        .dark .live-trip-row:hover {
            background: rgba(255, 255, 255, .04);
        }

        .live-trip-list-head {
            background:
                radial-gradient(circle at top right, rgba(34, 197, 94, .18), transparent 34%),
                linear-gradient(135deg, #020617, #0f172a 70%);
            color: #d1d5db;
            font-size: 10px;
            font-weight: 950;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .live-list-plate {
            color: #0f172a;
            font-size: 20px;
            font-weight: 950;
            letter-spacing: -.04em;
        }

        .dark .live-list-plate {
            color: #fff;
        }

        .live-list-trip {
            color: #0369a1;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 14px;
            font-weight: 900;
        }

        .dark .live-list-trip {
            color: #93c5fd;
        }

        .live-list-destination {
            min-width: 0;
            overflow: hidden;
            color: #0f172a;
            font-size: 14px;
            font-weight: 850;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .live-list-destination {
            color: #f8fafc;
        }

        .live-list-value {
            color: #334155;
            font-size: 13px;
            font-weight: 850;
        }

        .dark .live-list-value {
            color: #cbd5e1;
        }

        .live-list-status {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            border-radius: 999px;
            padding: 7px 10px;
            background: rgba(34, 197, 94, .12);
            color: #15803d;
            font-size: 11px;
            font-weight: 950;
            text-transform: uppercase;
        }

        .dark .live-list-status {
            background: rgba(34, 197, 94, .16);
            color: #bbf7d0;
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

        @media (max-width: 900px) {
            .live-trip-hero {
                padding: 24px;
            }

            .live-trip-list-head {
                display: none;
            }

            .live-trip-row {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
                padding: 18px;
            }

            .live-list-destination {
                grid-column: span 2;
                white-space: normal;
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
            <section class="live-trip-list">
                <div class="live-trip-row live-trip-list-head">
                    <div>Veículo</div>
                    <div>Viagem</div>
                    <div>Destino</div>
                    <div>Km pago</div>
                    <div>Status</div>
                    <div>Início</div>
                    <div>Duração</div>
                    <div>Recebido em</div>
                </div>

                @foreach ($viagens as $viagem)
                    <article class="live-trip-row">
                        <div class="live-list-plate">{{ $viagem['placa'] }}</div>
                        <div class="live-list-trip">{{ $viagem['numero_viagem'] }}</div>
                        <div class="live-list-destination" title="{{ $viagem['destino'] }}">{{ $viagem['destino'] }}</div>
                        <div class="live-list-value">{{ number_format($viagem['km_pago'], 1, ',', '.') }}</div>
                        <div><span class="live-list-status">{{ $viagem['status'] }}</span></div>
                        <div class="live-list-value">{{ $viagem['inicio_humano'] }}</div>
                        <div class="live-list-value">{{ $viagem['duracao_viagem'] }}</div>
                        <div class="live-list-value">{{ $viagem['recebido_em_humano'] }}</div>
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
