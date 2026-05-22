<x-filament-widgets::widget>
    <x-filament::section>
        <style>
            .pneu-alertas-shell {
                display: grid;
                gap: 1.25rem;
            }

            .pneu-alertas-grid {
                display: grid;
                gap: 1rem;
            }

            @media (min-width: 1024px) {
                .pneu-alertas-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .pneu-alertas-panel {
                border: 1px solid #e5e7eb;
                border-radius: 1.25rem;
                background: #ffffff;
                overflow: hidden;
            }

            .pneu-alertas-panel__head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.75rem;
                padding: 1rem 1rem 0.85rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .pneu-alertas-panel__title {
                font-size: 0.8rem;
                font-weight: 800;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .pneu-alertas-panel__hint {
                margin-top: 0.25rem;
                font-size: 0.8rem;
                color: #64748b;
            }

            .pneu-alertas-panel__count {
                min-width: 2rem;
                padding: 0.35rem 0.7rem;
                border-radius: 999px;
                font-size: 0.8rem;
                font-weight: 800;
                text-align: center;
            }

            .pneu-alertas-panel--warning .pneu-alertas-panel__title {
                color: #b45309;
            }

            .pneu-alertas-panel--warning .pneu-alertas-panel__count {
                background: #fef3c7;
                color: #92400e;
            }

            .pneu-alertas-panel--danger .pneu-alertas-panel__title {
                color: #b91c1c;
            }

            .pneu-alertas-panel--danger .pneu-alertas-panel__count {
                background: #fee2e2;
                color: #b91c1c;
            }

            .pneu-alertas-panel__body {
                display: grid;
                gap: 0.85rem;
                padding: 1rem;
            }

            .pneu-alerta-card {
                border-radius: 1rem;
                padding: 1rem;
            }

            .pneu-alerta-card--warning {
                border: 1px solid #fde68a;
                background: linear-gradient(180deg, #fffdf5 0%, #fffbeb 100%);
            }

            .pneu-alerta-card--danger {
                border: 1px solid #fecaca;
                background: linear-gradient(180deg, #fffafa 0%, #fef2f2 100%);
            }

            .pneu-alerta-card__head {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .pneu-alerta-card__title {
                font-weight: 700;
                color: #0f172a;
            }

            .pneu-alerta-card__subtitle {
                margin-top: 0.15rem;
                font-size: 0.84rem;
                color: #475569;
            }

            .pneu-alerta-card__list {
                display: grid;
                gap: 0.5rem;
                margin-top: 0.85rem;
            }

            .pneu-alerta-card__item {
                border-radius: 0.85rem;
                background: rgba(255, 255, 255, 0.78);
                padding: 0.7rem 0.8rem;
                font-size: 0.85rem;
                color: #334155;
            }

            .pneu-alerta-card__chips {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
                margin-top: 0.45rem;
            }

            .pneu-alerta-chip {
                border-radius: 999px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                padding: 0.18rem 0.5rem;
                font-size: 0.74rem;
                color: #475569;
            }

            .pneu-alerta-empty {
                border: 1px dashed #cbd5e1;
                border-radius: 1rem;
                background: #f8fafc;
                padding: 1rem;
                font-size: 0.9rem;
                color: #64748b;
            }
        </style>

        <div class="pneu-alertas-shell">
            <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Alertas operacionais de pneus</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Despareamento por eixo/cubo com foco em medida e marca, separado do alerta de rodízio por km na posição atual.
                    </p>
                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Limite de rodízio: <span class="font-semibold">{{ number_format($threshold_km_rodizio, 0, ',', '.') }} km</span>
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-4">
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Placa</label>
                    <select wire:model.live="veiculoIdFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todas</option>
                        @foreach($placas as $id => $placa)
                            <option value="{{ $id }}">{{ $placa }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Eixo</label>
                    <select wire:model.live="eixoFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todos</option>
                        @foreach($eixos as $eixo => $label)
                            <option value="{{ $eixo }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Posição</label>
                    <select wire:model.live="posicaoFilter" class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Todas</option>
                        @foreach($posicoes as $posicao => $label)
                            <option value="{{ $posicao }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex items-end">
                    <x-filament::button color="gray" wire:click="resetFilters" class="w-full">
                        Limpar filtros
                    </x-filament::button>
                </div>
            </div>

            @if($total_alertas === 0)
                <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300">
                    Nenhum alerta ativo no momento.
                </div>
            @else
                <div class="pneu-alertas-grid">
                    <section class="pneu-alertas-panel pneu-alertas-panel--warning">
                        <div class="pneu-alertas-panel__head">
                            <div>
                                <div class="pneu-alertas-panel__title">Despareamento</div>
                                <div class="pneu-alertas-panel__hint">
                                    Considera divergência entre pneus do mesmo cubo apenas por medida e marca.
                                </div>
                            </div>

                            <div class="pneu-alertas-panel__count">{{ $despareamento->count() }}</div>
                        </div>

                        <div class="pneu-alertas-panel__body">
                            @forelse($despareamento as $alerta)
                                <article class="pneu-alerta-card pneu-alerta-card--warning">
                                    <div class="pneu-alerta-card__head">
                                        <div>
                                            <div class="pneu-alerta-card__title">{{ $alerta['titulo'] }}</div>
                                            <div class="pneu-alerta-card__subtitle">{{ $alerta['placa'] }} · {{ $alerta['descricao'] }}</div>
                                        </div>

                                        @if($url = $veiculoUrl($alerta['veiculo_id']))
                                            <x-filament::link :href="$url" size="sm" icon="heroicon-m-arrow-top-right-on-square" target="_blank">
                                                Veículo
                                            </x-filament::link>
                                        @endif
                                    </div>

                                    <div class="pneu-alerta-card__list">
                                        @foreach($alerta['posicoes'] as $posicao)
                                            <div class="pneu-alerta-card__item">
                                                <div><span class="font-medium">{{ $posicao['posicao'] }}</span> · {{ $posicao['numero_fogo'] }}</div>
                                                <div class="pneu-alerta-card__chips">
                                                    <span class="pneu-alerta-chip">Medida: {{ $posicao['medida'] }}</span>
                                                    <span class="pneu-alerta-chip">Marca: {{ $posicao['marca'] }}</span>
                                                    <span class="pneu-alerta-chip">Modelo: {{ $posicao['modelo'] }}</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </article>
                            @empty
                                <div class="pneu-alerta-empty">
                                    Nenhum despareamento identificado.
                                </div>
                            @endforelse
                        </div>
                    </section>

                    <section class="pneu-alertas-panel pneu-alertas-panel--danger">
                        <div class="pneu-alertas-panel__head">
                            <div>
                                <div class="pneu-alertas-panel__title">Rodízio por ciclo</div>
                                <div class="pneu-alertas-panel__hint">
                                    Pneus que atingiram ou ultrapassaram o limite de km configurado na posição atual.
                                </div>
                            </div>

                            <div class="pneu-alertas-panel__count">{{ $rodizio->count() }}</div>
                        </div>

                        <div class="pneu-alertas-panel__body">
                            @forelse($rodizio as $alerta)
                                <article class="pneu-alerta-card pneu-alerta-card--danger">
                                    <div class="pneu-alerta-card__head">
                                        <div>
                                            <div class="pneu-alerta-card__title">{{ $alerta['titulo'] }}</div>
                                            <div class="pneu-alerta-card__subtitle">{{ $alerta['placa'] }} · Eixo {{ $alerta['eixo'] }} · {{ $alerta['hub'] }}</div>
                                        </div>

                                        @if($url = $veiculoUrl($alerta['veiculo_id']))
                                            <x-filament::link :href="$url" size="sm" icon="heroicon-m-arrow-top-right-on-square" target="_blank">
                                                Veículo
                                            </x-filament::link>
                                        @endif
                                    </div>

                                    <div class="pneu-alerta-card__list">
                                        <div class="pneu-alerta-card__item">
                                            {{ $alerta['descricao'] }}
                                        </div>
                                    </div>
                                </article>
                            @empty
                                <div class="pneu-alerta-empty">
                                    Nenhum pneu acima do limite de rodízio na posição atual.
                                </div>
                            @endforelse
                        </div>
                    </section>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
