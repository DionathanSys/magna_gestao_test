<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Alertas operacionais de pneus</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Despareamento por eixo/cubo e rodízio por km de ciclo.
                </p>
            </div>

            <div class="text-sm text-gray-500 dark:text-gray-400">
                Limite de rodízio: <span class="font-semibold">{{ number_format($threshold_km_rodizio, 0, ',', '.') }} km</span>
            </div>
        </div>

        @if($total_alertas === 0)
            <div class="mt-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-900 dark:bg-green-950/30 dark:text-green-300">
                Nenhum alerta ativo no momento.
            </div>
        @else
            <div class="mt-5 grid gap-6 lg:grid-cols-2">
                <div class="space-y-3">
                    <div class="text-sm font-semibold uppercase tracking-[0.08em] text-amber-700 dark:text-amber-300">
                        Despareamento: {{ $despareamento->count() }}
                    </div>

                    @forelse($despareamento as $alerta)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-amber-900 dark:text-amber-100">{{ $alerta['titulo'] }}</div>
                                    <div class="text-sm text-amber-700 dark:text-amber-300">{{ $alerta['placa'] }} · {{ $alerta['descricao'] }}</div>
                                </div>
                                @if($url = $veiculoUrl($alerta['veiculo_id']))
                                    <x-filament::link :href="$url" size="sm" icon="heroicon-m-arrow-top-right-on-square" target="_blank">
                                        Veículo
                                    </x-filament::link>
                                @endif
                            </div>

                            <div class="mt-3 space-y-2 text-sm">
                                @foreach($alerta['posicoes'] as $posicao)
                                    <div class="rounded-lg bg-white/80 px-3 py-2 dark:bg-white/5">
                                        <span class="font-medium">{{ $posicao['posicao'] }}</span>
                                        · {{ $posicao['numero_fogo'] }}
                                        · {{ $posicao['medida'] }}
                                        · {{ $posicao['marca'] }}
                                        · {{ $posicao['modelo'] }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            Nenhum despareamento identificado.
                        </div>
                    @endforelse
                </div>

                <div class="space-y-3">
                    <div class="text-sm font-semibold uppercase tracking-[0.08em] text-red-700 dark:text-red-300">
                        Rodízio por ciclo: {{ $rodizio->count() }}
                    </div>

                    @forelse($rodizio as $alerta)
                        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/20">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-semibold text-red-900 dark:text-red-100">{{ $alerta['titulo'] }}</div>
                                    <div class="text-sm text-red-700 dark:text-red-300">{{ $alerta['placa'] }} · Eixo {{ $alerta['eixo'] }} · {{ $alerta['hub'] }}</div>
                                </div>
                                @if($url = $veiculoUrl($alerta['veiculo_id']))
                                    <x-filament::link :href="$url" size="sm" icon="heroicon-m-arrow-top-right-on-square" target="_blank">
                                        Veículo
                                    </x-filament::link>
                                @endif
                            </div>

                            <div class="mt-3 rounded-lg bg-white/80 px-3 py-2 text-sm dark:bg-white/5">
                                {{ $alerta['descricao'] }}
                            </div>
                        </div>
                    @empty
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                            Nenhum pneu acima do limite de rodízio por ciclo.
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
