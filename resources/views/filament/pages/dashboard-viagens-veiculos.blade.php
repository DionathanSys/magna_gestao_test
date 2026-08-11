<x-filament-panels::page>
    <form wire:submit.prevent="carregarDados" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap gap-3">
            <x-filament::button type="submit" icon="heroicon-o-magnifying-glass">
                Aplicar filtros
            </x-filament::button>

            <x-filament::button type="button" color="gray" icon="heroicon-o-arrow-path" wire:click="limparFiltros">
                Limpar filtros
            </x-filament::button>
        </div>
    </form>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total de viagens</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalViagens(), 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Veículos com viagem</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalVeiculos(), 0, ',', '.') }}
            </div>
        </div>

        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Clientes atendidos</div>
            <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                {{ number_format($this->getTotalClientes(), 0, ',', '.') }}
            </div>
        </div>
    </div>

    @if (count($cards) > 0)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($cards as $card)
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Veículo</div>
                            <div class="mt-1 text-xl font-semibold text-gray-950 dark:text-white">
                                {{ $card['placa'] }}
                            </div>
                        </div>

                        <div class="rounded-full bg-primary-50 px-3 py-1 text-sm font-semibold text-primary-700 dark:bg-primary-400/10 dark:text-primary-300">
                            {{ number_format($card['total_viagens'], 0, ',', '.') }} viagem{{ $card['total_viagens'] === 1 ? '' : 's' }}
                        </div>
                    </div>

                    <div class="mt-5 border-t border-gray-200 pt-4 dark:border-white/10">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Cliente</div>
                        <div class="mt-1 text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ $card['cliente'] }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-xl bg-white p-8 text-center shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-base font-semibold text-gray-950 dark:text-white">Nenhuma viagem encontrada</div>
            <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajuste os filtros para visualizar os cards.</div>
        </div>
    @endif
</x-filament-panels::page>
