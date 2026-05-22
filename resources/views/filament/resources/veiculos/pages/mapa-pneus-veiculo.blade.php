<x-filament-panels::page>
    <style>
        .tire-map-shell {
            --map-line: #cbd5e1;
            --map-body: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            --map-surface: #ffffff;
            --map-text: #0f172a;
            --map-muted: #64748b;
            --map-ok: #16a34a;
            --map-warning: #f59e0b;
            --map-info: #2563eb;
            --map-danger: #dc2626;
            --map-neutral: #94a3b8;
        }

        .tire-map-board {
            background:
                radial-gradient(circle at top, rgba(37, 99, 235, 0.08), transparent 32%),
                linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 1.5rem;
            padding: 1rem;
            box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
        }

        .tire-map-layout {
            display: block;
        }

        .tire-map-visual {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.75rem;
            align-items: stretch;
        }

        .tire-map-side {
            display: grid;
            gap: 1rem;
        }

        .tire-map-side-row {
            min-height: 4.5rem;
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.4rem;
            justify-content: center;
        }

        .tire-map-side-row.is-right {
            justify-content: center;
        }

        .tire-map-side-row.is-left {
            justify-content: center;
        }

        .tire-slot {
            width: 100%;
            max-width: 4.5rem;
            border-radius: 0.9rem;
            border: 2px solid currentColor;
            background: var(--map-surface);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            color: var(--map-neutral);
            padding: 0.45rem 0.35rem;
            transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            text-align: left;
        }

        .tire-slot:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.12);
        }

        .tire-slot.is-selected {
            outline: 3px solid rgba(37, 99, 235, 0.18);
            transform: translateY(-2px);
        }

        .tire-slot--ok { color: var(--map-ok); }
        .tire-slot--warning { color: var(--map-warning); }
        .tire-slot--info { color: var(--map-info); }
        .tire-slot--danger { color: var(--map-danger); }
        .tire-slot--neutral { color: var(--map-neutral); }

        .tire-slot__code {
            display: block;
            font-size: 0.65rem;
            font-weight: 800;
            color: var(--map-text);
        }

        .tire-slot__value {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            margin-top: 0.1rem;
        }

        .tire-slot__meta {
            display: block;
            font-size: 0.62rem;
            color: var(--map-muted);
            margin-top: 0.15rem;
            line-height: 1.2;
        }

        .tire-slot__km {
            display: block;
            font-size: 0.58rem;
            font-weight: 700;
            color: var(--map-text);
            margin-top: 0.18rem;
        }

        .tire-map-eixo {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: rgba(255, 255, 255, 0.88);
            padding: 0.75rem;
        }

        .tire-map-eixo__title {
            margin-bottom: 0.5rem;
            text-align: center;
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--map-muted);
        }

        .tire-map-eixo__line {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.4rem;
        }

        .tire-map-summary {
            display: grid;
            gap: 0.9rem;
        }

        .tire-map-summary-card {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            padding: 1rem;
        }

        .tire-map-stats {
            display: grid;
            gap: 0.75rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .tire-map-stat {
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            background: #f8fafc;
            padding: 0.85rem;
        }
    </style>

    <div class="tire-map-shell space-y-6">
        <div class="tire-map-board">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Frota / Inspeção de Pneus</p>
                    <h2 class="text-2xl font-bold text-slate-900">Mapa de pneus — {{ $record->placa }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $mapa['configuracao_label'] }}
                        @if($record->tipoVeiculo?->descricao)
                            · {{ $record->tipoVeiculo->descricao }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-6 tire-map-layout">
                <div class="space-y-5">
                    <div class="tire-map-stats">
                        <div class="tire-map-stat">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Posições</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $mapa['resumo']['total_posicoes'] }}</div>
                        </div>
                        <div class="tire-map-stat">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Aplicados</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $mapa['resumo']['total_aplicados'] }}</div>
                        </div>
                        <div class="tire-map-stat">
                            <div class="text-xs uppercase tracking-[0.18em] text-slate-400">Inspecionados</div>
                            <div class="mt-2 text-2xl font-bold text-slate-900">{{ $mapa['resumo']['total_inspecionados'] }}</div>
                        </div>
                    </div>

                    <div class="tire-map-visual">
                        @foreach($mapa['eixos'] as $eixo)
                            <div class="tire-map-eixo">
                                <div class="tire-map-eixo__title">{{ $eixo['titulo'] }}</div>

                                @php($mergedSlots = array_merge($eixo['left'], $eixo['right']))

                                @if(count($mergedSlots) >= 4)
                                    <div class="tire-map-eixo__line">
                                        @foreach($mergedSlots as $slot)
                                            <button
                                                type="button"
                                                wire:click="{{ $slot['pneu_id'] ? "openInspection({$slot['id']})" : "selectPosicao({$slot['id']})" }}"
                                                class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                            >
                                                <span class="tire-slot__code">{{ $slot['label'] }}</span>
                                                <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                <span class="tire-slot__meta">{{ $slot['posicao'] }}</span>
                                                @if($slot['pneu_id'])
                                                    <span class="tire-slot__km">{{ number_format($slot['km_ciclo_atual'] ?? 0, 0, ',', '.') }} km</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    @if(count($eixo['left']))
                                        <div class="tire-map-side-row is-left">
                                            @foreach($eixo['left'] as $slot)
                                                <button
                                                    type="button"
                                                    wire:click="{{ $slot['pneu_id'] ? "openInspection({$slot['id']})" : "selectPosicao({$slot['id']})" }}"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['label'] }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">{{ number_format($slot['km_ciclo_atual'] ?? 0, 0, ',', '.') }} km</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if(count($eixo['right']))
                                        <div class="tire-map-side-row is-right mt-2">
                                            @foreach($eixo['right'] as $slot)
                                                <button
                                                    type="button"
                                                    wire:click="{{ $slot['pneu_id'] ? "openInspection({$slot['id']})" : "selectPosicao({$slot['id']})" }}"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['label'] }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">{{ number_format($slot['km_ciclo_atual'] ?? 0, 0, ',', '.') }} km</span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
