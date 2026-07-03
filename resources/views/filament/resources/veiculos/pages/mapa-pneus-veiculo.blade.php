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

        .tire-map-top-stack {
            display: grid;
            gap: 1rem;
        }

        .tire-map-visual {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.75rem;
            align-items: stretch;
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
            max-width: 6.48rem;
            min-height: 5.4rem;
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
            display: flex;
            flex-direction: column;
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

        .tire-map-eixo__front-line {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            align-items: start;
            gap: 1.5rem;
        }

        .tire-map-eixo__front-side {
            display: flex;
        }

        .tire-map-eixo__front-side.is-left {
            justify-content: flex-start;
        }

        .tire-map-eixo__front-side.is-right {
            justify-content: flex-end;
        }

        .tire-map-note {
            border: 1px solid #dbeafe;
            border-radius: 1rem;
            background: #eff6ff;
            color: #1e3a8a;
            padding: 0.85rem 1rem;
            font-size: 0.85rem;
        }

        .tire-map-modebar {
            border: 1px solid #e2e8f0;
            border-radius: 1.25rem;
            background: #ffffff;
            padding: 1rem;
            display: grid;
            gap: 0.85rem;
        }

        .tire-map-modebar__head {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            justify-content: space-between;
            align-items: flex-start;
        }

        .tire-map-modebar__title {
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--map-muted);
        }

        .tire-map-modebar__meta {
            color: var(--map-text);
            font-size: 0.95rem;
            font-weight: 700;
        }

        .tire-map-modebar__submeta {
            color: var(--map-muted);
            font-size: 0.8rem;
        }

        .tire-map-modes {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tire-map-mode {
            border: 1px solid #cbd5e1;
            border-radius: 999px;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.8rem;
            font-weight: 700;
            line-height: 1;
            padding: 0.6rem 0.85rem;
            cursor: pointer;
            transition: background .15s ease, border-color .15s ease, transform .15s ease;
        }

        .tire-map-mode:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            transform: translateY(-1px);
        }

        .tire-map-mode.is-active {
            border-color: #bfdbfe;
            color: #1d4ed8;
            background: #eff6ff;
        }
    </style>

    <div class="tire-map-shell space-y-6">
        <div class="tire-map-board">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-slate-400">Frota / Inspecao de Pneus</p>
                    <h2 class="text-2xl font-bold text-slate-900">Mapa de pneus - {{ $record->placa }}</h2>
                    <p class="text-sm text-slate-500">
                        {{ $mapa['configuracao_label'] }}
                        @if($record->tipoVeiculo?->descricao)
                            - {{ $record->tipoVeiculo->descricao }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="mt-8 tire-map-layout">
                <div class="tire-map-top-stack">
                    <div class="tire-map-note">
                        O clique no slot segue o modo ativo abaixo. Troque o modo antes de clicar no pneu para evitar abrir a acao errada.
                    </div>

                    <div class="tire-map-modebar">
                        <div class="tire-map-modebar__head">
                            <div>
                                <div class="tire-map-modebar__title">Modo Ativo</div>
                                <div class="tire-map-modebar__meta">
                                    {{ $interactionModes[$interactionMode]['label'] ?? 'Inspecionar' }}
                                </div>
                                <div class="tire-map-modebar__submeta">
                                    {{ $interactionModes[$interactionMode]['hint'] ?? '' }}
                                </div>
                            </div>

                            @if($selectedPosicao)
                                <div>
                                    <div class="tire-map-modebar__title">Posicao Selecionada</div>
                                    <div class="tire-map-modebar__meta">
                                        {{ $selectedPosicao->eixo }}o eixo / {{ $selectedPosicao->mapaPosicao?->nome ?? $selectedPosicao->posicao }}
                                    </div>
                                    <div class="tire-map-modebar__submeta">
                                        {{ $selectedPosicao->pneu?->numero_fogo ? 'Pneu ' . $selectedPosicao->pneu->numero_fogo : 'Posicao vazia' }}
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="tire-map-modes">
                            @foreach($interactionModes as $modeKey => $mode)
                                <button
                                    type="button"
                                    wire:click="setInteractionMode('{{ $modeKey }}')"
                                    class="tire-map-mode {{ $interactionMode === $modeKey ? 'is-active' : '' }}"
                                >
                                    {{ $mode['label'] }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    <div class="tire-map-visual">
                        @foreach($mapa['eixos'] as $eixo)
                            <div class="tire-map-eixo">
                                <div class="tire-map-eixo__title">{{ $eixo['titulo'] }}</div>

                                @php($mergedSlots = array_merge($eixo['left'], $eixo['right']))
                                @php($isFrontAxleLayout = count($eixo['left']) === 1 && count($eixo['right']) === 1)

                                @if(count($mergedSlots) >= 4)
                                    <div class="tire-map-eixo__line">
                                        @foreach($mergedSlots as $slot)
                                            <button
                                                type="button"
                                                wire:click="handleSlotClick({{ $slot['id'] }})"
                                                class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                            >
                                                <span class="tire-slot__code">{{ $slot['modelo'] ?: 'Sem modelo' }}</span>
                                                <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                <span class="tire-slot__meta">{{ $slot['posicao_nome'] ?? $slot['posicao'] }}</span>
                                                @if($slot['pneu_id'])
                                                    <span class="tire-slot__meta">{{ $slot['desenho_atual'] ?: 'Sem desenho' }}</span>
                                                @endif
                                                @if($slot['pneu_id'])
                                                    <span class="tire-slot__km">
                                                        Pos.: {{ $slot['km_rodado'] !== null ? number_format((float) $slot['km_rodado'], 0, ',', '.') : '0' }} km
                                                    </span>
                                                @endif
                                                @if($slot['pneu_id'])
                                                    <span class="tire-slot__km">
                                                        Ciclo: {{ $slot['km_ciclo_atual'] !== null ? number_format((float) $slot['km_ciclo_atual'], 0, ',', '.') : '0' }} km
                                                    </span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @elseif($isFrontAxleLayout)
                                    <div class="tire-map-eixo__front-line">
                                        <div class="tire-map-eixo__front-side is-left">
                                            @foreach($eixo['left'] as $slot)
                                                <button
                                                    type="button"
                                                    wire:click="handleSlotClick({{ $slot['id'] }})"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['modelo'] ?: 'Sem modelo' }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao_nome'] ?? $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__meta">{{ $slot['desenho_atual'] ?: 'Sem desenho' }}</span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Pos.: {{ $slot['km_rodado'] !== null ? number_format((float) $slot['km_rodado'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Ciclo: {{ $slot['km_ciclo_atual'] !== null ? number_format((float) $slot['km_ciclo_atual'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>

                                        <div class="tire-map-eixo__front-side is-right">
                                            @foreach($eixo['right'] as $slot)
                                                <button
                                                    type="button"
                                                    wire:click="handleSlotClick({{ $slot['id'] }})"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['modelo'] ?: 'Sem modelo' }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao_nome'] ?? $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__meta">{{ $slot['desenho_atual'] ?: 'Sem desenho' }}</span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Pos.: {{ $slot['km_rodado'] !== null ? number_format((float) $slot['km_rodado'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Ciclo: {{ $slot['km_ciclo_atual'] !== null ? number_format((float) $slot['km_ciclo_atual'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    @if(count($eixo['left']))
                                        <div class="tire-map-side-row is-left">
                                            @foreach($eixo['left'] as $slot)
                                                <button
                                                    type="button"
                                                    wire:click="handleSlotClick({{ $slot['id'] }})"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['modelo'] ?: 'Sem modelo' }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao_nome'] ?? $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__meta">{{ $slot['desenho_atual'] ?: 'Sem desenho' }}</span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Pos.: {{ $slot['km_rodado'] !== null ? number_format((float) $slot['km_rodado'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Ciclo: {{ $slot['km_ciclo_atual'] !== null ? number_format((float) $slot['km_ciclo_atual'], 0, ',', '.') : '0' }} km
                                                        </span>
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
                                                    wire:click="handleSlotClick({{ $slot['id'] }})"
                                                    class="tire-slot tire-slot--{{ $slot['status'] }} {{ $slot['selected'] ? 'is-selected' : '' }}"
                                                >
                                                    <span class="tire-slot__code">{{ $slot['modelo'] ?: 'Sem modelo' }}</span>
                                                    <span class="tire-slot__value">{{ $slot['numero_fogo'] ?: 'Vazio' }}</span>
                                                    <span class="tire-slot__meta">{{ $slot['posicao_nome'] ?? $slot['posicao'] }}</span>
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__meta">{{ $slot['desenho_atual'] ?: 'Sem desenho' }}</span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Pos.: {{ $slot['km_rodado'] !== null ? number_format((float) $slot['km_rodado'], 0, ',', '.') : '0' }} km
                                                        </span>
                                                    @endif
                                                    @if($slot['pneu_id'])
                                                        <span class="tire-slot__km">
                                                            Ciclo: {{ $slot['km_ciclo_atual'] !== null ? number_format((float) $slot['km_ciclo_atual'], 0, ',', '.') : '0' }} km
                                                        </span>
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
