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

    <div class="overflow-hidden rounded-3xl bg-gray-950 shadow-2xl ring-1 ring-white/10 dark:bg-black">
        <div class="relative isolate p-6 sm:p-8 lg:p-10">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_left,rgba(250,204,21,0.30),transparent_30%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.28),transparent_34%)]"></div>
            <div class="absolute inset-x-0 bottom-0 -z-10 h-px bg-gradient-to-r from-transparent via-yellow-300/70 to-transparent"></div>

            <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-end">
                <div>
                    <div class="inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-semibold uppercase tracking-[0.28em] text-yellow-200 ring-1 ring-white/15">
                        Operação de viagens
                    </div>

                    <h2 class="mt-5 max-w-3xl text-3xl font-black tracking-tight text-white sm:text-5xl">
                        Performance por caminhão, cliente por cliente.
                    </h2>

                    <p class="mt-4 max-w-2xl text-sm leading-6 text-gray-300 sm:text-base">
                        Visão rápida do volume de viagens registrado na tabela viagens, com filtro por período, veículo e cliente.
                    </p>
                </div>

                <div class="rounded-2xl bg-white/10 p-5 ring-1 ring-white/15 backdrop-blur">
                    <div class="text-sm font-medium text-gray-300">Veículo líder do período</div>

                    @if ($lider)
                        <div class="mt-3 flex items-end justify-between gap-4">
                            <div>
                                <div class="text-4xl font-black tracking-tight text-white">{{ $lider['placa'] }}</div>
                                <div class="mt-1 text-sm text-gray-300">
                                    Principal cliente: {{ $lider['principal']['cliente'] }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-black text-yellow-200">{{ number_format($lider['total'], 0, ',', '.') }}</div>
                                <div class="text-xs font-semibold uppercase tracking-widest text-gray-400">viagens</div>
                            </div>
                        </div>
                    @else
                        <div class="mt-3 text-lg font-semibold text-white">Sem viagens no período</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form wire:submit.prevent="carregarDados" class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        {{ $this->form }}

        <div class="mt-5 flex flex-wrap gap-3">
            <x-filament::button type="submit" icon="heroicon-o-funnel">
                Aplicar filtros
            </x-filament::button>

            <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="limparFiltros">
                Voltar para hoje
            </x-filament::button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="absolute right-0 top-0 h-24 w-24 rounded-bl-full bg-yellow-300/20"></div>
            <div class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total de viagens</div>
            <div class="mt-3 text-4xl font-black tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalViagens(), 0, ',', '.') }}
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="absolute right-0 top-0 h-24 w-24 rounded-bl-full bg-blue-400/20"></div>
            <div class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Veículos em rota</div>
            <div class="mt-3 text-4xl font-black tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalVeiculos(), 0, ',', '.') }}
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="absolute right-0 top-0 h-24 w-24 rounded-bl-full bg-emerald-400/20"></div>
            <div class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Clientes atendidos</div>
            <div class="mt-3 text-4xl font-black tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalClientes(), 0, ',', '.') }}
            </div>
        </div>
    </div>

    @if ($veiculos->isNotEmpty())
        <div class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-3">
            @foreach ($veiculos as $index => $veiculo)
                @php
                    $percentualVolume = ($veiculo['total'] / $maiorVolume) * 100;
                @endphp

                <div class="group overflow-hidden rounded-3xl bg-white shadow-sm ring-1 ring-gray-950/5 transition duration-200 hover:-translate-y-1 hover:shadow-xl dark:bg-gray-900 dark:ring-white/10">
                    <div class="relative bg-gray-950 p-6 text-white">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(250,204,21,0.28),transparent_35%)]"></div>

                        <div class="relative flex items-start justify-between gap-4">
                            <div>
                                <div class="text-xs font-semibold uppercase tracking-[0.25em] text-yellow-200">Caminhão</div>
                                <div class="mt-2 text-3xl font-black tracking-tight">{{ $veiculo['placa'] }}</div>
                            </div>

                            <div class="rounded-2xl bg-white px-4 py-3 text-right text-gray-950 shadow-lg">
                                <div class="text-3xl font-black leading-none">{{ number_format($veiculo['total'], 0, ',', '.') }}</div>
                                <div class="mt-1 text-[11px] font-bold uppercase tracking-widest text-gray-500">viagens</div>
                            </div>
                        </div>

                        <div class="relative mt-6">
                            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-gray-300">
                                <span>Volume relativo</span>
                                <span>#{{ $index + 1 }} no ranking</span>
                            </div>
                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                                <div class="h-full rounded-full bg-gradient-to-r from-yellow-200 via-yellow-400 to-amber-500" style="width: {{ $percentualVolume }}%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <div class="text-sm font-bold text-gray-950 dark:text-white">Clientes trabalhados</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Participação dentro do veículo</div>
                            </div>
                            <div class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                {{ $veiculo['clientes']->count() }} cliente{{ $veiculo['clientes']->count() === 1 ? '' : 's' }}
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach ($veiculo['clientes'] as $cliente)
                                @php
                                    $percentualCliente = ($cliente['total_viagens'] / max($veiculo['total'], 1)) * 100;
                                @endphp

                                <div>
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 text-sm font-semibold text-gray-800 dark:text-gray-100">
                                            {{ $cliente['cliente'] }}
                                        </div>
                                        <div class="shrink-0 rounded-full bg-primary-50 px-2.5 py-1 text-xs font-bold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                                            {{ number_format($cliente['total_viagens'], 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                                        <div class="h-full rounded-full bg-gray-950 dark:bg-yellow-300" style="width: {{ $percentualCliente }}%"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-3xl border border-dashed border-gray-300 bg-white p-12 text-center dark:border-white/15 dark:bg-gray-900">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-500 dark:bg-white/10 dark:text-gray-300">
                <x-filament::icon icon="heroicon-o-truck" class="h-7 w-7" />
            </div>
            <div class="mt-5 text-lg font-black text-gray-950 dark:text-white">Nenhuma viagem encontrada</div>
            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Tente ampliar o período ou remover filtros de veículo e cliente.</div>
        </div>
    @endif
</x-filament-panels::page>
