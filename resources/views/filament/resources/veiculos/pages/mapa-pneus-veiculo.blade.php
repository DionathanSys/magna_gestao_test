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
            padding: 1.5rem;
            box-shadow: 0 16px 45px rgba(15, 23, 42, 0.08);
        }

        .tire-map-layout {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: minmax(0, 1fr);
        }

        @media (min-width: 1100px) {
            .tire-map-layout {
                grid-template-columns: minmax(0, 1.5fr) minmax(20rem, 24rem);
                align-items: start;
            }
        }

        .tire-map-visual {
            display: grid;
            grid-template-columns: minmax(5rem, 1fr) minmax(9rem, 12rem) minmax(5rem, 1fr);
            gap: 0.75rem;
            align-items: stretch;
        }

        .tire-map-side {
            display: grid;
            gap: 1rem;
        }

        .tire-map-side-row {
            min-height: 7rem;
            display: grid;
            align-content: center;
            gap: 0.5rem;
        }

        .tire-map-side-row.is-right {
            justify-items: start;
        }

        .tire-map-side-row.is-left {
            justify-items: end;
        }

        .tire-map-vehicle {
            position: relative;
            border: 1px solid var(--map-line);
            border-radius: 999px;
            background: var(--map-body);
            min-height: 34rem;
            overflow: hidden;
        }

        .tire-map-vehicle::before {
            content: '';
            position: absolute;
            inset: 1.1rem;
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 999px;
        }

        .tire-map-cab {
            position: absolute;
            top: 1.35rem;
            left: 50%;
            transform: translateX(-50%);
            width: 62%;
            height: 7rem;
            border-radius: 1.4rem;
            background: linear-gradient(180deg, #e2e8f0 0%, #cbd5e1 100%);
        }

        .tire-map-chassis {
            position: absolute;
            top: 9rem;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            width: 1.1rem;
            border-radius: 999px;
            background: linear-gradient(180deg, #94a3b8 0%, #64748b 100%);
        }

        .tire-map-axles {
            position: relative;
            z-index: 2;
            display: grid;
            height: 100%;
            padding: 7.5rem 1rem 2rem;
        }

        .tire-map-axle {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--map-muted);
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .tire-map-axle::before {
            content: '';
            position: absolute;
            left: 1rem;
            right: 1rem;
            height: 2px;
            background: var(--map-line);
        }

        .tire-slot {
            width: 100%;
            max-width: 5.25rem;
            border-radius: 1rem;
            border: 2px solid currentColor;
            background: var(--map-surface);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
            color: var(--map-neutral);
            padding: 0.6rem 0.45rem;
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
            font-size: 0.72rem;
            font-weight: 800;
            color: var(--map-text);
        }

        .tire-slot__value {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            margin-top: 0.15rem;
        }

        .tire-slot__meta {
            display: block;
            font-size: 0.68rem;
            color: var(--map-muted);
            margin-top: 0.2rem;
            line-height: 1.2;
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
                        <div class="tire-map-side">
                            @foreach($mapa['eixos'] as $eixo)
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
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>

                        <div class="tire-map-vehicle">
                            <div class="tire-map-cab"></div>
                            <div class="tire-map-chassis"></div>
                            <div class="tire-map-axles" style="grid-template-rows: repeat({{ max(count($mapa['eixos']), 1) }}, minmax(0, 1fr));">
                                @foreach($mapa['eixos'] as $eixo)
                                    <div class="tire-map-axle">{{ $eixo['titulo'] }}</div>
                                @endforeach
                            </div>
                        </div>

                        <div class="tire-map-side">
                            @foreach($mapa['eixos'] as $eixo)
                                <div class="tire-map-side-row is-right">
                                    @foreach($eixo['right'] as $slot)
                                        <button
                                            type="button"
                                            wire:click="{{ $slot['pneu_id'] ? "openInspection({$slot['id']})" : "selectPosicao({$slot['id']})" }}"
                                            class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                        >
                                            <span class="tire-slot__code">{{ $slot['label'] }}</span>
                                            <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                            <span class="tire-slot__meta">{{ $slot['posicao'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="tire-map-summary">
                    <div class="tire-map-summary-card">
                        @if($selectedPosicao)
                            @php($ultimaInspecao = $selectedPosicao->pneu?->inspecoes?->first())
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Selecionado</p>
                                    <h3 class="mt-1 text-lg font-bold text-slate-900">
                                        {{ $selectedPosicao->pneu?->numero_fogo ?: 'Posição vazia' }}
                                    </h3>
                                    <p class="text-sm text-slate-500">
                                        {{ $selectedPosicao->sequencia ? 'P'.str_pad((string) $selectedPosicao->sequencia, 2, '0', STR_PAD_LEFT) : 'Sem sequência' }}
                                        · {{ $selectedPosicao->eixo }}º eixo · {{ $selectedPosicao->posicao }}
                                    </p>
                                </div>
                                @if($selectedCanInspect)
                                    <x-filament::button wire:click="openInspection({{ $selectedPosicao->id }})" icon="heroicon-o-arrow-top-right-on-square" size="sm">
                                        Inspecionar
                                    </x-filament::button>
                                @endif
                            </div>

                            <div class="mt-5 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Marca / Modelo</span>
                                    <span class="text-right font-medium text-slate-900">
                                        {{ trim(($selectedPosicao->pneu?->marcaCatalogo?->nome ?? '').' '.($selectedPosicao->pneu?->modeloCatalogo?->nome ?? '')) ?: '-' }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Medida</span>
                                    <span class="text-right font-medium text-slate-900">{{ $selectedPosicao->pneu?->medidaCatalogo?->codigo ?? '-' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Km na posição</span>
                                    <span class="text-right font-medium text-slate-900">{{ number_format($selectedPosicao->km_rodado ?? 0, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Última inspeção</span>
                                    <span class="text-right font-medium text-slate-900">{{ $ultimaInspecao?->data_inspecao?->format('d/m/Y') ?? 'Sem registro' }}</span>
                                </div>
                                <div class="flex items-center justify-between gap-3">
                                    <span class="text-slate-500">Resultado</span>
                                    <span class="text-right font-medium text-slate-900">{{ $ultimaInspecao?->resultado?->value ?? 'N/A' }}</span>
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">Nenhuma posição configurada para este veículo.</p>
                        @endif
                    </div>

                    <div class="tire-map-summary-card">
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">Legenda</p>
                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex items-center gap-3"><span class="inline-block h-3 w-3 rounded-full bg-green-600"></span><span class="text-slate-700">Aprovado</span></div>
                            <div class="flex items-center gap-3"><span class="inline-block h-3 w-3 rounded-full bg-amber-500"></span><span class="text-slate-700">Monitorar / aguardando conserto</span></div>
                            <div class="flex items-center gap-3"><span class="inline-block h-3 w-3 rounded-full bg-blue-600"></span><span class="text-slate-700">Apto para recapagem</span></div>
                            <div class="flex items-center gap-3"><span class="inline-block h-3 w-3 rounded-full bg-red-600"></span><span class="text-slate-700">Reprovado / condenado</span></div>
                            <div class="flex items-center gap-3"><span class="inline-block h-3 w-3 rounded-full bg-slate-400"></span><span class="text-slate-700">Sem inspeção registrada</span></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{ $this->table }}
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
